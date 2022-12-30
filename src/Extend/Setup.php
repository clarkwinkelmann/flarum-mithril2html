<?php

namespace ClarkWinkelmann\Mithril2Html\Extend;

use ClarkWinkelmann\Mithril2Html\Console\TroubleshootCommand;
use ClarkWinkelmann\Mithril2Html\Content\AssetsJsOnly;
use ClarkWinkelmann\Mithril2Html\Controller;
use ClarkWinkelmann\Mithril2Html\Renderer;
use Flarum\Extend;
use Flarum\Extension\Extension;
use Flarum\Frontend\Content;
use Flarum\Frontend\Document;
use Flarum\Frontend\Frontend;
use Illuminate\Contracts\Container\Container;
use Illuminate\View\Compilers\BladeCompiler;

class Setup implements Extend\ExtenderInterface
{
    static bool $configured = false;

    public function extend(Container $container, Extension $extension = null)
    {
        if (self::$configured) {
            return;
        }

        self::$configured = true;

        (new Extend\Console())
            ->command(TroubleshootCommand::class)
            ->extend($container);

        (new Extend\Frontend('mithril2html'))
            ->js(__DIR__ . '/../../js/dist/mithril2html.js')
            ->css(__DIR__ . '/../../less/mithril2html.less')
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

            $frontend->content($container->make(AssetsJsOnly::class)->forFrontend('forum'));
            $frontend->content($container->make(AssetsJsOnly::class)->forFrontend('mithril2html'));
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
