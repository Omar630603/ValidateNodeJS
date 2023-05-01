<?php

namespace App\Events\CopyTestsFolder;

use App\Events\CopyTestsFolder\CopyTestsFolderEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CopyTestsFolderListener
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
    public function handle(CopyTestsFolderEvent $event): void
    {
        //
    }
}
