<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Greenter\Model\DocumentInterface;
use Greenter\Report\PdfReport;
use Greenter\Report\ReportInterface;

class PdfReportDecorator implements ReportInterface
{
    /**
     * @var ReportInterface
     */
    private $htmlReport;

    /**
     * @var string
     */
    private $wkhtmlBin;
    /**
     * @var array
     */
    private $options;

    /**
     * PdfReportDecorator constructor.
     *
     * @param ReportInterface $htmlReport
     * @param string $wkhtmlBin
     * @param array $options
     */
    public function __construct(ReportInterface $htmlReport, string $wkhtmlBin, array $options)
    {
        $this->htmlReport = $htmlReport;
        $this->wkhtmlBin = $wkhtmlBin;
        $this->options = $options;
    }

    public function render(DocumentInterface $document, array $parameters = []): ?string
    {
        $reporter = $this->createPdfReport();

        $pdf = $reporter->render($document, $parameters);

        if ($pdf == null) {
            throw new Exception($reporter->getExporter()->getError());
        }

        return $pdf;
    }

    private function createPdfReport(): PdfReport
    {
        $pdfReport = new PdfReport($this->htmlReport);
        $pdfReport->setBinPath($this->wkhtmlBin);
        $pdfReport->setOptions($this->options);

        return $pdfReport;
    }
}