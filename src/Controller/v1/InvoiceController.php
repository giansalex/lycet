<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:06 PM.
 */

namespace App\Controller\v1;

use App\Service\ConsultCdrServiceFactory;
use App\Service\DocumentRequestInterface;
use Greenter\Model\Sale\Invoice;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * InvoiceController constructor.
     * @param DocumentRequestInterface $document
     * @param SerializerInterface $serializer
     */
    public function __construct(DocumentRequestInterface $document, SerializerInterface $serializer)
    {
        $this->document = $document;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/send", methods={"POST"})
     *
     * @OA\Post(
     *     path="/invoice/send",
     *     tags={"invoice"},
     *     summary="Enviar una Factura o Boleta",
     *     description="Retorna la respuesta de SUNAT y el XML firmado",
     *     operationId="sendInvoice",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/DocumentResponse")),
     *     @OA\Response(response=400, description="Error de Validación",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ValidationResponse")))
     * )
     *
     * @return Response
     */
    public function send(): Response
    {
        return $this->document->send(Invoice::class);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @OA\Post(
     *     path="/invoice/xml",
     *     tags={"invoice"},
     *     summary="Generar el XML de una factura o boleta",
     *     description="Retorna el XML",
     *     operationId="getInvoiceXml",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Invoice::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @OA\Post(
     *     path="/invoice/pdf",
     *     tags={"invoice"},
     *     summary="Generar el PDF de una factura o boleta",
     *     description="Retorna el Pdf",
     *     operationId="getInvoicePdf",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Invoice")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Invoice::class);
    }

    /**
     * @Route("/status", methods={"GET"})
     *
     * @OA\Get(
     *     path="/invoice/status",
     *     tags={"invoice"},
     *     summary="Obtiene el estado del CDR de una Factura o Boleta",
     *     description="Retorna el CDR desde SUNAT",
     *     operationId="getInvoiceStatus",
     *     @OA\Parameter(name="tipo", in="query", required=true, description="Tipo de comprobante (Ej. 01, 03)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="serie", in="query", required=true, description="Serie del comprobante (Ej. F001)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="numero", in="query", required=true, description="Número correlativo del comprobante", @OA\Schema(type="string")),
     *     @OA\Parameter(name="ruc", in="query", required=false, description="RUC de empresa (Múltiples Empresas)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/StatusResult"))
     * )
     *
     * @param Request $request
     * @param ConsultCdrServiceFactory $factory
     * @return JsonResponse
     */
    public function status(Request $request, ConsultCdrServiceFactory $factory): JsonResponse
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
        $rucParam = $request->query->get('ruc');
        $see = $factory->build($rucParam);
        $username = $factory->getCredentialUser($rucParam);
        if (empty($username) || strlen($username) < 11) {
            return new JsonResponse(['message' => 'No se encontraron credenciales para el RUC indicado'], 400);
        }
        $ruc = substr($username, 0, 11);
        $result = $see->getStatusCdr($ruc, $tipo, $serie, $numero);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }

        $json = $this->serializer->serialize($result, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}
