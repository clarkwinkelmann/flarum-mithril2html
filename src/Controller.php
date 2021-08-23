<?php

namespace ClarkWinkelmann\Mithril2Html;

use Flarum\Api\Client;
use Flarum\Frontend\Document;
use Flarum\Frontend\Frontend;
use Flarum\Http\Exception\RouteNotFoundException;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\NotAuthenticatedException;
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
            throw new NotAuthenticatedException();
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
            $frontend->content(function (Document $document, ServerRequestInterface $request) use ($preload) {
                //TODO: find a way for query strings to be usable
                $response = $this->api
                    ->withParentRequest($request)
                    ->get($preload);

                // Most content classes usually throw an HTTP error code if the API request was 404
                // we'll do the same here as this makes the most sense
                if ($response->getStatusCode() === 404) {
                    throw new RouteNotFoundException;
                }

                $document->payload['apiDocument'] = json_decode($response->getBody());
            });
        }

        return new HtmlResponse(
            $frontend->document(RequestUtil::withActor($request, $actor))->render()
        );
    }
}
