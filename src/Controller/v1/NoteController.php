<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 23:41
 */

namespace App\Controller\v1;

use App\Service\DocumentRequestInterface;
use Greenter\Model\Sale\Note;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}