<?php

namespace ClarkWinkelmann\Mithril2Html;

use Flarum\Foundation\Config;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Document;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Same as Flarum's Flarum\Frontend\Content\Assets but it skips CSS
 * This is to improve performance as there's no need for CSS during headless HTML rendering
 */
class AssetsContentJsOnly
{
    protected $container;
    protected $config;
    protected $assets;

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    public function forFrontend(string $name)
    {
        $this->assets = $this->container->make('flarum.assets.' . $name);

        return $this;
    }

    public function __invoke(Document $document, Request $request)
    {
        $locale = $request->getAttribute('locale');

        $compilers = [
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)],
        ];

        if ($this->config->inDebugMode()) {
            $this->forceCommit(Arr::flatten($compilers));
        }

        $document->js = array_merge($document->js, $this->getUrls($compilers['js']));
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
