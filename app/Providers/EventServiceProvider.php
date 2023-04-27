<?php

namespace App\Providers;

use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Events\CloneRepository\CloneRepositoryListener;
use App\Events\UnzipZipFiles\UnzipZipFilesEvent;
use App\Events\UnzipZipFiles\UnzipZipFilesListener;
use App\Events\ExamineFolderStructure\ExamineFolderStructureEvent;
use App\Events\ExamineFolderStructure\ExamineFolderStructureListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CloneRepositoryEvent::class => [
            CloneRepositoryListener::class,
        ],
        UnzipZipFilesEvent::class => [
            UnzipZipFilesListener::class,
        ],
        ExamineFolderStructureEvent::class => [
            ExamineFolderStructureListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
