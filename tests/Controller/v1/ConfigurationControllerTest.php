<?php

namespace App\Tests\Controller\v1;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfigurationControllerTest extends WebTestCase
{
    private static $dataPath;

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
            json_encode(['logo' => base64_encode('fake-logo-content')])
        );

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

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
}
