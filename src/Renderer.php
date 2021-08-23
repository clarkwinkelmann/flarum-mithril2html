<?php

namespace ClarkWinkelmann\Mithril2Html;

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

    public function render(ComponentInterface $component): string
    {
        $token = $this->settings->get('mithril2html.token');

        if (!$token) {
            $token = Str::random(32);

            $this->settings->set('mithril2html.token', $token);
        }

        $endpoint = $this->url->to('forum')->route('mithril2html') . '#!/' . $component->route();

        $actor = $component->actor();

        $html = Browsershot::url($endpoint)
            ->setExtraHttpHeaders([
                'X-Browsershot-Auth' => $token,
                'X-Browsershot-User' => $actor ? (string)$actor->id : '',
                'X-Browsershot-Preload' => $component->preload() ?? '',
            ])
            ->bodyHtml();

        $selector = $component->selector();

        if (!$selector) {
            return trim($html);
        }

        $crawler = new Crawler($html);

        $node = $crawler->filter($selector);

        if ($node->count() === 0) {
            if ($crawler->filter('#app')->count() === 0) {
                throw new \Exception("Could not find element with selector $selector nor #app on $endpoint. The page was probably blocked by the webserver configuration or a proxy");
            }

            throw new \Exception("Could not find element with selector $selector on $endpoint");
        }

        return trim($node->html());
    }
}
