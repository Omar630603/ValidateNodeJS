<?php

namespace App\Events\CloneRepo;

use App\Events\CloneRepo\CloneRepoEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class CloneRepoListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CloneRepoEvent $event): void
    {
        Log::info("Cloning repo {$event->repoUrl} into {$event->tempDir}");

        $process = new Process(['git', 'clone', $event->repoUrl, $event->tempDir]);
        $process->run();

        if ($process->isSuccessful()) {
            Log::info("Cloned repo {$event->repoUrl} into {$event->tempDir}");
            // Update the submission status to "cloned"
            // Dispatch the next event (e.g. RunTestsEvent)

        } else {
            Log::error("Failed to clone repo {$event->repoUrl}");
            // Update the submission status to "clone_failed"
        }
    }
}
