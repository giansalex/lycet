<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 02:04 PM.
 */

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @Route("/swagger")
     * @param Request $request
     * @return Response
     */
    public function swagger(Request $request): Response
    {
        $rootUrl = $request->getUriForPath('');
        $path = __DIR__.'/../../public/swagger.yaml';
        $content = file_get_contents($path);
        $content = str_replace('lycet.api', $rootUrl, $content);

        return new Response($content, 200, ['Content-Type' => 'text/yaml']);
    }
}
