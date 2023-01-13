<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 21:42
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use Greenter\See;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface DocumentRequestInterface
 */
interface DocumentRequestInterface
{
    /**
     * Set document to process.
     *
     * @param string $class
     */
    public function setDocumentType(string $class);

    /**
     * Get Result.
     *
     * @return Response
     */
    public function send(): Response;

    /**
     * Get Xml.
     *
     * @return Response
     */
    public function xml(): Response;

    /**
     * Get Pdf.
     *
     * @return Response
     */
    public function pdf(): Response;

    /**
     * Get Configured See.
     *
     * @param string $ruc
     * @return See
     */
    public function getSee(string $ruc): See;

    /**
     * Get parsed document.
     *
     * @return DocumentInterface
     */
    public function getDocument(): DocumentInterface;
}
