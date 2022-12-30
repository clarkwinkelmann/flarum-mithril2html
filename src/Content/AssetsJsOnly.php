<?php

namespace ClarkWinkelmann\Mithril2Html\Content;

use Flarum\Foundation\Config;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Document;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Same as Flarum's Flarum\Frontend\Content\Assets, but it skips CSS unless explicitly requested
 * This is to improve performance as there's no need for CSS during headless HTML rendering
 */
class AssetsJsOnly
{
    protected Container $container;
    protected Config $config;
    protected Assets $assets;

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function forFrontend(string $name): self
    {
        $this->assets = $this->container->make('flarum.assets.' . $name);

        return $this;
    }

    public function __invoke(Document $document, ServerRequestInterface $request)
    {
        $locale = $request->getAttribute('locale');

        // The extension will use the header but to make testing easier a query param is also supported
        $withCss = $request->getHeaderLine('X-Browsershot-CSS') === '1' || Arr::get($request->getQueryParams(), 'withCSS') === '1';

        $compilers = array_merge([
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)],
        ], $withCss ? [
            'css' => [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)],
        ] : []);

        if ($this->config->inDebugMode()) {
            $this->forceCommit(Arr::flatten($compilers));
        }

        $document->js = array_merge($document->js, $this->getUrls($compilers['js']));

        if ($withCss) {
            $document->css = array_merge($document->css, $this->getUrls($compilers['css']));
        }
    }

    private function forceCommit(array $compilers)
    {
        /** @var CompilerInterface $compiler */
        foreach ($compilers as $compiler) {
            $compiler->commit(true);
        }
    }

    private function getUrls(array $compilers)
    {
        return array_filter(array_map(function (CompilerInterface $compiler) {
            return $compiler->getUrl();
        }, $compilers));
    }
}
