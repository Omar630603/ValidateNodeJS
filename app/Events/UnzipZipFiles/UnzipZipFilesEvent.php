<?php

namespace App\Events\UnzipZipFiles;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnzipZipFilesEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $submission;
    public $zipFileDir;
    public $tempDir;
    public $command;
    /**
     * Create a new event instance.
     */
    public function __construct($submission, $zipFileDir, $tempDir, $command)
    {
        $this->submission = $submission;
        $this->zipFileDir = $zipFileDir;
        $this->tempDir = $tempDir;
        $this->command = $command;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
