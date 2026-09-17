<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:41
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use App\Service\SeeFactory;
use Greenter\Model\Summary\Summary;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * Class SummaryController.
 *
 * @Route("/api/v1/summary")
 */
class SummaryController extends AbstractController
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
     *     path="/summary/send",
     *     tags={"summary"},
     *     summary="Enviar una Resumen diario de Boletas",
     *     description="Retorna la respuesta de SUNAT y el XML firmado",
     *     operationId="sendSummary",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Summary")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/SummaryResponse")),
     *     @OA\Response(response=400, description="Error de Validación",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ValidationResponse")))
     * )
     *
     * @return Response
     */
    public function send(): Response
    {
        return $this->document->send(Summary::class);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @OA\Post(
     *     path="/summary/xml",
     *     tags={"summary"},
     *     summary="Generar el XML de un resumen diario",
     *     description="Retorna el XML",
     *     operationId="getSummaryXml",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Summary")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Summary::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @OA\Post(
     *     path="/summary/pdf",
     *     tags={"summary"},
     *     summary="Generar el PDF de un resumen diario",
     *     description="Retorna el PDF",
     *     operationId="getSummaryPdf",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Summary")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Summary::class);
    }

    /**
     * @Route("/status", methods={"GET"})
     *
     * @OA\Get(
     *     path="/summary/status",
     *     tags={"summary"},
     *     summary="Obtiene el estado del ticket",
     *     description="Retorna el XML",
     *     operationId="getSummaryStatus",
     *     @OA\Parameter(name="ticket", in="query", description="Ticket de Resumen diario", @OA\Schema(type="string")),
     *     @OA\Parameter(name="ruc", in="query", required=false, description="RUC de empresa (Multiples Empresas)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/StatusResult"))
     * )
     *
     * @param Request $request
     * @param SeeFactory $factory
     * @return JsonResponse
     */
    public function status(Request $request, SeeFactory $factory): JsonResponse
    {
        $ticket = $request->query->get('ticket');
        if (empty($ticket)) {
            return new JsonResponse(['message' => 'Ticket Requerido'], 400);
        }
        $see = $factory->build(Summary::class, $request->query->get('ruc'));
        $result = $see->getStatus($ticket);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }

        $json = $this->serializer->serialize($result, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}