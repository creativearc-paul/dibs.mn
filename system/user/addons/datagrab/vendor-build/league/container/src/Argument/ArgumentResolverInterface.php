<?php

declare (strict_types=1);
namespace BoldMinded\DataGrab\Dependency\League\Container\Argument;

use BoldMinded\DataGrab\Dependency\League\Container\ContainerAwareInterface;
use ReflectionFunctionAbstract;
interface ArgumentResolverInterface extends ContainerAwareInterface
{
    public function resolveArguments(array $arguments) : array;
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []) : array;
}
