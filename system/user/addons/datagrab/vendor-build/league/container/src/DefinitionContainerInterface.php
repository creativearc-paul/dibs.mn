<?php

declare (strict_types=1);
namespace BoldMinded\DataGrab\Dependency\League\Container;

use BoldMinded\DataGrab\Dependency\League\Container\Definition\DefinitionInterface;
use BoldMinded\DataGrab\Dependency\League\Container\Inflector\InflectorInterface;
use BoldMinded\DataGrab\Dependency\League\Container\ServiceProvider\ServiceProviderInterface;
use BoldMinded\DataGrab\Dependency\Psr\Container\ContainerInterface;
interface DefinitionContainerInterface extends ContainerInterface
{
    public function add(string $id, $concrete = null) : DefinitionInterface;
    public function addServiceProvider(ServiceProviderInterface $provider) : self;
    public function addShared(string $id, $concrete = null) : DefinitionInterface;
    public function extend(string $id) : DefinitionInterface;
    public function getNew($id);
    public function inflector(string $type, ?callable $callback = null) : InflectorInterface;
}
