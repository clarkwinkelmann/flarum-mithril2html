<?php

namespace ClarkWinkelmann\Mithril2Html;

use ClarkWinkelmann\Mithril2Html\Exception\RenderFailException;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\DomCrawler\Crawler;

class Renderer
{
    protected $settings;
    protected $url;

    public function __construct(SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->settings = $settings;
        $this->url = $url;
    }

    protected $browsershot = null;
    protected $html = null;

    public function render(ComponentInterface $component): string
    {
        $token = $this->settings->get('mithril2html.token');

        if (!$token) {
            $token = Str::random(32);

            $this->settings->set('mithril2html.token', $token);
        }

        $endpoint = $this->url->to('forum')->route('mithril2html') . '#!/' . $component->route();

        $actor = $component->actor();

        $this->browsershot = Browsershot::url($endpoint)
            ->setExtraHttpHeaders([
                'X-Browsershot-Auth' => $token,
                'X-Browsershot-User' => $actor ? (string)$actor->id : '',
                'X-Browsershot-Preload' => $component->preload() ?? '',
            ]);
        $this->html = $this->browsershot->bodyHtml();

        $selector = $component->selector();

        if (!$selector) {
            return trim($this->html);
        }

        $crawler = new Crawler($this->html);

        $node = $crawler->filter($selector);

        if ($node->count() === 0) {
            if ($crawler->filter('#app')->count() === 0) {
                throw new RenderFailException("Could not find element with selector $selector nor #app on $endpoint. The page was probably blocked by the webserver configuration or a proxy");
            }

            throw new RenderFailException("Could not find element with selector $selector on $endpoint");
        }

        return trim($node->html());
    }

    public function getFullHtml(): ?string
    {
        return $this->html;
    }

    public function getBrowsershotInstance(): ?Browsershot
    {
        return $this->browsershot;
    }
}
