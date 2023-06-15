<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 18/02/2018
 * Time: 11:57
 */

namespace App\Tests\Controller\v1;

use App\Service\ConfigProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvoiceControllerTest extends WebTestCase
{
    public function testSendAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);
        $client = $this->getClientConfigured();

        $client->request(
            'POST',
            '/api/v1/invoice/send');

        $response = $client->getResponse();

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testSend()
    {
        $data = file_get_contents(__DIR__.'/../../Resources/documents/invoice.json');

        $client = $this->getClientConfigured();

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
        $this->assertEquals('0', $result->sunatResponse->cdrResponse->code);
        $this->assertCount(0, $result->sunatResponse->cdrResponse->notes);
    }

    public function testXml()
    {
        $data = file_get_contents(__DIR__.'/../../Resources/documents/invoice.json');

        $client = $this->getClientConfigured();

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

    /**
     * @return ConfigProviderInterface
     */
    private function getFileConfig()
    {
        $stub = $this->getMockBuilder(ConfigProviderInterface::class)
                    ->getMock();

        $stub->method('get')
            ->willReturnCallback(function ($key) {
               switch ($key) {
                   case 'certificate':
                       $path = __DIR__.'/../../Resources/cert.pem';
                       return file_get_contents($path);
                   default:
                       return '';
               }
            });

        /**@var $stub ConfigProviderInterface*/
        return $stub;
    }

    private function getClientConfigured()
    {
        $client = static::createClient();
        $client->getContainer()->set(ConfigProviderInterface::class, $this->getFileConfig());
        return $client;
    }
}
