<?php

namespace BoldMinded\DataGrab\Dependency\Carbon\Laravel;

use BoldMinded\DataGrab\Dependency\Carbon\Carbon;
use BoldMinded\DataGrab\Dependency\Carbon\CarbonImmutable;
use BoldMinded\DataGrab\Dependency\Carbon\CarbonInterval;
use BoldMinded\DataGrab\Dependency\Carbon\CarbonPeriod;
use BoldMinded\DataGrab\Dependency\Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use BoldMinded\DataGrab\Dependency\Illuminate\Events\Dispatcher;
use BoldMinded\DataGrab\Dependency\Illuminate\Events\EventDispatcher;
use BoldMinded\DataGrab\Dependency\Illuminate\Support\Carbon as IlluminateCarbon;
use BoldMinded\DataGrab\Dependency\Illuminate\Support\Facades\Date;
use Throwable;
class ServiceProvider extends \BoldMinded\DataGrab\Dependency\Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->updateLocale();
        if (!$this->app->bound('events')) {
            return;
        }
        $service = $this;
        $events = $this->app['events'];
        if ($this->isEventDispatcher($events)) {
            $events->listen(\class_exists('BoldMinded\\DataGrab\\Dependency\\Illuminate\\Foundation\\Events\\LocaleUpdated') ? 'Illuminate\\Foundation\\Events\\LocaleUpdated' : 'locale.changed', function () use($service) {
                $service->updateLocale();
            });
        }
    }
    public function updateLocale()
    {
        $app = $this->app && \method_exists($this->app, 'getLocale') ? $this->app : app('translator');
        $locale = $app->getLocale();
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        CarbonPeriod::setLocale($locale);
        CarbonInterval::setLocale($locale);
        if (\class_exists(IlluminateCarbon::class)) {
            IlluminateCarbon::setLocale($locale);
        }
        if (\class_exists(Date::class)) {
            try {
                $root = Date::getFacadeRoot();
                $root->setLocale($locale);
            } catch (Throwable $e) {
                // Non Carbon class in use in Date facade
            }
        }
    }
    public function register()
    {
        // Needed for Laravel < 5.3 compatibility
    }
    protected function isEventDispatcher($instance)
    {
        return $instance instanceof EventDispatcher || $instance instanceof Dispatcher || $instance instanceof DispatcherContract;
    }
}
