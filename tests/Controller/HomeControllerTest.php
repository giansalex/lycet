<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 23/01/2018
 * Time: 03:48 PM
 */

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testSwagger()
    {
        $client = static::createClient();
        $client->request('GET', '/swagger');

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/yaml', $response->headers->get('Content-Type'));
    }
}