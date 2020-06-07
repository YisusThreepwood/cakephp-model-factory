<?php

use Chustilla\ModelFactory\Factory;
use Chustilla\ModelFactory\Exceptions\DefinitionNotFoundException;

if (!function_exists('factory')) {
    function factory(string $class): Factory
    {
        return Factory::getInstance()->getFactoryOf($class);
    }
}
