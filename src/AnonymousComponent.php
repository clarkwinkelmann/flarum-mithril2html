<?php

namespace ClarkWinkelmann\Mithril2Html;

use Flarum\User\User;

class AnonymousComponent implements ComponentInterface
{
    protected string $route;
    protected ?string $preload;
    protected ?User $actor;
    protected string $selector;

    public function __construct(string $route, string $preload = null, User $actor = null, string $selector = '#content')
    {
        $this->route = $route;
        $this->preload = $preload;
        $this->actor = $actor;
        $this->selector = $selector;
    }

    public function route(): string
    {
        return $this->route;
    }

    public function preload(): ?string
    {
        return $this->preload;
    }

    public function actor(): ?User
    {
        return $this->actor;
    }

    public function selector(): ?string
    {
        return $this->selector;
    }
}
