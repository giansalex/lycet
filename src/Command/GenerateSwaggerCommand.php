<?php

namespace App\Command;

use OpenApi\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genera public/swagger.yaml a partir de las anotaciones @OA\ presentes en src/.
 *
 * Uso:
 *   php bin/console app:swagger:generate
 *
 * De esta forma la especificación OpenAPI se mantiene sincronizada con el
 * código: al agregar o modificar un endpoint solo hay que actualizar sus
 * anotaciones y volver a ejecutar este comando.
 */
class GenerateSwaggerCommand extends Command
{
    protected static $defaultName = 'app:swagger:generate';
    protected static $defaultDescription = 'Genera public/swagger.yaml desde las anotaciones OpenAPI del código.';

    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Ruta del archivo de salida',
                'public/swagger.yaml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $scanDir = $this->projectDir . DIRECTORY_SEPARATOR . 'src';
        $outputOption = $input->getOption('output');
        $outputPath = $this->isAbsolute($outputOption)
            ? $outputOption
            : $this->projectDir . DIRECTORY_SEPARATOR . $outputOption;

        $io->title('Generando especificación OpenAPI');
        $io->text('Escaneando anotaciones en: ' . $scanDir);

        $openapi = Generator::scan([$scanDir]);

        $yaml = $openapi->toYaml();
        $bytes = file_put_contents($outputPath, $yaml);

        if ($bytes === false) {
            $io->error('No se pudo escribir el archivo: ' . $outputPath);

            return Command::FAILURE;
        }

        $io->success(sprintf('swagger.yaml generado (%d bytes) en %s', $bytes, $outputPath));

        return Command::SUCCESS;
    }

    private function isAbsolute(string $path): bool
    {
        return $path !== ''
            && ($path[0] === '/' || $path[0] === '\\' || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1);
    }
}
