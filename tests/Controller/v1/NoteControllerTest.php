<?php
/**
 * Functional tests for GET /api/v1/note/status
 *
 * Follows the same WebTestCase + container-mock pattern used in InvoiceControllerTest.
 */

namespace App\Tests\Controller\v1;

use App\Service\ConsultCdrServiceFactory;
use Greenter\Model\Response\StatusCdrResult;
use Greenter\Ws\Services\ConsultCdrService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class NoteControllerTest extends WebTestCase
{
    // -----------------------------------------------------------------------
    // Auth guard tests
    // -----------------------------------------------------------------------

    /**
     * Any /api/* request without ?token must be denied.
     */
    public function testStatusWithoutTokenIsDenied(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $client = static::createClient();
        $client->request('GET', '/api/v1/note/status?tipo=07&serie=EC01&numero=1&ruc=20161515648');

        // The exception is thrown before the response is built, but some
        // Symfony versions convert it; accept either 403 or the thrown exception.
        $this->assertContains(
            $client->getResponse()->getStatusCode(),
            [401, 403]
        );
    }

    // -----------------------------------------------------------------------
    // Validation error tests (400)
    // -----------------------------------------------------------------------

    public function testStatusReturnsBadRequestWhenTipoIsMissing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/note/status?token=123456&serie=EC01&numero=1&ruc=20161515648');

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testStatusReturnsBadRequestWhenSerieIsMissing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/note/status?token=123456&tipo=07&numero=1&ruc=20161515648');

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testStatusReturnsBadRequestWhenNumeroIsMissing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/note/status?token=123456&tipo=07&serie=EC01&ruc=20161515648');

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testStatusReturnsBadRequestForInvalidTipoInvoice(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/note/status?token=123456&tipo=01&serie=F001&numero=1&ruc=20161515648');

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertStringContainsString('07', $body['message']);
        $this->assertStringContainsString('08', $body['message']);
    }

    public function testStatusReturnsBadRequestForUnknownRuc(): void
    {
        // Factory mock: getCredentialUser returns '' (unknown RUC)
        $factory = $this->buildFactoryMock('');

        $client = static::createClient();
        $client->getContainer()->set(ConsultCdrServiceFactory::class, $factory);

        $client->request(
            'GET',
            '/api/v1/note/status?token=123456&tipo=07&serie=EC01&numero=1&ruc=99999999999'
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertStringContainsStringIgnoringCase('credencial', $body['message']);
    }

    // -----------------------------------------------------------------------
    // Success tests (200)
    // -----------------------------------------------------------------------

    /**
     * tipo=07 (Nota de Crédito) → 200 with a serialized StatusCdrResult.
     */
    public function testStatusSuccessForNotaCredito(): void
    {
        $result = $this->buildSuccessResult('0161', 'La razon social RUC 20161515648');
        $factory = $this->buildFactoryMockWithResult('20161515648MODDATOS', $result);

        $client = static::createClient();
        $client->getContainer()->set(ConsultCdrServiceFactory::class, $factory);

        $client->request(
            'GET',
            '/api/v1/note/status?token=123456&tipo=07&serie=EC01&numero=1&ruc=20161515648'
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $body = json_decode($response->getContent());
        $this->assertTrue($body->success);
        $this->assertNotEmpty($body->cdrZip);      // base64-encoded CDR
        $this->assertEquals('0161', $body->code);
    }

    /**
     * tipo=08 (Nota de Débito) → 200 with a serialized StatusCdrResult.
     */
    public function testStatusSuccessForNotaDebito(): void
    {
        $result = $this->buildSuccessResult('0162', 'La razon social RUC 20161515648');
        $factory = $this->buildFactoryMockWithResult('20161515648MODDATOS', $result);

        $client = static::createClient();
        $client->getContainer()->set(ConsultCdrServiceFactory::class, $factory);

        $client->request(
            'GET',
            '/api/v1/note/status?token=123456&tipo=08&serie=ED01&numero=1&ruc=20161515648'
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $body = json_decode($response->getContent());
        $this->assertTrue($body->success);
        $this->assertNotEmpty($body->cdrZip);
        $this->assertEquals('0162', $body->code);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a factory mock whose getCredentialUser() returns $credentialUser and
     * build() returns a ConsultCdrService mock that yields $result from getStatusCdr().
     */
    private function buildFactoryMockWithResult(string $credentialUser, StatusCdrResult $result): ConsultCdrServiceFactory
    {
        $service = $this->getMockBuilder(ConsultCdrService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $service->method('getStatusCdr')
            ->willReturn($result);

        /** @var ConsultCdrServiceFactory $factory */
        $factory = $this->getMockBuilder(ConsultCdrServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory->method('build')
            ->willReturn($service);

        $factory->method('getCredentialUser')
            ->willReturn($credentialUser);

        return $factory;
    }

    /**
     * Build a factory mock whose getCredentialUser() returns $credentialUser (no build needed).
     * Used for the "unknown RUC → 400" scenario where the controller bails out before calling build().
     */
    private function buildFactoryMock(string $credentialUser): ConsultCdrServiceFactory
    {
        /** @var ConsultCdrServiceFactory $factory */
        $factory = $this->getMockBuilder(ConsultCdrServiceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory->method('getCredentialUser')
            ->willReturn($credentialUser);

        return $factory;
    }

    /**
     * Build a successful StatusCdrResult with a fake CDR ZIP payload.
     */
    private function buildSuccessResult(string $code, string $message): StatusCdrResult
    {
        $result = new StatusCdrResult();
        $result->setSuccess(true);
        $result->setCode($code);
        $result->setMessage($message);
        // Simulate a binary CDR ZIP (will be base64-encoded by the controller)
        $result->setCdrZip('fake-cdr-zip-content');

        return $result;
    }
}
