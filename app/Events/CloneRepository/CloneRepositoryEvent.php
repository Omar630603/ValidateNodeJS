<?php

namespace App\Events\CloneRepository;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CloneRepositoryEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $submissionId;
    public $repoUrl;
    public $tempDir;
    public $command;
    /**
     * Create a new event instance.
     */
    public function __construct($submissionId, $repoUrl, $tempDir, $command)
    {
        $this->submissionId = $submissionId;
        $this->repoUrl = $repoUrl;
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
