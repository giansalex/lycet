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
     * @var RequestStack
     */
    private $requestStack;
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
        RequestParserInterface $parser,
        ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->parser = $parser;
        $this->container = $container;
    }

    /**
     * Get Result. 
     * @param string $class
     * @return Response
     */
    public function send(string $class): Response
    {
        $document = $this->getDocument($class);
        $company = $document->getCompany();

        $see = $this->getSee($class, $company->getRuc());
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
     * @param string $class
     * @return Response
     */
    public function xml(string $class): Response
    {
        $document = $this->getDocument($class);
        $company = $document->getCompany();
        $see = $this->getSee($class, $company->getRuc());

        $xml  = $see->getXmlSigned($document);

        return $this->file($xml, $document->getName().'.xml', 'text/xml');
    }

    /**
     * Get Pdf.
     * 
     * @param string $class
     * @return Response
     */
    public function pdf(string $class): Response
    {
        $document = $this->getDocument($class);
        $params = $this->getKeyContent("parameters");

        $company = $document->getCompany();
        $jsonCompanies = $this->getParameter('companies');
        $ruc = $company->getRuc();
        if (empty($companies) && ($companies = json_decode($jsonCompanies, true)) && array_key_exists($ruc, $companies)) {
            $logo = $this->getFile($companies[$ruc]['logo']);
        } else {
            $logo = $this->getParameter('logo');
        }

        $see = $this->getSee($class, $ruc);

        $parameters = [
            'system' => [
                'logo' => $params['system']['logo'] ?? $logo,
                'hash' => $this->getHashFromXml($see->getXmlSigned($document)),
            ],
            'user' => [
                'header' => '',
                'extras' => $params['user']['extras'] ?? [],
            ]
        ];

        $report = $this->getReport();
        $pdf = $report->render($document, $parameters);

        return $this->file($pdf, $document->getName() . '.pdf', 'application/pdf');
    }

    /**
     * Get Configured See.
     *
     * @param string $class
     * @param string $ruc
     * @return See
     */
    public function getSee(string $class, string $ruc): See
    {
        $factory = $this->container->get(SeeFactory::class);

        return $factory->build($class, $ruc);
    }

    /**
     * Get parsed document.
     *
     * @param string $class
     * @return DocumentInterface
     */
    public function getDocument(string $class): DocumentInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->parser->getObject($request, $class);
    }

    /**
     * @return array
     */
    private function getKeyContent(string $key): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        return $this->parser->getKey($request, $key);
    }

    private function getReport(): ReportInterface
    {
        return $this->container->get(ReportInterface::class);
    }

    private function json($data, int $status = 200, array $headers = [])
    {
        $json = $this->container->get('serializer')->serialize($data, 'json');

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

        return $config->get($key);
    }

    private function getFile($filename): string
    {
        $config = $this->container->get(FileDataReader::class);

        return $config->getContents($filename);
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
