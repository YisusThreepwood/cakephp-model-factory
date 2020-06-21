<?php

namespace Chustilla\ModelFactory;

use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Chustilla\ModelFactory\Exceptions\ModelNotFoundException;
use Faker\Generator;

class ModelBuilder
{
    private $modelClass;
    private $definition;
    private $states;
    private $faker;
    private $times;

    public function __construct(
        string $modelClass,
        $definition,
        Generator $faker,
        ?array $states = [],
        ?int $times = null
    ) {
        $this->modelClass = $modelClass;
        $this->definition = $definition;
        $this->states = $states;
        $this->faker = $faker;
        $this->times = $times;
    }

    /**
     * @param array|null $attributes
     * @return Entity|array
     * @throws ModelNotFoundException
     */
    public function create(?array $attributes = [])
    {
        $models = $this->make($attributes);
        if (is_array($models)) {
            foreach ($models as $model) {
                $this->persist($model);
            }
        } else {
            $this->persist($models);
        }


        return $models;
    }

    /**
     * @param array|null $attributes
     * @return Entity|array
     * @throws ModelNotFoundException
     */
    public function make(?array $attributes = [])
    {
        if ($this->times && $this->times > 1) {
            $models = [];
            for ($i = 0; $i < $this->times; $i++) {
                $model = $this->makeModel($this->modelClass);
                $this->setModelAttributes($model, $attributes);
                $models[] = $model;
            }
        } else {
            $models = $this->makeModel($this->modelClass);
            $this->setModelAttributes($models, $attributes);
        }

        return $models;
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
        $attr = array_merge(
            $this->getAttributesFromCurrentDefinition(),
            $this->getAttributesFromCurrentStates(),
            $attributes
        );
        foreach ($attr as $name => $value) {
            $this->setModelAttribute($model, $name, $value);
        }
    }

    private function getAttributesFromCurrentDefinition(): array
    {
        return $this->getAttributesFrom($this->definition);
    }

    private function getAttributesFrom($attributes): array
    {
        return array_map(function ($attr) {
            return is_callable($attr) ? call_user_func($attr, $this->faker) : $attr;
        }, is_callable($attributes) ? call_user_func($attributes, $this->faker) : $attributes);
    }

    private function getAttributesFromCurrentStates(): array
    {
        $attributes = [];
        foreach ($this->states as $state) {
            $attributes = array_merge($this->getAttributesFrom($state));
        }
        return $attributes;
    }

    private function setModelAttribute(Entity $model, string $attribute, $value)
    {
        $model->{$attribute} = $value;
    }

    /**
     * @param Entity $model
     * @return Entity
     */
    private function persist(Entity $model): Entity
    {
        $repository = $this->getRepositoryFor($model);
        $repository->save($model);
        return $model;
    }

    /**
     * @param Entity $model
     * @return Table
     */
    private function getRepositoryFor(Entity $model): Table
    {
        return TableRegistry::getTableLocator()->get(repositoryAlias($model));
    }
}
