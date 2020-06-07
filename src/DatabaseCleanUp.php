<?php

namespace Chustilla\ModelFactory;

trait DatabaseCleanUp
{
    public function __destruct()
    {
        Factory::getInstance()->cleanUpData();
    }

    public function tearDown()
    {
        Factory::getInstance()->cleanUpData();
    }
}
