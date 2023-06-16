<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:06 PM.
 */

namespace App\Controller\v1;

use App\Service\ConfigProviderInterface;
use App\Service\DocumentRequestInterface;
use Greenter\Model\Sale\Invoice;
use Greenter\Ws\Services\ConsultCdrService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SunatEndpoints;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InvoiceController.
 *
 * @Route("/api/v1/invoice")
 */
class InvoiceController extends AbstractController
{
    /**
     * @var DocumentRequestInterface
     */
    private $document;

    /**
     * @var ConfigProviderInterface
     */
    private $config;

    /**
     * @var ConfigProviderInterface
     */
    private $fileProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * InvoiceController constructor.
     * @param DocumentRequestInterface $document
     * @param ConfigProviderInterface $config
     * @param ConfigProviderInterface $fileProvider
     * @param SerializerInterface $serializer
     */
    public function __construct(DocumentRequestInterface $document, ConfigProviderInterface $config, ConfigProviderInterface $fileProvider, SerializerInterface $serializer)
    {
        $this->document = $document;
        $this->document->setDocumentType(Invoice::class);
        $this->config = $config;
        $this->fileProvider = $fileProvider;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/send", methods={"POST"})
     *
     * @return Response
     */
    public function send(): Response
    {
        return $this->document->send();
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml();
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf();
    }

    /**
     * @Route("/status", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $tipo = $request->query->get('tipo');
        $serie = $request->query->get('serie');
        $numero = $request->query->get('numero');
        if (empty($tipo)) {
            return new JsonResponse(['message' => 'Tipo Requerido'], 400);
        }
        if (empty($serie)) {
            return new JsonResponse(['message' => 'Serie Requerido'], 400);
        }
        if (empty($numero)) {
            return new JsonResponse(['message' => 'Numero Requerido'], 400);
        }
        $see = $this->getCdrStatusService($request->query->get('ruc'));
        $username = $this->getConfig('SOL_USER', $request->query->get('ruc'));
        $ruc = substr($username, 0, 11);
        $result = $see->getStatusCdr($ruc, $tipo, $serie, $numero);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }

        $json = $this->serializer->serialize($result, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @param $ruc |null
     * @return ConsultCdrService|false
     */
    private function getCdrStatusService($ruc = null)
    {
        $ws = new SoapClient(SunatEndpoints::FE_CONSULTA_CDR . '?wsdl');

        if (!empty($ruc)) {
            $ws->setCredentials($this->getConfig('SOL_USER', $ruc), $this->getConfig('SOL_PASS', $ruc));
        } else {
            $ws->setCredentials($this->getConfig('SOL_USER'), $this->getConfig('SOL_PASS'));
        }

        $service = new ConsultCdrService();
        $service->setClient($ws);

        return $service;
    }

    private function getConfig($key, $ruc = null)
    {
        if (empty($ruc)) {
            return $this->config->get($key);
        }

        $jsonCompanies = $this->fileProvider->get('companies');
        if (empty($jsonCompanies)) {
            return false;
        }

        $companies = json_decode($jsonCompanies, true);

        if (!array_key_exists($ruc, $companies)) {
            return false;
        }

        $config = $companies[$ruc];

        return $config[$key];
    }
}
