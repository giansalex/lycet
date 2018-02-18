<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:51
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use Greenter\Model\Voided\Reversion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ReversionController.
 *
 * @Route("/api/v1/reversion")
 */
class ReversionController extends AbstractController
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
        $this->document->setDocumentType(Reversion::class);
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
     * @return Response
     */
    public function status(Request $request): Response
    {
        $ticket = $request->query->get('ticket');
        if (empty($ticket)) {
            return new Response('Ticket Requerido', 400);
        }
        $see = $this->document->getSee();
        $result = $see->getStatus($ticket);

        if ($result->isSuccess()) {
            $result->setCdrZip(base64_encode($result->getCdrZip()));
        }

        return $this->json($result);
    }
}