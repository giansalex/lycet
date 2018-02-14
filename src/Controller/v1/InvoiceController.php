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
use Greenter\Report\HtmlReport;
use Greenter\Report\ReportInterface;
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
     * @var ReportInterface
     */
    private $report;

    /**
     * InvoiceController constructor.
     * @param See $see
     * @param DocumentValidatorInterface $validator
     * @param ContextAwareDenormalizerInterface $denormalizer
     * @param HtmlReport $report
     */
    public function __construct(
        See $see,
        DocumentValidatorInterface $validator,
        ContextAwareDenormalizerInterface $denormalizer,
        HtmlReport $report)
    {
        $this->denormalizer = $denormalizer;
        $this->see = $see;
        $this->validator = $validator;
        $this->report = $report;
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

    /**
     * @Route("/xml", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function xml(Request $request): Response
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

        $xml = $this->see->getXmlSigned($invoice);

        return new Response($xml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * @Route("/pdf", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     */
    public function pdf(Request $request): Response
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

        $logo = file_get_contents(__DIR__.'/../../../tests/Resources/logo.png');

        $html = $this->report->render($invoice, [
            'system' => [
                'logo' => $logo,
                'hash' => 'xkhakjjuui293/=33w',
            ],
            'user' => [
                'resolucion' => '-',
                'header' => 'Telf: <b>(056) 123375</b>',
                'extras' => [
                    ['name' => 'CONDICION DE PAGO', 'value' => 'Efectivo'],
                    ['name' => 'VENDEDOR', 'value' => 'GITHUB SELLER'],
                ],
            ]
        ]);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }
}
