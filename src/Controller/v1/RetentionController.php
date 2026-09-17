<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:50
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use Greenter\Model\Retention\Retention;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * Class RetentionController.
 *
 * @Route("/api/v1/retention")
 */
class RetentionController extends AbstractController
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
     *     path="/retention/send",
     *     tags={"retention"},
     *     summary="Enviar un Comprobante de Retencion",
     *     description="Retorna la respuesta de SUNAT y el XML firmado",
     *     operationId="sendRetention",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Retention")),
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
        return $this->document->send(Retention::class);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @OA\Post(
     *     path="/retention/xml",
     *     tags={"retention"},
     *     summary="Generar el XML de un Comprobante de Retencion",
     *     description="Retorna el XML",
     *     operationId="getRetentionXml",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Retention")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Retention::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @OA\Post(
     *     path="/retention/pdf",
     *     tags={"retention"},
     *     summary="Generar el pdf de un Comprobante de Retencion",
     *     description="Retorna el pdf",
     *     operationId="getRetentionPdf",
     *     @OA\RequestBody(required=true, description="Comprobante",
     *         @OA\JsonContent(ref="#/components/schemas/Retention")),
     *     @OA\Response(response=200, description="Operacion exitosa"),
     *     @OA\Response(response=400, description="Error de Validación")
     * )
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Retention::class);
    }
}