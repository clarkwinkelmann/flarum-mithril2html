<?php

namespace ClarkWinkelmann\Mithril2Html;

use Flarum\Extend;
use Flarum\Extension\Extension;
use Flarum\Frontend\Content;
use Flarum\Frontend\Document;
use Flarum\Frontend\Frontend;
use Illuminate\Contracts\Container\Container;
use Illuminate\View\Compilers\BladeCompiler;

class Setup implements Extend\ExtenderInterface
{
    static $configured = false;

    public function extend(Container $container, Extension $extension = null)
    {
        if (self::$configured) {
            return;
        }

        self::$configured = true;

        (new Extend\Frontend('mithril2html'))
            ->js(__DIR__ . '/../js/dist/mithril2html.js')
            ->extend($container);

        (new Extend\Routes('forum'))
            ->get('/mithril2html', 'mithril2html', Controller::class)
            ->extend($container);

        // Enables the use of a custom frontend
        $container->bind('flarum.frontend.mithril2html', function () use ($container) {
            $frontend = $container->make(Frontend::class);

            $frontend->content(function (Document $document) {
                $document->layoutView = 'flarum::frontend.forum';
            });

            $frontend->content($container->make(AssetsContentJsOnly::class)->forFrontend('forum'));
            $frontend->content($container->make(AssetsContentJsOnly::class)->forFrontend('mithril2html'));
            $frontend->content($container->make(Content\CorePayload::class));
            $frontend->content($container->make(Content\Meta::class));

            return $frontend;
        });

        $container->extend('blade.compiler', function (BladeCompiler $blade) {
            $blade->directive('mithril2html', function ($expression) {
                return '<?php echo resolve(' . Renderer::class . "::class)->render($expression) ?>";
            });

            return $blade;
        });
    }
}
