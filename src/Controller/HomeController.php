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
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $pathDocs = $request->getUriForPath('/docs');

        $content = $this->getWithReplace(
            __DIR__ . '/../../views/welcome.html',
            'lycet.api',
            $pathDocs
        );

        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * @Route("/docs")
     * @param Request $request
     * @return Response
     */
    public function docs(Request $request): Response
    {
        $swaggerUrl = $request->getUriForPath('/swagger');

        $content = <<<HTML
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Lycet API Reference</title>
            </head>
            <body>
                <div id="app"></div>

                <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
                <script>
                    Scalar.createApiReference('#app', {
                        url: '{$swaggerUrl}',
                        theme: 'default'
                    });
                </script>
            </body>
            </html>
        HTML;

        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * @Route("/swagger")
     * @param Request $request
     * @return Response
     */
    public function swagger(Request $request): Response
    {
        $rootUrl = $request->getHttpHost() . $request->getBasePath();

        $content = $this->getWithReplace(
            __DIR__ . '/../../public/swagger.yaml',
            'lycet.api',
            $rootUrl
        );

        return new Response($content, 200, ['Content-Type' => 'text/yaml']);
    }

    private function getWithReplace($path, $oldValue, $newValue)
    {
        $content = file_get_contents($path);

        return str_replace($oldValue, $newValue, $content);
    }
}
