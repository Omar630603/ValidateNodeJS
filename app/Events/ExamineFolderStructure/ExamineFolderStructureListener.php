<?php

namespace App\Events\ExamineFolderStructure;

use App\Events\ExamineFolderStructure\ExamineFolderStructureEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ExamineFolderStructureListener
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
    public function handle(ExamineFolderStructureEvent $event): void
    {
        //
    }
}
