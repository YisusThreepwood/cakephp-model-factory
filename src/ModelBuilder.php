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

    public function __construct(string $modelClass, $definition, Generator $faker, ?array $states = [])
    {
        $this->modelClass = $modelClass;
        $this->definition = $definition;
        $this->states = $states;
        $this->faker = $faker;
    }

    /**
     * @param array|null $attributes
     * @return Entity
     * @throws ModelNotFoundException
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
     * @throws ModelNotFoundException
     */
    public function make(?array $attributes = []): Entity
    {
        $model = $this->makeModel($this->modelClass);
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
