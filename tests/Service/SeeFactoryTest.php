<?php

namespace App\Tests\Service;

use App\Service\CompanyConfigResolver;
use App\Service\ConfigProviderInterface;
use App\Service\FileDataReader;
use App\Service\SeeFactory;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Retention\Retention;
use Greenter\Model\Sale\Invoice;
use Greenter\See;
use Greenter\Ws\Services\SunatEndpoints;
use PHPUnit\Framework\TestCase;

class SeeFactoryTest extends TestCase
{
    private const RUC = '20000000001';

    private const ENV = [
        'SOL_USER' => '20000000009MODDATOS',
        'SOL_PASS' => 'moddatos',
        'FE_URL' => 'https://env/fe',
        'RE_URL' => 'https://env/re',
        'GUIA_URL' => 'https://env/guia',
    ];

    public function testBuildWithCompanyUsesActiveEnvironment()
    {
        $companies = [self::RUC => [
            'SOL_USER' => self::RUC . 'MODDATOS',
            'SOL_PASS' => 'moddatos',
            'certificate' => 'missing.pem',
            'environment' => 'bizlink-prod',
            'environments' => ['bizlink-prod' => [
                'SOL_USER' => self::RUC . 'OSE',
                'SOL_PASS' => 'ose-pass',
                'FE_URL' => 'https://ose.example/billService',
                'certificate' => 'cert.pem',
            ]],
        ]];

        $see = $this->createMock(See::class);
        $see->expects($this->once())->method('setCredentials')->with(self::RUC . 'OSE', 'ose-pass');
        $see->expects($this->once())->method('setCertificate')->with(file_get_contents(__DIR__ . '/../Resources/cert.pem'));
        $see->expects($this->once())->method('setService')->with('https://ose.example/billService');

        $factory = $this->makeFactory($see, $companies);

        $this->assertSame($see, $factory->build(Invoice::class, self::RUC));
    }

    public function testBuildWithCompanyUsesPresetForProductionRetention()
    {
        $companies = [self::RUC => [
            'SOL_USER' => self::RUC . 'REAL',
            'SOL_PASS' => 'real',
            'certificate' => 'cert.pem',
            'environment' => 'production',
        ]];

        $see = $this->createMock(See::class);
        $see->expects($this->once())->method('setCredentials')->with(self::RUC . 'REAL', 'real');
        $see->expects($this->once())->method('setService')->with(SunatEndpoints::RETENCION_PRODUCCION);

        $this->makeFactory($see, $companies)->build(Retention::class, self::RUC);
    }

    public function testBuildWithUnknownRucFallsBackToEnv()
    {
        $see = $this->createMock(See::class);
        $see->expects($this->once())->method('setCredentials')->with(self::ENV['SOL_USER'], self::ENV['SOL_PASS']);
        $see->expects($this->once())->method('setCertificate')->with('default-cert');
        $see->expects($this->once())->method('setService')->with(self::ENV['GUIA_URL']);

        $this->makeFactory($see, [self::RUC => ['SOL_USER' => 'x']])->build(Despatch::class, '99999999999');
    }

    public function testBuildWithNullRucUsesEnv()
    {
        $see = $this->createMock(See::class);
        $see->expects($this->once())->method('setCredentials')->with(self::ENV['SOL_USER'], self::ENV['SOL_PASS']);
        $see->expects($this->once())->method('setService')->with(self::ENV['FE_URL']);

        $this->makeFactory($see, [])->build(Invoice::class, null);
    }

    private function makeFactory(See $see, array $companies): SeeFactory
    {
        $env = $this->createMock(ConfigProviderInterface::class);
        $env->method('get')->willReturnCallback(function ($key) {
            return self::ENV[$key] ?? false;
        });

        $files = $this->createMock(ConfigProviderInterface::class);
        $files->method('get')->willReturnCallback(function ($key) use ($companies) {
            if ($key === 'companies') {
                return empty($companies) ? '' : json_encode($companies);
            }
            if ($key === 'certificate') {
                return 'default-cert';
            }

            return '';
        });

        $resolver = new CompanyConfigResolver($files, $env, new FileDataReader(__DIR__ . '/../Resources'));

        return new SeeFactory($env, $files, $resolver, $see);
    }
}
