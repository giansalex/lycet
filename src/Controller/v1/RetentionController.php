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
        $this->document->setDocumentType(Retention::class);
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
}