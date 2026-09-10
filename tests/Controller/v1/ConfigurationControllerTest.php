<?php

namespace App\Tests\Controller\v1;

use Greenter\Ws\Services\SunatEndpoints;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigurationControllerTest extends WebTestCase
{
    private static $dataPath;

    /**
     * Create a minimal valid 1x1 PNG image.
     */
    private static function createMinimalPng(): string
    {
        $img = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        return $data;
    }

    public static function setUpBeforeClass(): void
    {
        self::$dataPath = __DIR__ . '/../../tmp_data';
        if (!is_dir(self::$dataPath)) {
            mkdir(self::$dataPath, 0755, true);
        }
    }

    public static function tearDownAfterClass(): void
    {
        $files = glob(self::$dataPath . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir(self::$dataPath);
    }

    public function testUpsertCompanyRequiresFields()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/v1/configuration/company/20000000001?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['SOL_USER' => '20000000001MODDATOS'])
        );

        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUpsertCompany()
    {
        $client = static::createClient();

        $certContent = 'fake-certificate-content';

        $client->request(
            'PUT',
            '/api/v1/configuration/company/20000000001?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'SOL_USER' => '20000000001MODDATOS',
                'SOL_PASS' => 'moddatos',
                'certificate' => base64_encode($certContent),
                'FE_URL' => 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService',
            ])
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);
        $this->assertEquals('20000000001', $result['ruc']);

        // Verify certificate file was created
        $this->assertFileExists(self::$dataPath . '/20000000001-cert.pem');
        $this->assertEquals($certContent, file_get_contents(self::$dataPath . '/20000000001-cert.pem'));

        // Verify empresas.json was created
        $this->assertFileExists(self::$dataPath . '/empresas.json');
        $companies = json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
        $this->assertArrayHasKey('20000000001', $companies);
        $this->assertEquals('20000000001MODDATOS', $companies['20000000001']['SOL_USER']);
        $this->assertEquals('https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService', $companies['20000000001']['FE_URL']);
        $this->assertArrayNotHasKey('logo', $companies['20000000001']);
    }

    public function testUploadCompanyLogo()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/v1/configuration/company/20000000001/logo?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['logo' => base64_encode(self::createMinimalPng())])
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // detectImageExtension returns 'png' for PNG images
        $this->assertFileExists(self::$dataPath . '/20000000001-logo.png');

        $companies = json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
        $this->assertEquals('20000000001-logo.png', $companies['20000000001']['logo']);
    }

    public function testUpsertCompanyPreservesLogo()
    {
        $client = static::createClient();

        // Update company credentials — logo should be preserved
        $client->request(
            'PUT',
            '/api/v1/configuration/company/20000000001?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'SOL_USER' => '20000000001NEWUSER',
                'SOL_PASS' => 'newpass',
                'certificate' => base64_encode('updated-cert'),
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $companies = json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
        $this->assertEquals('20000000001NEWUSER', $companies['20000000001']['SOL_USER']);
        $this->assertEquals('20000000001-logo.png', $companies['20000000001']['logo']);
    }

    public function testRemoveCompanyLogo()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/v1/configuration/company/20000000001/logo?token=123456'
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertFileDoesNotExist(self::$dataPath . '/20000000001-logo.png');

        $companies = json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
        $this->assertArrayNotHasKey('logo', $companies['20000000001']);
    }

    public function testUpsertSecondCompany()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/v1/configuration/company/20000000002?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'SOL_USER' => '20000000002MODDATOS',
                'SOL_PASS' => 'moddatos2',
                'certificate' => base64_encode('cert-2'),
            ])
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // Both companies should exist
        $companies = json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
        $this->assertCount(2, $companies);
        $this->assertArrayHasKey('20000000001', $companies);
        $this->assertArrayHasKey('20000000002', $companies);
    }

    public function testRemoveCompany()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/v1/configuration/company/20000000001?token=123456'
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        // Verify removed from empresas.json
        $companies = json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
        $this->assertArrayNotHasKey('20000000001', $companies);
        $this->assertArrayHasKey('20000000002', $companies);

        // Verify files were cleaned up
        $this->assertFileDoesNotExist(self::$dataPath . '/20000000001-cert.pem');
        $this->assertFileDoesNotExist(self::$dataPath . '/20000000001-logo.png');
    }

    public function testRemoveCompanyNotFound()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/v1/configuration/company/99999999999?token=123456'
        );

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    // ---- Environments (issue #655). State here: only company 20000000002 exists. ----

    /** @var \Symfony\Bundle\FrameworkBundle\KernelBrowser|null */
    private $client;

    private function apiRequest(string $method, string $path, ?array $body = null)
    {
        // A test may issue several requests; the kernel can only be booted once per test.
        if ($this->client === null) {
            $this->client = static::createClient();
        }
        $client = $this->client;
        $client->request(
            $method,
            '/api/v1/configuration' . $path . '?token=123456',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $body === null ? null : json_encode($body)
        );

        return $client->getResponse();
    }

    private static function companies(): array
    {
        return json_decode(file_get_contents(self::$dataPath . '/empresas.json'), true);
    }

    public function testGetCompany()
    {
        $response = $this->apiRequest('GET', '/company/20000000002');
        $this->assertEquals(200, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);
        $this->assertEquals('20000000002', $result['ruc']);
        $this->assertNull($result['environment']);
        $this->assertSame([], $result['environments']);
        $this->assertTrue($result['hasCertificate']);
        $this->assertFalse($result['hasLogo']);
        $this->assertEquals('20000000002MODDATOS', $result['SOL_USER']);
        $this->assertEquals($_ENV['FE_URL'], $result['FE_URL']);
        $this->assertArrayNotHasKey('SOL_PASS', $result);
        $this->assertArrayNotHasKey('CLIENT_SECRET', $result);
        $this->assertArrayNotHasKey('certificate', $result);
    }

    public function testGetCompanyNotFound()
    {
        $this->assertEquals(404, $this->apiRequest('GET', '/company/99999999999')->getStatusCode());
    }

    public function testUpsertEnvironmentInvalidName()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environments/bad%20name', ['FE_URL' => 'https://x']);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUpsertEnvironmentCompanyNotFound()
    {
        $response = $this->apiRequest('PUT', '/company/99999999999/environments/prod', ['FE_URL' => 'https://x']);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUpsertEnvironmentRequiresValues()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environments/bizlink-prod', []);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUpsertEnvironment()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environments/bizlink-prod', [
            'SOL_USER' => '20000000002OSE',
            'SOL_PASS' => 'ose-pass',
            'certificate' => base64_encode('env-cert'),
            'FE_URL' => 'https://ose.bizlinks.com/billService',
            'CLIENT_ID' => 'ose-id',
            'CLIENT_SECRET' => 'ose-secret',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bizlink-prod', json_decode($response->getContent(), true)['environment']);

        $this->assertFileExists(self::$dataPath . '/20000000002-bizlink-prod-cert.pem');
        $this->assertEquals('env-cert', file_get_contents(self::$dataPath . '/20000000002-bizlink-prod-cert.pem'));

        $company = self::companies()['20000000002'];
        $block = $company['environments']['bizlink-prod'];
        $this->assertEquals('20000000002-bizlink-prod-cert.pem', $block['certificate']);
        $this->assertEquals('https://ose.bizlinks.com/billService', $block['FE_URL']);
        $this->assertEquals('20000000002OSE', $block['SOL_USER']);
        $this->assertEquals('20000000002MODDATOS', $company['SOL_USER']);
        $this->assertArrayNotHasKey('environment', $company);
    }

    public function testUpsertEnvironmentWithoutCertificate()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environments/production', [
            'SOL_USER' => '20000000002REAL',
            'SOL_PASS' => 'real',
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFileDoesNotExist(self::$dataPath . '/20000000002-production-cert.pem');

        $block = self::companies()['20000000002']['environments']['production'];
        $this->assertArrayNotHasKey('certificate', $block);
        $this->assertEquals('20000000002REAL', $block['SOL_USER']);
    }

    public function testSetActiveEnvironmentRequiresName()
    {
        $this->assertEquals(400, $this->apiRequest('PUT', '/company/20000000002/environment', [])->getStatusCode());
    }

    public function testSetActiveEnvironmentUnknown()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environment', ['environment' => 'ghost']);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testSetActiveEnvironmentCompanyNotFound()
    {
        $response = $this->apiRequest('PUT', '/company/99999999999/environment', ['environment' => 'beta']);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSetActiveEnvironmentPresetWithoutBlock()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environment', ['environment' => 'beta']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('beta', self::companies()['20000000002']['environment']);
    }

    public function testGetCompanyUsesSunatPresets()
    {
        $result = json_decode($this->apiRequest('GET', '/company/20000000002')->getContent(), true);

        $this->assertEquals('beta', $result['environment']);
        $this->assertEquals(SunatEndpoints::FE_BETA, $result['FE_URL']);
        $this->assertEquals(SunatEndpoints::RETENCION_BETA, $result['RE_URL']);
        $this->assertEquals(SunatEndpoints::GUIA_BETA, $result['GUIA_URL']);
        $this->assertEquals('20000000002MODDATOS', $result['SOL_USER']);
    }

    public function testSetActiveEnvironment()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environment', ['environment' => 'bizlink-prod']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('bizlink-prod', self::companies()['20000000002']['environment']);
    }

    public function testGetCompanyResolvesActiveEnvironment()
    {
        $result = json_decode($this->apiRequest('GET', '/company/20000000002')->getContent(), true);

        $this->assertEquals('bizlink-prod', $result['environment']);
        $this->assertEqualsCanonicalizing(['bizlink-prod', 'production'], $result['environments']);
        $this->assertEquals('20000000002OSE', $result['SOL_USER']);
        $this->assertEquals('https://ose.bizlinks.com/billService', $result['FE_URL']);
        $this->assertEquals('ose-id', $result['CLIENT_ID']);
        $this->assertEquals($_ENV['RE_URL'], $result['RE_URL']);
        $this->assertTrue($result['hasCertificate']);
        $this->assertArrayNotHasKey('SOL_PASS', $result);
        $this->assertArrayNotHasKey('CLIENT_SECRET', $result);
    }

    public function testUpsertCompanyPreservesEnvironments()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002', [
            'SOL_USER' => '20000000002NEWUSER',
            'SOL_PASS' => 'newpass',
            'certificate' => base64_encode('updated-cert-2'),
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $company = self::companies()['20000000002'];
        $this->assertEquals('20000000002NEWUSER', $company['SOL_USER']);
        $this->assertEquals('bizlink-prod', $company['environment']);
        $this->assertArrayHasKey('bizlink-prod', $company['environments']);
        $this->assertArrayHasKey('production', $company['environments']);
        $this->assertFileExists(self::$dataPath . '/20000000002-bizlink-prod-cert.pem');
    }

    public function testUpsertCompanyRejectsUnknownEnvironment()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002', [
            'SOL_USER' => '20000000002NEWUSER',
            'SOL_PASS' => 'newpass',
            'certificate' => base64_encode('updated-cert-2'),
            'environment' => 'ghost',
        ]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUpsertCompanyWithEnvironmentsInBody()
    {
        $response = $this->apiRequest('PUT', '/company/20000000003', [
            'SOL_USER' => '20000000003MODDATOS',
            'SOL_PASS' => 'moddatos',
            'certificate' => base64_encode('cert-3'),
            'environment' => 'production',
            'environments' => [
                'production' => [
                    'SOL_USER' => '20000000003REAL',
                    'SOL_PASS' => 'real',
                    'certificate' => base64_encode('cert-3-prod'),
                ],
            ],
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        $company = self::companies()['20000000003'];
        $this->assertEquals('production', $company['environment']);
        $this->assertEquals('20000000003-production-cert.pem', $company['environments']['production']['certificate']);
        $this->assertEquals('cert-3-prod', file_get_contents(self::$dataPath . '/20000000003-production-cert.pem'));

        $result = json_decode($this->apiRequest('GET', '/company/20000000003')->getContent(), true);
        $this->assertEquals('20000000003REAL', $result['SOL_USER']);
        $this->assertEquals(SunatEndpoints::FE_PRODUCCION, $result['FE_URL']);

        // Replacing environments with an empty map drops the block, its cert and the active environment
        $response = $this->apiRequest('PUT', '/company/20000000003', [
            'SOL_USER' => '20000000003MODDATOS',
            'SOL_PASS' => 'moddatos',
            'certificate' => base64_encode('cert-3'),
            'environments' => [],
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $company = self::companies()['20000000003'];
        $this->assertArrayNotHasKey('environments', $company);
        $this->assertEquals('production', $company['environment']); // preset names stay valid without a block
        $this->assertFileDoesNotExist(self::$dataPath . '/20000000003-production-cert.pem');

        $this->assertEquals(200, $this->apiRequest('DELETE', '/company/20000000003')->getStatusCode());
    }

    public function testRemoveEnvironmentNotFound()
    {
        $this->assertEquals(404, $this->apiRequest('DELETE', '/company/20000000002/environments/ghost')->getStatusCode());
        $this->assertEquals(404, $this->apiRequest('DELETE', '/company/99999999999/environments/production')->getStatusCode());
    }

    public function testRemoveActiveEnvironment()
    {
        $response = $this->apiRequest('DELETE', '/company/20000000002/environments/bizlink-prod');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertFileDoesNotExist(self::$dataPath . '/20000000002-bizlink-prod-cert.pem');
        $this->assertFileExists(self::$dataPath . '/20000000002-cert.pem');

        $company = self::companies()['20000000002'];
        $this->assertArrayNotHasKey('environment', $company);
        $this->assertSame(['production'], array_keys($company['environments']));
    }

    public function testRemoveCompanyCleansEnvironmentCerts()
    {
        $response = $this->apiRequest('PUT', '/company/20000000002/environments/production', [
            'certificate' => base64_encode('prod-cert'),
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFileExists(self::$dataPath . '/20000000002-production-cert.pem');

        $this->assertEquals(200, $this->apiRequest('DELETE', '/company/20000000002')->getStatusCode());

        $this->assertFileDoesNotExist(self::$dataPath . '/20000000002-cert.pem');
        $this->assertFileDoesNotExist(self::$dataPath . '/20000000002-production-cert.pem');
        $this->assertArrayNotHasKey('20000000002', self::companies());
    }
}
