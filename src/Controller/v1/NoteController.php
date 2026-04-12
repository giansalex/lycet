<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:41
 */

namespace App\Controller\v1;

use App\Service\ConsultCdrServiceFactory;
use App\Service\DocumentRequestInterface;
use Greenter\Model\Sale\Note;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NoteController.
 *
 * @Route("/api/v1/note")
 */
class NoteController extends AbstractController
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
     * NoteController constructor.
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
     * @return Response
     */
    public function send(): Response
    {
        return $this->document->send(Note::class);
    }

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @return Response
     */
    public function xml(): Response
    {
        return $this->document->xml(Note::class);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @return Response
     */
    public function pdf(): Response
    {
        return $this->document->pdf(Note::class);
    }

    /**
     * @Route("/status", methods={"GET"})
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
        if (!in_array($tipo, ['07', '08'], true)) {
            return new JsonResponse(['message' => 'Tipo debe ser 07 (Nota de Crédito) o 08 (Nota de Débito)'], 400);
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
