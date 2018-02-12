<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:06 PM.
 */

namespace App\Controller\v1;

use Greenter\Model\Response\BillResult;
use Greenter\Model\Sale\Invoice;
use Greenter\See;
use Greenter\Validator\DocumentValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class InvoiceController.
 *
 * @Route("/api/v1/invoice")
 */
class InvoiceController extends AbstractController
{
    /**
     * @var ContextAwareDenormalizerInterface
     */
    private $denormalizer;
    /**
     * @var See
     */
    private $see;
    /**
     * @var DocumentValidatorInterface
     */
    private $validator;

    /**
     * InvoiceController constructor.
     * @param See $see
     * @param DocumentValidatorInterface $validator
     * @param ContextAwareDenormalizerInterface $denormalizer
     */
    public function __construct(
        See $see,
        DocumentValidatorInterface $validator,
        ContextAwareDenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
        $this->see = $see;
        $this->validator = $validator;
    }

    /**
     * @Route("/", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $context = array(ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true);

        $data = $request->getContent();
        $decode = json_decode($data, true);

        /**@var $invoice Invoice */
        $invoice = $this->denormalizer->denormalize(
            $decode,
            Invoice::class, null, $context
        );

        /**@var $errors \Symfony\Component\Validator\ConstraintViolationList */
        $errors = $this->validator->validate($invoice);
        if ($errors->count() !== 0) {
            return $this->json($errors);
        }

        $this->see->setCertificate(file_get_contents(__DIR__.'/../../../tests/Resources/SFSCert.pem'));
        /**@var $result BillResult */
        $result = $this->see->send($invoice);
        if (!$result->isSuccess()) {
            return $this->json($result->getError());
        }

        return $this->json($result->getCdrResponse());
    }
}
