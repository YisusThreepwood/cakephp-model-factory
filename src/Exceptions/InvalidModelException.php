<?php

namespace Chustilla\ModelFactory\Exceptions;

use Exception;
use Throwable;

class InvalidModelException extends Exception
{
    public function __construct(string $model, $message = "", $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = "Model {$model} is not a CakePHP model";
        }
        parent::__construct($message, $code, $previous);
    }
}
