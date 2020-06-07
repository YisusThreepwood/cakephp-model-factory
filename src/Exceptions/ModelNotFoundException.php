<?php

namespace Chustilla\ModelFactory\Exceptions;

use Exception;
use Throwable;

class ModelNotFoundException extends Exception
{
    public function __construct(string $model, $message = "", $code = 0, Throwable $previous = null)
    {
        if (!$message) {
            $message = "Model {$model} not found";
        }
        parent::__construct($message, $code, $previous);
    }
}
