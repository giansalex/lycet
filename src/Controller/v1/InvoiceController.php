<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:06 PM.
 */

namespace App\Controller\v1;

use Greenter\Model\Company\Company;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Sale\Invoice;
use Greenter\See;
use Greenter\Services\SenderInterface;
use Greenter\Validator\DocumentValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class InvoiceController.
 *
 * @Route("/api/v1/invoice")
 */
class InvoiceController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
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
     * @param SerializerInterface $serializer
     */
    public function __construct(
        See $see,
        DocumentValidatorInterface $validator,
        SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
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
        /**@var $serialzier Serializer */
        $serialzier = $this->serializer;
        $context = array(ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true);

        $data = $request->getContent();
        $decode = json_decode($data, true);

        /**@var $obj Invoice */
        $obj = $serialzier->denormalize(
            $decode,
            Invoice::class, null, $context
        );
//
//        return $this->json($obj);
        /**@var $invoice Invoice*/
        $invoice = $serialzier->deserialize($data, Invoice::class, 'json');
        /**@var $errors \Symfony\Component\Validator\ConstraintViolationListInterface*/
        $errors = $this->validator->validate($invoice);
        if ($errors->count() !== 0) {
            return $this->json($errors);
        }
        return new Response('sad');
//        return new Response($invoice->getCompany()->getRuc());
        $this->see->setCertificate(file_get_contents(__DIR__.'/../../../tests/Resources/SFSCert.pem'));
        /**@var $result BillResult */
        $result = $this->see->send($invoice);
        if (!$result->isSuccess()) {
            return $this->json($result->getError());
        }

        return $this->json($result->getCdrResponse());
    }
}
