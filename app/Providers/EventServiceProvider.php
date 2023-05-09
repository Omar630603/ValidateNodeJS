<?php

namespace App\Providers;

use App\Events\CloneRepository\CloneRepositoryEvent;
use App\Events\CloneRepository\CloneRepositoryListener;
use App\Events\UnzipZipFiles\UnzipZipFilesEvent;
use App\Events\UnzipZipFiles\UnzipZipFilesListener;
use App\Events\ExamineFolderStructure\ExamineFolderStructureEvent;
use App\Events\ExamineFolderStructure\ExamineFolderStructureListener;
use App\Events\AddEnvFile\AddEnvFileEvent;
use App\Events\AddEnvFile\AddEnvFileListener;
use App\Events\ReplacePackageJson\ReplacePackageJsonEvent;
use App\Events\ReplacePackageJson\ReplacePackageJsonListener;
use App\Events\CopyTestsFolder\CopyTestsFolderEvent;
use App\Events\CopyTestsFolder\CopyTestsFolderListener;
use App\Events\NpmInstall\NpmInstallEvent;
use App\Events\NpmInstall\NpmInstallListener;
use App\Events\NpmRunStart\NpmRunStartEvent;
use App\Events\NpmRunStart\NpmRunStartListener;
use App\Events\NpmRunTests\NpmRunTestsEvent;
use App\Events\NpmRunTests\NpmRunTestsListener;
use App\Events\DeleteTempDirectory\DeleteTempDirectoryEvent;
use App\Events\DeleteTempDirectory\DeleteTempDirectoryListener;
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
        AddEnvFileEvent::class => [
            AddEnvFileListener::class,
        ],
        ReplacePackageJsonEvent::class => [
            ReplacePackageJsonListener::class,
        ],
        CopyTestsFolderEvent::class => [
            CopyTestsFolderListener::class,
        ],
        NpmInstallEvent::class => [
            NpmInstallListener::class,
        ],
        NpmRunStartEvent::class => [
            NpmRunStartListener::class,
        ],
        NpmRunTestsEvent::class => [
            NpmRunTestsListener::class,
        ],
        DeleteTempDirectoryEvent::class => [
            DeleteTempDirectoryListener::class,
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
