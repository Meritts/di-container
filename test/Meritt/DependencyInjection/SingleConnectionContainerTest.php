<?php

namespace Meritt\DependencyInjection;


class SingleConnectionContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function trueMustBeReturnedWhenIsConfiguredAsDevelopMode()
    {
        $container = new SingleConnectionContainer();
        $container->setParameter('environment', 'dev');

        $this->assertTrue($container->isDevelopment());
    }

    /**
     * @test
     */
    public function falseMustBeReturnedWhenIsConfiguredAsDevelopMode()
    {
        $container = new SingleConnectionContainer();
        $container->setParameter('environment', 'prod');

        $this->assertFalse($container->isDevelopment());
    }
}
