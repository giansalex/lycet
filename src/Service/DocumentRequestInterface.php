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
     * Get Result.
     *
     * @param string $class
     * @return Response
     */
    public function send(string $class): Response;

    /**
     * Get Xml.
     *
     * @param string $class
     * @return Response
     */
    public function xml(string $class): Response;

    /**
     * Get Pdf.
     *
     * @param string $class
     * @return Response
     */
    public function pdf(string $class): Response;

    /**
     * Get Configured See.
     *
     * @param string $class
     * @param string $ruc
     * @return See
     */
    public function getSee(string $class, string $ruc): See;

    /**
     * Get parsed document.
     *
     * @param string $class
     * @return DocumentInterface
     */
    public function getDocument(string $class): DocumentInterface;
}
