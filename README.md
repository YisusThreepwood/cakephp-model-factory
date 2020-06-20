# cakephp-model-factory
An easy for rapid creation of models for the purpose of testing, inspired by Laravel Model Factory.

Requirements
---
 + PHP >=7.2

Installation
---
Via Composer

```
$ composer install chustilla/cakephp-model-factory
```

Usage
---
### Define factories
Through model definition you will tell to the factory how to populate the entity with data.

An example of model definition would be...

```php
# database/factories/BarFactory.php

<?php

use App\Model\Entity\Bar;

$factory->define(Bar::class, [
    'id' => 123,
    'name' => 'Guybrush Threepwood',
]);
```

That is an unusual model definition because it would create all entity instances with the same data.
Usually you need compute data or use [Faker](https://github.com/fzaninotto/Faker) library for fake data, in that case you can use a Closure for defining model attributes

```php
<?php

use App\Model\Entity\Bar;
use Faker\Generator as Faker;

$factory->define(Bar::class, function (Faker $faker) {
    return [
        'id' =>  $faker->numberBetween(1, 1000),
        'name' => $faker->name,
    ];
});
```

### Load model definitions
For creating models from its definitions you need to load those definitions.
A good place for loading the models definitions could be the tests bootstrap file,
by specifying the models definitions directory path.

```php
# tests/bootstrap.php

<?php

use Chustilla\ModelFactory\Factory;

Factory::getInstance()->loadFactories('absolute/or/relative/path/to/models/definitions/directory');
```

### Creating models
For an easy model creation the library provides the *factory()* helper

```php
# tests/TestCase/Unit/Service/FooServiceTest.php

<?php

use Cake\TestSuite\TestCase;

class FooServiceTest extends TestCase
{
    public function testMethod()
    {
        // Arrange
        $bar = factory(Bar::class)->create();
        ...
    }
}
```

That will create a full populate entity and save it in the datasurce defined for testing.
If you don't need to persist the data you can use the *make()* method

```php
$bar = factory(Bar::class)->make();
```

### Override model definition data
By passing an array of attributes to *create()* or *make()* functions you can override the data filled by the model definition

```php
$bar = factory(Bar::class)->make(['id' => 1, 'name' => 'LeChuck']);
```

### States
By states, you can define discrete modifications to the model attributes.

```php
<?php

use App\Model\Entity\Bar;
use App\Model\Entity\Foo;
use Faker\Generator as Faker;

$factory->define(Bar::class, function (Faker $faker) {
    return [
        'id' =>  $faker->numberBetween(1, 1000),
        'name' => $faker->name,
    ];
});

$factory->state(Bar::class, 'withFoo', function () {
    return [
        foo => factory(Foo::class)->create(),
    ];
});
```

As for definitions you can define state attributes by a Closure or a simple array

Apply states during the model building in the bellow way. States combination is allowed.

```php
$bar = factory(Bar::class)->states['withFoo', 'active']->create();
```

### Deleting models
For ensuring tests isolation is very important to remove the models stored by each test.
The library provides a trait which will do it for you.

```php
# tests/TestCase/Unit/Service/FooServiceTest.php

<?php

use Cake\TestSuite\TestCase;
use Chustilla\ModelFactory\DatabaseCleanUp;

class FooServiceTest extends TestCase
{
    use DatabaseCleanUp;

    public function testMethod()
    {
        // Arrange
        $bar = factory(Bar::class)->create();
        ...
    }
}
```


License
---
The MIT License (MIT). Please see [License File](./LICENSE) for more information.
