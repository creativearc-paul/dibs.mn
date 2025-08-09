<?php

declare (strict_types=1);
namespace BoldMinded\DataGrab\Dependency\League\Container\Argument;

interface ArgumentInterface
{
    /**
     * @return mixed
     */
    public function getValue();
}
