<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 17/02/2018
 * Time: 21:59
 */

namespace App\Service;

use Greenter\Model\DocumentInterface;
use Greenter\Validator\DocumentValidatorInterface;
use Greenter\Validator\SymfonyValidator;

class AppDocumentValidator implements DocumentValidatorInterface
{
    /**
     * @var SymfonyValidator
     */
    private $validator;

    /**
     * AppDocumentValidator constructor.
     * @param SymfonyValidator $validator
     */
    public function __construct(SymfonyValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param DocumentInterface $document
     *
     * @return mixed
     */
    public function validate(DocumentInterface $document): ?object
    {
        /**@var $errors \Symfony\Component\Validator\ConstraintViolationList */
        $errors = $this->validator->validate($document);

        if ($errors->count() === 0) {
            return [];
        }

        $all = [];
        foreach ($errors as $error) {
            $all = [
              'message' => $error->getMessage(),
              'field' => $error->getPropertyPath(),
            ];
        }

        return $all;
    }
}