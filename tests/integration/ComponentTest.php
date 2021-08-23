<?php

namespace integration;

use ClarkWinkelmann\Mithril2Html\AnonymousComponent;
use ClarkWinkelmann\Mithril2Html\Renderer;
use ClarkWinkelmann\Mithril2Html\Setup;
use Flarum\Extend\View;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;

class ComponentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // This is the URL to the PHP development server
        $this->config('url', 'http://localhost:8080');

        // We pre-set the browsershot token because the transaction prevents sharing it with the other process
        //$this->setting('mithril2html.token', 'testing');

        // The "seed" and extenders are registered in the router.php of the PHP dev server

        // The extender doesn't actually have much effect here, the actual usage happens in router.php
        // This is just necessary for the UrlGenerator to know the route name
        // And also the blade directive
        $this->extend(
            new Setup(),
            (new View())->namespace('mithril2html-test', __DIR__ . '/../fixtures'),
        );
    }

    protected function getRenderer(): Renderer
    {
        $this->app();

        return resolve(Renderer::class);
    }

    public function test_hello_world()
    {
        $this->assertEquals('<p>Hello World</p>', $this->getRenderer()->render(new AnonymousComponent('hello-world')));
    }

    public function test_discussion_title()
    {
        $this->assertEquals('<h1>The discussion</h1>', $this->getRenderer()->render(new AnonymousComponent('discussion-title', '/discussions/1')));
    }

    public function test_whoami()
    {
        $this->assertEquals('<span>admin</span>', $this->getRenderer()->render(new AnonymousComponent('whoami', null, User::find(1))));
    }

    public function test_blade()
    {
        /**
         * @var Factory $view
         */
        $view = $this->app()->getContainer()->make(Factory::class);

        $this->assertEquals("<p>Template</p>\n\n<p>Hello World</p>", $view->make('mithril2html-test::template'));
    }
}
