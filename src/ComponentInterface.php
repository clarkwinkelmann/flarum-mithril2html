<?php

namespace ClarkWinkelmann\Mithril2Html;

use Flarum\User\User;

interface ComponentInterface
{
    public function route(): string;

    public function preload(): ?string;

    public function actor(): ?User;

    public function selector(): ?string;
}
