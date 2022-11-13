<?php

namespace ClarkWinkelmann\Mithril2Html\Console;

use ClarkWinkelmann\Mithril2Html\AnonymousComponent;
use ClarkWinkelmann\Mithril2Html\Renderer;
use Flarum\User\UserRepository;
use Illuminate\Console\Command;

class TroubleshootCommand extends Command
{
    protected $signature = 'mithril2html:troubleshoot {route} {--preload=} {--actor=} {--selector=#content}';
    protected $description = 'Render a component from the command line for testing';

    public function handle(Renderer $renderer, UserRepository $repository)
    {
        $actor = $this->hasOption('actor') ? $repository->findOrFail($this->option('actor')) : null;

        $this->line("\n\n## Output HTML\n");

        try {
            $this->line($renderer->render(new AnonymousComponent(
                $this->argument('route'),
                $this->option('preload'),
                $actor,
                $this->option('selector'),
            )));
        } catch (\Exception $exception) {
            $this->error('Rendering failed: ' . $exception->getMessage());

            $this->line("\n\n## Full HTML\n");
            $this->line($renderer->getFullHtml());
        }

        $messages = $renderer->getBrowsershotInstance()->consoleMessages();

        $this->line("\n\n## Console Messages\n");

        if (count($messages) === 0) {
            $this->line('No console messages');
        }

        foreach ($messages as $message) {
            $this->line('[' . $message['type'] . '] ' . $message['message']);
        }

        $requests = $renderer->getBrowsershotInstance()->triggeredRequests();

        $this->line("\n\n## Network requests\n");

        if (count($requests) === 0) {
            $this->line('No requests');
        }

        foreach ($requests as $request) {
            $this->line($request['url']);
        }
    }
}
