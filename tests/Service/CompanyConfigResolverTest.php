<?php

namespace App\Tests\Service;

use App\Service\CompanyConfigResolver;
use App\Service\ConfigProviderInterface;
use App\Service\FileDataReader;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Perception\Perception;
use Greenter\Model\Retention\Retention;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\Voided;
use Greenter\Ws\Services\SunatEndpoints;
use PHPUnit\Framework\TestCase;

class CompanyConfigResolverTest extends TestCase
{
    private const RUC = '20000000001';

    public function testGetCompanyReturnsNullWhenCompaniesFileIsEmpty()
    {
        $resolver = $this->makeResolver('');

        $this->assertSame([], $resolver->getCompanies());
        $this->assertNull($resolver->getCompany(self::RUC));
    }

    public function testGetCompanyReturnsNullForInvalidJson()
    {
        $resolver = $this->makeResolver('{not json');

        $this->assertNull($resolver->getCompany(self::RUC));
    }

    public function testGetCompanyReturnsNullForUnknownRuc()
    {
        $resolver = $this->makeResolver($this->companies(['SOL_USER' => 'x']));

        $this->assertNull($resolver->getCompany('99999999999'));
    }

    public function testGetCompanyReturnsCompanyConfig()
    {
        $resolver = $this->makeResolver($this->companies(['SOL_USER' => 'x']));

        $this->assertSame(['SOL_USER' => 'x'], $resolver->getCompany(self::RUC));
    }

    public function testGetActiveEnvironmentReturnsNullWhenUnsetOrNotString()
    {
        $resolver = $this->makeResolver('');

        $this->assertNull($resolver->getActiveEnvironment([]));
        $this->assertNull($resolver->getActiveEnvironment(['environment' => '']));
        $this->assertNull($resolver->getActiveEnvironment(['environment' => 123]));
        $this->assertSame('beta', $resolver->getActiveEnvironment(['environment' => 'beta']));
    }

    public function testGetEnvironmentNames()
    {
        $resolver = $this->makeResolver('');

        $this->assertSame([], $resolver->getEnvironmentNames([]));
        $this->assertSame(
            ['beta', 'bizlink-prod'],
            $resolver->getEnvironmentNames(['environments' => ['beta' => [], 'bizlink-prod' => []]])
        );
    }

    public function testResolvePrefersActiveEnvironmentValue()
    {
        $company = [
            'SOL_USER' => 'ROOT',
            'environment' => 'bizlink-prod',
            'environments' => ['bizlink-prod' => ['SOL_USER' => 'ENV']],
        ];
        $resolver = $this->makeResolver('');

        $this->assertSame('ENV', $resolver->resolve($company, 'SOL_USER'));
    }

    public function testResolveFallsBackToRootWhenKeyMissingInEnvironment()
    {
        $company = [
            'SOL_USER' => 'ROOT',
            'environment' => 'bizlink-prod',
            'environments' => ['bizlink-prod' => ['FE_URL' => 'https://ose']],
        ];
        $resolver = $this->makeResolver('');

        $this->assertSame('ROOT', $resolver->resolve($company, 'SOL_USER'));
    }

    public function testResolveIgnoresActiveEnvironmentWithoutBlock()
    {
        $company = ['SOL_USER' => 'ROOT', 'environment' => 'custom'];
        $resolver = $this->makeResolver('', ['SOL_USER' => 'ENVFILE']);

        $this->assertSame('ROOT', $resolver->resolve($company, 'SOL_USER'));
    }

    /**
     * @dataProvider presetProvider
     */
    public function testResolveUsesSunatPresetsForUrlKeys(string $environment, string $key, string $expected)
    {
        $env = $this->createMock(ConfigProviderInterface::class);
        $env->expects($this->never())->method('get');
        $resolver = new CompanyConfigResolver($this->fileProviderMock(''), $env, $this->fileReader());

        $this->assertSame($expected, $resolver->resolve(['environment' => $environment], $key));
    }

    public function presetProvider(): array
    {
        return [
            ['beta', 'FE_URL', SunatEndpoints::FE_BETA],
            ['beta', 'RE_URL', SunatEndpoints::RETENCION_BETA],
            ['beta', 'GUIA_URL', SunatEndpoints::GUIA_BETA],
            ['production', 'FE_URL', SunatEndpoints::FE_PRODUCCION],
            ['production', 'RE_URL', SunatEndpoints::RETENCION_PRODUCCION],
            ['production', 'GUIA_URL', SunatEndpoints::GUIA_PRODUCCION],
        ];
    }

    public function testResolveRootUrlOverridesPreset()
    {
        $company = ['FE_URL' => 'https://root', 'environment' => 'production'];
        $resolver = $this->makeResolver('');

        $this->assertSame('https://root', $resolver->resolve($company, 'FE_URL'));
    }

    public function testResolvePresetIgnoredForNonUrlKeys()
    {
        $resolver = $this->makeResolver('', ['SOL_USER' => 'ENVFILE']);

        $this->assertSame('ENVFILE', $resolver->resolve(['environment' => 'beta'], 'SOL_USER'));
    }

    public function testResolvePresetIgnoredForCustomEnvironmentName()
    {
        $company = [
            'environment' => 'bizlink-prod',
            'environments' => ['bizlink-prod' => ['SOL_USER' => 'x']],
        ];
        $resolver = $this->makeResolver('', ['FE_URL' => 'https://env']);

        $this->assertSame('https://env', $resolver->resolve($company, 'FE_URL'));
    }

    public function testResolveFallsBackToEnvProvider()
    {
        $resolver = $this->makeResolver('', ['CLIENT_ID' => 'env-id']);

        $this->assertSame('env-id', $resolver->resolve(['SOL_USER' => 'x'], 'CLIENT_ID'));
    }

    public function testResolveReturnsNullWhenEnvProviderHasNoValue()
    {
        $resolver = $this->makeResolver('');

        $this->assertNull($resolver->resolve(['SOL_USER' => 'x'], 'CLIENT_ID'));
    }

    public function testResolveTreatsEmptyStringAsUnset()
    {
        $company = [
            'FE_URL' => 'https://root',
            'environment' => 'custom',
            'environments' => ['custom' => ['FE_URL' => '']],
        ];
        $resolver = $this->makeResolver('');

        $this->assertSame('https://root', $resolver->resolve($company, 'FE_URL'));
    }

    public function testResolveNeverUsesEnvProviderForFileKeys()
    {
        $env = $this->createMock(ConfigProviderInterface::class);
        $env->expects($this->never())->method('get');
        $resolver = new CompanyConfigResolver($this->fileProviderMock(''), $env, $this->fileReader());

        $this->assertNull($resolver->resolve(['SOL_USER' => 'x'], 'logo'));
        $this->assertNull($resolver->resolve(['SOL_USER' => 'x'], 'certificate'));
    }

    public function testResolveCertificateReadsEnvironmentFile()
    {
        $company = [
            'certificate' => 'missing-root.pem',
            'environment' => 'production',
            'environments' => ['production' => ['certificate' => 'cert.pem']],
        ];
        $resolver = $this->makeResolver('');

        $this->assertSame(file_get_contents(__DIR__ . '/../Resources/cert.pem'), $resolver->resolveCertificate($company));
    }

    public function testResolveCertificateFallsBackToDefaultCertificate()
    {
        $resolver = $this->makeResolver('', [], 'default-cert');

        $this->assertSame('default-cert', $resolver->resolveCertificate(['SOL_USER' => 'x']));
    }

    /**
     * @dataProvider urlKeyProvider
     */
    public function testUrlKeyForClass(string $class, string $expected)
    {
        $this->assertSame($expected, CompanyConfigResolver::urlKeyForClass($class));
    }

    public function urlKeyProvider(): array
    {
        return [
            [Invoice::class, 'FE_URL'],
            [Note::class, 'FE_URL'],
            [Summary::class, 'FE_URL'],
            [Voided::class, 'FE_URL'],
            [Perception::class, 'RE_URL'],
            [Retention::class, 'RE_URL'],
            [Reversion::class, 'RE_URL'],
            [Despatch::class, 'GUIA_URL'],
        ];
    }

    public function testIsPresetEnvironment()
    {
        $this->assertTrue(CompanyConfigResolver::isPresetEnvironment('beta'));
        $this->assertTrue(CompanyConfigResolver::isPresetEnvironment('production'));
        $this->assertFalse(CompanyConfigResolver::isPresetEnvironment('Beta'));
        $this->assertFalse(CompanyConfigResolver::isPresetEnvironment('prod'));
    }

    private function companies(array $company): string
    {
        return json_encode([self::RUC => $company]);
    }

    private function makeResolver(string $companiesJson, array $env = [], string $defaultCertificate = ''): CompanyConfigResolver
    {
        $envProvider = $this->createMock(ConfigProviderInterface::class);
        $envProvider->method('get')->willReturnCallback(function ($key) use ($env) {
            return $env[$key] ?? false;
        });

        return new CompanyConfigResolver($this->fileProviderMock($companiesJson, $defaultCertificate), $envProvider, $this->fileReader());
    }

    private function fileProviderMock(string $companiesJson, string $defaultCertificate = ''): ConfigProviderInterface
    {
        $files = $this->createMock(ConfigProviderInterface::class);
        $files->method('get')->willReturnCallback(function ($key) use ($companiesJson, $defaultCertificate) {
            if ($key === 'companies') {
                return $companiesJson;
            }
            if ($key === 'certificate') {
                return $defaultCertificate;
            }

            return '';
        });

        return $files;
    }

    private function fileReader(): FileDataReader
    {
        return new FileDataReader(__DIR__ . '/../Resources');
    }
}
