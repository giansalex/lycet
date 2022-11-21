<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 13/02/2018
 * Time: 22:16
 */

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Interface RequestParserInterface
 */
interface RequestParserInterface
{
    /**
     * @param Request $request
     * @param string $class
     * @return mixed
     */
    function getObject(Request $request, string $class);

    /**
     * @param Request $request
     * @param string $key
     * @return mixed
     */
    function getKey(Request $request, string $key);
}