<?php

namespace Chustilla\ModelFactory\Exceptions;

use Exception;
use Throwable;

class DefinitionNotFoundException extends Exception
{
    public function __construct(string $model, $message = "", $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = "Definition of model {$model} not found";
        }
        parent::__construct($message, $code, $previous);
    }
}
