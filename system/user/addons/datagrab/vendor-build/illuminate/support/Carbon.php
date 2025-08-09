<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Support;

use BoldMinded\DataGrab\Dependency\Carbon\Carbon as BaseCarbon;
use BoldMinded\DataGrab\Dependency\Carbon\CarbonImmutable as BaseCarbonImmutable;
class Carbon extends BaseCarbon
{
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
}
