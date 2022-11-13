<?php

namespace ClarkWinkelmann\Mithril2Html;

use ClarkWinkelmann\Mithril2Html\Exception\PreloadFailException;
use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Frontend\Frontend;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use Flarum\User\User;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Controller implements RequestHandlerInterface
{
    protected $settings;
    protected $api;

    public function __construct(SettingsRepositoryInterface $settings, Client $api)
    {
        $this->settings = $settings;
        $this->api = $api;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $token = $this->settings->get('mithril2html.token');

        if (!$token || $token !== $request->getHeaderLine('X-Browsershot-Auth')) {
            // Allow admins to load the mithril2html frontend for debugging
            // This is not meant to be accessed via a real browser under normal circumstances
            RequestUtil::getActor($request)->assertAdmin();
        }

        if ($userId = $request->getHeaderLine('X-Browsershot-User')) {
            $actor = User::findOrFail($userId);
        } else {
            $actor = new Guest();
        }

        /**
         * @var Frontend $frontend
         */
        $frontend = resolve('flarum.frontend.mithril2html');

        if ($preload = $request->getHeaderLine('X-Browsershot-Preload')) {
            $frontend->content(function (Document $document, ServerRequestInterface $request) use ($preload, $actor) {
                //TODO: find a way for query strings to be usable
                $response = $this->api
                    ->withActor($actor)
                    ->get($preload);

                // Maybe we should still proceed somehow for 404 errors because the app might want to render a custom javascript component for it
                // For other errors it's logical to stop here because the single page app will likely break as the result of an incomplete payload
                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    throw new PreloadFailException("The preload request for $preload failed with status code " . $response->getStatusCode());
                }

                $document->payload['apiDocument'] = json_decode($response->getBody());
            });
        }

        return new HtmlResponse(
            $frontend->document(RequestUtil::withActor($request, $actor))->render()
        );
    }
}
