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
    protected SettingsRepositoryInterface $settings;
    protected UrlGenerator $url;

    public function __construct(SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->settings = $settings;
        $this->url = $url;
    }

    protected ?Browsershot $browsershot = null;
    protected ?string $html = null;

    protected function prepareBrowsershot(ComponentInterface $component, bool $withCSS = false): string
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
                'X-Browsershot-CSS' => $withCSS ? '1' : '0',
            ]);

        return $endpoint;
    }

    /**
     * Render a component to HTML code
     * @param ComponentInterface $component The component to render
     * @return string The HTML of the tag matched by the component selector, or the entire HTML document if no selector is provided
     * @throws RenderFailException If Browsershot ran successfully but Mithril2Html encountered an issue with the output
     */
    public function render(ComponentInterface $component): string
    {
        $endpoint = $this->prepareBrowsershot($component);

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

    /**
     * Get a Browsershot instance for a component that can be used for a customized output
     * @param ComponentInterface $component The component to render
     * @param bool $withCSS Whether to load the CSS for forum+mithril2html frontends in the page
     * @return Browsershot An unused Browsershot instance pre-configured with the internal URL to render the given component
     */
    public function browsershot(ComponentInterface $component, bool $withCSS = true): Browsershot
    {
        $this->prepareBrowsershot($component, $withCSS);

        return $this->browsershot;
    }

    /**
     * After render() as been called, this method offers a quick access to the entire page HTML output.
     */
    public function getFullHtml(): ?string
    {
        return $this->html;
    }

    /**
     * After render() has been called, this method offers access to the browsershot instance that was used.
     * This allows accessing the console and network output.
     */
    public function getBrowsershotInstance(): ?Browsershot
    {
        return $this->browsershot;
    }
}
