<?php

use Cake\Utility\Inflector;
use Chustilla\ModelFactory\Factory;
use \Cake\ORM\Entity;

if (!function_exists('factory')) {
    function factory(string $class, ?int $times = null): Factory
    {
        return Factory::getInstance()->getFactoryOf($class, $times);
    }
}

if (!function_exists('repositoryAlias')) {
    function repositoryAlias(Entity $model): string
    {
        return Inflector::pluralize(
            (new \ReflectionClass(get_class($model)))->getShortName()
        );
    }
}
