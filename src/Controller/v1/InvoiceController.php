<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:06 PM.
 */

namespace App\Controller\v1;

use Greenter\Model\Company\Company;
use Greenter\Model\Sale\Invoice;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class InvoiceController.
 *
 * @Route("/api/v1/invoice")
 */
class InvoiceController extends AbstractController
{
    /**
     * @Route("/")
     *
     * @return Response
     */
    public function index(): Response
    {
        $inv = new Invoice();
        $inv->setCompany((new Company())->setRuc('20123'));
        $inv->setSerie('F001');

        return $this->json($inv);
    }
}
