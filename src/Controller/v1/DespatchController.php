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
        $this->document->setDocumentType(Despatch::class);
        $this->serializer = $serializer;
    }

    /**
     * @Route("/send", methods={"POST"})
     *
     * @return Response
     */
    public function send(SeeApiFactory $factory): Response
    {
        $document = $this->document->getDocument();
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