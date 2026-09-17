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
use Greenter\Model\Voided\Voided;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * Class VoidedController.
 *
 * @Route("/api/v1/voided")
 */
class VoidedController extends AbstractController
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
     *     path="/voided/send",
     *     tags={"voided"},
     *     summary="Enviar una Comunicación de Bajas",
     *     description="Retorna la respuesta de SUNAT y el XML firmado",
     *     operationId="sendVoided",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Voided")),
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
        return $this->document->send(Voided::class);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @OA\Post(
     *     path="/voided/xml",
     *     tags={"voided"},
     *     summary="Generar el XML de una Comunicación de Bajas",
     *     description="Retorna el XML",
     *     operationId="getVoidedXml",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Voided")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Voided::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @OA\Post(
     *     path="/voided/pdf",
     *     tags={"voided"},
     *     summary="Generar el PDF de una Comunicación de Bajas",
     *     description="Retorna el PDF",
     *     operationId="getVoidedPdf",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Voided")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Voided::class);
    }

    /**
     * @Route("/status", methods={"GET"})
     *
     * @OA\Get(
     *     path="/voided/status",
     *     tags={"voided"},
     *     summary="Obtiene el estado del ticket",
     *     description="Retorna el XML",
     *     operationId="getVoidedStatus",
     *     @OA\Parameter(name="ticket", in="query", description="Ticket de Comunicación de baja", @OA\Schema(type="string")),
     *     @OA\Parameter(name="ruc", in="query", required=false, description="RUC de empresa (Multiples Empresas)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/StatusResult"))
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request, SeeFactory $factory): JsonResponse
    {
        $ticket = $request->query->get('ticket');
        if (empty($ticket)) {
            return new JsonResponse(['message' => 'Ticket Requerido'], 400);
        }
        $see = $factory->build(Voided::class, $request->query->get('ruc'));
        $result = $see->getStatus($ticket);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }
        $json = $this->serializer->serialize($result, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}