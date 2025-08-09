<?php

declare (strict_types=1);
namespace BoldMinded\DataGrab\Dependency\League\Container\Argument;

interface DefaultValueInterface extends ArgumentInterface
{
    /**
     * @return mixed
     */
    public function getDefaultValue();
}
