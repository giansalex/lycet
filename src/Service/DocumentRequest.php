<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 21:47
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use Greenter\Model\Response\BaseResult;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Response\SummaryResult;
use Greenter\Report\ReportInterface;
use Greenter\Report\XmlUtils;
use Greenter\See;
use Greenter\Validator\DocumentValidatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class DocumentRequest
 */
class DocumentRequest implements DocumentRequestInterface
{
    /**
     * @var string
     */
    private $className;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var DocumentValidatorInterface
     */
    private $validator;
    /**
     * @var RequestParserInterface
     */
    private $parser;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        RequestStack $requestStack,
        DocumentValidatorInterface $validator,
        RequestParserInterface $parser,
        ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->validator = $validator;
        $this->parser = $parser;
        $this->container = $container;
    }

    /**
     * Set document to process.
     *
     * @param string $class
     */
    public function setDocumentType(string $class)
    {
        $this->className = $class;
    }

    /**
     * Get Result.
     *
     * @return Response
     */
    public function send(): Response
    {
       $document = $this->getDocument();

        /**@var $errors array */
        $errors = $this->validator->validate($document);
        if (count($errors)) {
            return $this->json($errors, 400);
        }

        $see = $this->getSeeWithCert();
        $result = $see->send($document);

        $this->toBase64Zip($result);
        $xml = $see->getFactory()->getLastXml();

        $data = [
            'xml' => $xml,
            'hash' => $this->GetHashFromXml($xml),
            'sunatResponse' => $result
        ];

        return $this->json($data);
    }

    /**
     * Get Xml.
     *
     * @return Response
     */
    public function xml(): Response
    {
        $document = $this->getDocument();

        /**@var $errors array */
        $errors = $this->validator->validate($document);
        if (count($errors)) {
            return $this->json($errors, 400);
        }

        $see = $this->getSeeWithCert();

        $xml  = $see->getXmlSigned($document);

        return $this->file($xml, $document->getName().'.xml', 'text/xml');
    }

    /**
     * Get Pdf.
     *
     * @return Response
     */
    public function pdf(): Response
    {
        $document = $this->getDocument();

        /**@var $errors array */
        $errors = $this->validator->validate($document);
        if (count($errors)) {
            return $this->json($errors, 400);
        }
        $path = $this->getParameter('logo_path');
        $logo = $path ? file_get_contents($path) : '';

        $parameters = [
            'system' => [
                'logo' => $logo,
//                'hash' => '',
            ],
            'user' => [
                'resolucion' => '-',
                'header' => '',
            ]
        ];

        $pdf  = $this->getReport()->render($document, $parameters);

        return $this->file($pdf, $document->getName().'.pdf', 'application/pdf');
    }

    /**
     * Get Configured See.
     *
     * @return See
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getSee(): See
    {
        $user = getenv('SOL_USER');
        $pass = getenv('SOL_PASS');
        $see = $this->container->get(See::class);
        $see->setCredentials($user, $pass);

        return $see;
    }

    /**
     * @return DocumentInterface
     */
    private function getDocument()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->parser->getObject($request, $this->className);
    }

    private function getReport()
    {
        return $this->container->get(ReportInterface::class);
    }

    private function getSeeWithCert()
    {
        $see = $this->getSee();
        $data = $this->getParameter('certificate');
        $see->setCertificate($data);

        return $see;
    }

    private function json($data, int $status = 200, array $headers = [], array $context = [])
    {
        $json = $this->container->get('serializer')->serialize($data, 'json', array_merge(array(
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ), $context));

        return new JsonResponse($json, $status, $headers, true);
    }

    private function file($content, string $fileName, string $contentType): Response
    {
        $response = new Response($content);

        // Create the disposition of the file
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        // Set the content disposition
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', $contentType);

        return $response;
    }

    private function getParameter($key): string
    {
        $config = $this->container->get(ConfigProviderInterface::class);
        $value = $config->get($key);

        return $value;
    }

    private function GetHashFromXml($xml): string
    {
        $utils = $this->container->get(XmlUtils::class);

        return $utils->getHashSign($xml);
    }

    /**
     * @param $result
     */
    private function toBase64Zip(BaseResult $result): void
    {
        if ($result->isSuccess() && !($result instanceof SummaryResult)) {
            /**@var $result BillResult */
            $zip = $result->getCdrZip();
            if ($zip) {
                $result->setCdrZip(base64_encode($zip));
            }
        }
    }
}