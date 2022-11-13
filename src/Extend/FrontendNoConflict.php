<?php

namespace ClarkWinkelmann\Mithril2Html\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Event\Disabled;
use Flarum\Extension\Event\Enabled;
use Flarum\Extension\Extension;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\RecompileFrontendAssets;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\Event\Saved;
use Illuminate\Contracts\Container\Container;

/**
 * Works the same way as Flarum's Extend\Frontend extender for javascript
 * But will automatically merge an extension's exports with existing exports
 *
 * This allows having both a forum and mithril2html bundle both exporting components without the second overriding the first
 *
 * It assumes another bundle for the same extension has already been registered with Flarum's Frontend extension beforehand!
 */
class FrontendNoConflict implements ExtenderInterface
{
    private $frontend;

    private $js;

    /**
     * @param string $frontend The name of the frontend.
     */
    public function __construct(string $frontend)
    {
        $this->frontend = $frontend;
    }


    /**
     * Add a JavaScript file to load in the frontend.
     *
     * @param string $path The path to the JavaScript file.
     * @return self
     */
    public function js(string $path): self
    {
        $this->js = $path;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $this->registerAssets($container, $this->getModuleName($extension));
    }

    private function registerAssets(Container $container, string $moduleName): void
    {
        $abstract = 'flarum.assets.' . $this->frontend;

        $container->resolving($abstract, function (Assets $assets) use ($moduleName) {
            if ($this->js) {
                $assets->js(function (SourceCollector $sources) use ($moduleName) {
                    $sources->addString(function () {
                        return 'var module={};';
                    });
                    $sources->addFile($this->js);
                    $sources->addString(function () use ($moduleName) {
                        // This is the only difference with the original extender
                        // We merge the keys of the first-level objects together
                        // In Flamarkt that key is always the frontend name
                        return "flarum.extensions['$moduleName']={...flarum.extensions['$moduleName'],...module.exports};";
                    });
                });
            }
        });

        if (!$container->bound($abstract)) {
            $container->bind($abstract, function (Container $container) {
                return $container->make('flarum.assets.factory')($this->frontend);
            });

            /** @var \Illuminate\Contracts\Events\Dispatcher $events */
            $events = $container->make('events');

            $events->listen(
                [Enabled::class, Disabled::class, ClearingCache::class],
                function () use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->flush();
                }
            );

            $events->listen(
                Saved::class,
                function (Saved $event) use ($container, $abstract) {
                    $recompile = new RecompileFrontendAssets(
                        $container->make($abstract),
                        $container->make(LocaleManager::class)
                    );
                    $recompile->whenSettingsSaved($event);
                }
            );
        }
    }

    private function getModuleName(?Extension $extension): string
    {
        return $extension ? $extension->getId() : 'site-custom';
    }
}
