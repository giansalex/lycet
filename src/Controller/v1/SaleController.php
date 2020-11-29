<?php
/**
 * Created by PhpStorm.
 * User: Soporte
 * Date: 28/02/2019
 * Time: 10:32
 */

namespace App\Controller\v1;

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Sale\BaseSale;
use Greenter\Report\Render\QrRender;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SaleController
 * @Route("/api/v1/sale")
 */
class SaleController extends AbstractController
{
    /**
     * @var QrRender
     */
    private $render;

    /**
     * SaleController constructor.
     * @param QrRender $render
     */
    public function __construct(QrRender $render)
    {
        $this->render = $render;
    }

    /**
     * @Route("/qr", methods={"POST"})
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function qr(Request $request): Response
    {
        $obj = json_decode($request->getContent());
        $sale = new BaseSale();
        $sale
            ->setCompany((new Company())->setRuc($obj->ruc))
            ->setTipoDoc($obj->tipo)
            ->setSerie($obj->serie)
            ->setCorrelativo($obj->numero)
            ->setMtoIGV($obj->igv)
            ->setMtoImpVenta($obj->total)
            ->setFechaEmision(new \DateTime($obj->emision))
            ->setClient(
                (new Client())
                ->setTipoDoc($obj->clienteTipo)
                ->setNumDoc($obj->clienteNumero)
            );

        $qr = $this->render->getImage($sale);

        return new Response($qr, 200, ['Content-Type' => 'image/svg+xml']);
    }
}