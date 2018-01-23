<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:04 PM.
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class HomeController.
 */
class HomeController
{
    /**
     * @Route("/")
     */
    public function index(): Response
    {
        return new Response('<h1>LYCET</h1><p>A REST API based on Greenter</p>');
    }
}
