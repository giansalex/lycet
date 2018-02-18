<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 18/02/2018
 * Time: 11:57
 */

namespace App\Tests\Controller\v1;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InvoiceControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $path = __DIR__.'/../../Resources/SFSCert.pem';
        putenv('CERT_PATH='.$path);
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testSendAccessDenied()
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/v1/invoice/send');

        $response = $client->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testSend()
    {
        $data = file_get_contents(__DIR__.'/../../Resources/documents/invoice.json');

        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/invoice/send?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $data);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getContent());
        $this->assertNotEmpty($result->xml);
        $this->assertNotEmpty($result->hash);
        $this->assertNotNull($result->sunatResponse);
        $this->assertTrue($result->sunatResponse->success);
    }

    public function testXml()
    {
        $data = file_get_contents(__DIR__.'/../../Resources/documents/invoice.json');

        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/invoice/xml?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $data);

        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $result = $response->getContent();
        $doc = new \DOMDocument();
        $doc->loadXML($result);
        $this->assertEquals('Invoice', $doc->documentElement->nodeName);
    }
}