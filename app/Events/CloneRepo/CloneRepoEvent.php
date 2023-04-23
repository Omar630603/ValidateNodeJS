<?php

namespace App\Events\CloneRepo;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CloneRepoEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $submissionId;
    public $repoUrl;
    public $tempDir;
    /**
     * Create a new event instance.
     */
    public function __construct($submissionId, $repoUrl, $tempDir)
    {
        $this->submissionId = $submissionId;
        $this->repoUrl = $repoUrl;
        $this->tempDir = $tempDir;
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
