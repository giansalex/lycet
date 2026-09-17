<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:50
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use App\Service\SeeApiFactory;
use Greenter\Model\Despatch\Despatch;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * Class DespatchController.
 *
 * @Route("/api/v1/despatch")
 */
class DespatchController extends AbstractController
{
    private DocumentRequestInterface $document;
    private SerializerInterface $serializer;

    /**
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
     *     path="/despatch/send",
     *     tags={"despatch"},
     *     summary="Enviar una Guía de Remisión",
     *     description="Retorna la respuesta de SUNAT y el XML firmado",
     *     operationId="sendDespatch",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Despatch")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/SummaryResponse")),
     *     @OA\Response(response=400, description="Error de Validación",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ValidationResponse")))
     * )
     *
     * @return Response
     */
    public function send(SeeApiFactory $factory): Response
    {
        /** @var \Greenter\Model\Despatch\Despatch $document */
        $document = $this->document->getDocument(Despatch::class);
        $see = $factory->build($document->getCompany()->getRuc());
        $result = $see->send($document);

        $xml = $see->getLastXml();

        $data = [
            'xml' => $xml,
            'sunatResponse' => $result
        ];

        $json = $this->serializer->serialize($data, 'json');

        return new JsonResponse($json, 200, [], true);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @OA\Post(
     *     path="/despatch/xml",
     *     tags={"despatch"},
     *     summary="Generar el XML de una Guía de Remisión",
     *     description="Retorna el XML",
     *     operationId="getDespatchXml",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Despatch")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Despatch::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @OA\Post(
     *     path="/despatch/pdf",
     *     tags={"despatch"},
     *     summary="Generar el Pdf de una Guía de Remisión",
     *     description="Retorna el Pdf",
     *     operationId="getDespatchPdf",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Despatch")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Despatch::class);
    }

    /**
     * @Route("/status", methods={"GET"})
     *
     * @OA\Get(
     *     path="/despatch/status",
     *     tags={"despatch"},
     *     summary="Obtiene el estado del ticket",
     *     description="Retorna el CDR",
     *     operationId="getDespatchStatus",
     *     @OA\Parameter(name="ticket", in="query", description="Ticket del comprobante", @OA\Schema(type="string")),
     *     @OA\Parameter(name="ruc", in="query", required=false, description="RUC de empresa (Multiples Empresas)", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Operacion exitosa",
     *         @OA\JsonContent(ref="#/components/schemas/StatusResult"))
     * )
     *
     * @param Request $request
     * @param SeeApiFactory $factory
     * @return JsonResponse
     */
    public function status(Request $request, SeeApiFactory $factory): JsonResponse
    {
        $ticket = $request->query->get('ticket');
        if (empty($ticket)) {
            return new JsonResponse(['message' => 'Ticket Requerido'], 400);
        }
        $see = $factory->build($request->query->get('ruc'));
        $result = $see->getStatus($ticket);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }

        $json = $this->serializer->serialize($result, 'json');

        return new JsonResponse($json, 200, [], true);
    }
}