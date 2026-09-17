<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:50
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use Greenter\Model\Perception\Perception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * Class PerceptionController.
 *
 * @Route("/api/v1/perception")
 */
class PerceptionController extends AbstractController
{
    /**
     * @var DocumentRequestInterface
     */
    private $document;

    /**
     * InvoiceController constructor.
     * @param DocumentRequestInterface $document
     */
    public function __construct(DocumentRequestInterface $document)
    {
        $this->document = $document;
    }

    /**
     * @Route("/send", methods={"POST"})
     *
     * @OA\Post(
     *     path="/perception/send",
     *     tags={"perception"},
     *     summary="Enviar un Comprobante de Percepcion",
     *     description="Retorna la respuesta de SUNAT y el XML firmado",
     *     operationId="sendPerception",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Perception")),
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
        return $this->document->send(Perception::class);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @OA\Post(
     *     path="/perception/xml",
     *     tags={"perception"},
     *     summary="Generar el XML de un Comprobante de Percepcion",
     *     description="Retorna el XML",
     *     operationId="getPerceptionXml",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Perception")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Perception::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @OA\Post(
     *     path="/perception/pdf",
     *     tags={"perception"},
     *     summary="Generar el pdf de un Comprobante de Percepcion",
     *     description="Retorna el pdf",
     *     operationId="getPerceptionPdf",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Perception")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Perception::class);
    }
}