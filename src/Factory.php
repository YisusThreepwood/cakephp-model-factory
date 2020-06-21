<?php

namespace Chustilla\ModelFactory;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Chustilla\ModelFactory\Exceptions\DefinitionNotFoundException;
use Chustilla\ModelFactory\Exceptions\DuplicateDefinitionException;
use Chustilla\ModelFactory\Exceptions\ModelStateNotFoundException;
use Symfony\Component\Finder\Finder;
use Faker\Factory as Faker;

class Factory
{
    private static $instance = null;
    private $definitions = [];
    private $currentDefinition;
    private $repositoriesForTruncating = [];
    private $faker;
    private $states = [];
    private $times;

    /**
     * @var array|string
     */
    private $currentStates = [];

    private function __construct()
    {
        $this->faker = Faker::create();
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
     * @param array|callable $attributes
     * @return $this
     * @throws DuplicateDefinitionException
     */
    public function define(string $class, $attributes): self
    {
        if ($this->hasDefinition($class)) {
            throw new DuplicateDefinitionException($class);
        }

        $this->definitions[$class] = $attributes;
        return $this;
    }

    private function hasDefinition(string $definitionName): bool
    {
        return array_key_exists($definitionName, $this->definitions);
    }

    public function state(string $definition, string $stateName, $attributes): self
    {
        if (!isset($this->states[$definition])) {
            $this->states[$definition] = [];
        }
        $this->states[$definition][$stateName] = $attributes;

        return $this;
    }

    /**
     * @param array|null $attributes
     * @return Entity|array
     */
    public function create(?array $attributes = [])
    {
        $statesToApply = $this->currentStates;

        $models = (new ModelBuilder(
            $this->currentDefinition,
            $this->definitions[$this->currentDefinition],
            $this->faker,
            array_filter(
                $statesToApply ? $this->states[$this->currentDefinition] : [],
                function (string $stateName) use ($statesToApply){
                    return  in_array($stateName, $statesToApply);
                },
                ARRAY_FILTER_USE_KEY
            ),
            $this->times
        ))->create($attributes);

        $this->times = null;
        $this->addTablesForTruncatingFrom($models);

        return $models;
    }

    private function addTablesForTruncatingFrom($models): void
    {
        if (is_array($models)) {
            foreach ($models as $model) {
                $this->addTableForTruncatingFrom($model);
            }
        } else {
            $this->addTableForTruncatingFrom($models);
        }
    }

    private function addTableForTruncatingFrom(Entity $model): void
    {
        $repository = repositoryAlias($model);
        if (!in_array($repository, $this->repositoriesForTruncating)) {
            $this->repositoriesForTruncating[] = $repository;
        }
    }

    public function make(?array $attributes = [])
    {
        $statesToApply = $this->currentStates;

        $models = (new ModelBuilder(
            $this->currentDefinition,
            $this->definitions[$this->currentDefinition],
            $this->faker,
            array_filter(
                $statesToApply ? $this->states[$this->currentDefinition] : [],
                function (string $stateName) use ($statesToApply){
                    return  in_array($stateName, $statesToApply);
                },
                ARRAY_FILTER_USE_KEY
            ),
            $this->times
        ))->make($attributes);

        $this->times = null;

        return $models;
    }

    /**
     * @param string $class
     * @return $this
     * @throws DefinitionNotFoundException
     */
    public function getFactoryOf(string $class, ?int $times = null): Factory
    {
        if (!$this->hasDefinition($class)) {
            throw new DefinitionNotFoundException($class);
        }
        $this->currentDefinition = $class;
        $this->times = $times;
        $this->currentStates = [];
        return $this;
    }

    /**
     * @param array|string $states
     * @return $this
     * @throws ModelStateNotFoundException
     */
    public function states($states): self
    {
        $statesArray = is_array($states) ? $states : [$states];
        foreach ($statesArray as $state) {
            if (!isset($this->states[$this->currentDefinition][$state])) {
                throw new ModelStateNotFoundException($this->currentDefinition, $state);
            }
        }

        $this->currentStates = $statesArray;

        return $this;
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
