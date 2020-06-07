<?php

namespace Chustilla\ModelFactory\Exceptions;

use Exception;
use Throwable;

class DuplicateDefinitionException extends Exception
{
    public function __construct(string $model, $message = "", $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = "Definition of model {$model} is duplicated";
        }
        parent::__construct($message, $code, $previous);
    }
}
