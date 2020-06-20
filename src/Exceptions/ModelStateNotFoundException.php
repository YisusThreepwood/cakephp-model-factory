<?php

namespace Chustilla\ModelFactory\Exceptions;

use Exception;
use Throwable;

class ModelStateNotFoundException extends Exception
{
    public function __construct(string $model, string $state, string $message = "", int $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = "State {$state} not found for model {$model} not found";
        }
        parent::__construct($message, $code, $previous);
    }
}
