<?php

namespace Chustilla\ModelFactory;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Chustilla\ModelFactory\Exceptions\DefinitionNotFoundException;
use Chustilla\ModelFactory\Exceptions\DuplicateDefinitionException;
use Chustilla\ModelFactory\Exceptions\InvalidModelException;
use Chustilla\ModelFactory\Exceptions\ModelNotFoundException;
use Symfony\Component\Finder\Finder;

class Factory
{
    private static $instance = null;
    private $definitions = [];
    private $currentDefinition;
    private $repositoriesForTruncating = [];
    private $faker;

    private function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    public static function getInstance(): self
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function loadFactories($path): self
    {
        $factory = $this;

        if (is_dir($path)) {
            foreach (Finder::create()->files()->name('*.php')->in($path) as $file) {
                require $file->getRealPath();
            }
        }

        return $factory;
    }

    /**
     * @param string $class
     * @param callable $attributes
     * @return $this
     * @throws DuplicateDefinitionException
     */
    public function define(string $class, callable $attributes): self
    {
        if ($this->hasDefinition($class)) {
            throw new DuplicateDefinitionException($class);
        }

        $this->definitions[$class] = call_user_func($attributes, $this->faker);
        return $this;
    }

    /**
     * @param array|null $attributes
     * @return Entity
     * @throws ModelNotFoundException|InvalidModelException
     */
    public function create(?array $attributes = [])
    {
        $model = $this->make($attributes);
        $this->persist($model);

        return $model;
    }

    /**
     * @param array|null $attributes
     * @return Entity
     * @throws ModelNotFoundException|InvalidModelException
     */
    public function make(?array $attributes = []): Entity
    {
        $model = $this->makeModel($this->currentDefinition);
        $this->setModelAttributes($model, $attributes);

        return $model;
    }

    /**
     * @param string $class
     * @return Entity
     * @throws ModelNotFoundException
     */
    private function makeModel(string $class): Entity
    {
        if(!class_exists($class)) {
            throw new ModelNotFoundException($class);
        }

        return new $class();
    }

    private function setModelAttributes(Entity $model, ?array $attributes = [])
    {
        $attr = array_merge($this->getAttributesFromCurrentDefinition(), $attributes);
        foreach ($attr as $name => $value) {
            $this->setModelAttribute($model, $name, $value);
        }
    }

    private function getAttributesFromCurrentDefinition(): array
    {
        return $this->definitions[$this->currentDefinition];
    }

    private function setModelAttribute(Entity $model, string $attribute, $value)
    {
        $model->{$attribute} = $value;
    }

    /**
     * @param Entity $model
     * @return Entity
     * @throws InvalidModelException
     */
    private function persist(Entity $model): Entity
    {
        $repository = $this->getRepositoryFor($model);
        $this->addTableForTruncatingFrom($model);
        $repository->save($model);
        return $model;
    }

    /**
     * @param Entity $model
     * @return Table
     * @throws InvalidModelException
     */
    private function getRepositoryFor(Entity $model): Table
    {
        $repositoryAlias = $this->getRepositoryAliasFrom($model);

        return TableRegistry::getTableLocator()->get($repositoryAlias);
    }

    private function getRepositoryAliasFrom(Entity $model): string
    {
        return Inflector::pluralize(
            (new \ReflectionClass(get_class($model)))->getShortName()
        );
    }

    private function addTableForTruncatingFrom(Entity $model): void
    {
        $repository = $this->getRepositoryAliasFrom($model);
        if (!in_array($repository, $this->repositoriesForTruncating)) {
            $this->repositoriesForTruncating[] = $repository;
        }
    }

    /**
     * @param string $class
     * @return $this
     * @throws DefinitionNotFoundException
     */
    public function getFactoryOf(string $class): Factory
    {
        if (!$this->hasDefinition($class)) {
            throw new DefinitionNotFoundException($class);
        }
        $this->currentDefinition = $class;
        return $this;
    }

    private function hasDefinition(string $definitionName): bool
    {
        return array_key_exists($definitionName, $this->definitions);
    }

    public function cleanUpData()
    {
        while (count($this->repositoriesForTruncating)) {
            TableRegistry::getTableLocator()->get(
                array_shift($this->repositoriesForTruncating)
            )->deleteAll([]);
        }
    }
}
