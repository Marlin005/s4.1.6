<?php

declare(strict_types=1);

namespace Doctrine\Bundle\MongoDBBundle\Tests\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

abstract class AbstractDriverTest extends TestCase
{
    public function testFindMappingFile()
    {
        $driver = $this->getDriver([
            'foo' => 'MyNamespace\MyBundle\DocumentFoo',
            $this->getFixtureDir() => 'MyNamespace\MyBundle\Document',
        ]);

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo')
        );
    }

    public function testFindMappingFileInSubnamespace()
    {
        $driver = $this->getDriver([$this->getFixtureDir() => 'MyNamespace\MyBundle\Document']);

        $locator = $this->getDriverLocator($driver);

        $this->assertEquals(
            $this->getFixtureDir() . '/Foo.Bar' . $this->getFileExtension(),
            $locator->findMappingFile('MyNamespace\MyBundle\Document\Foo\Bar')
        );
    }

    /**
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testFindMappingFileNamespacedFoundFileNotFound()
    {
        $driver = $this->getDriver([$this->getFixtureDir() => 'MyNamespace\MyBundle\Document']);

        $locator = $this->getDriverLocator($driver);
        $locator->findMappingFile('MyNamespace\MyBundle\Document\Missing');
    }

    /**
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function testFindMappingNamespaceNotFound()
    {
        $driver = $this->getDriver([$this->getFixtureDir() => 'MyNamespace\MyBundle\Document']);

        $locator = $this->getDriverLocator($driver);
        $locator->findMappingFile('MyOtherNamespace\MyBundle\Document\Foo');
    }

    abstract protected function getFileExtension();

    abstract protected function getFixtureDir();

    abstract protected function getDriver(array $paths = []);

    private function getDriverLocator(FileDriver $driver)
    {
        $ref = new ReflectionProperty($driver, 'locator');
        $ref->setAccessible(true);

        return $ref->getValue($driver);
    }
}
