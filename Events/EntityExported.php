<?php

namespace Exports\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class EntityExported implements ShouldBroadcast
{
    /**
     * The notifable entity who received the remind.
     *
     * @var mixed
     */
    public $notifable;

    /**
     * The notification instance.
     *
     * @var \Illuminate\Notifications\Notification
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  mixed $notifable
     * @param  \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function __construct($notifable, array $data)
    {
        $this->notifable = $notifable;
        $this->data = $data;

    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return ['private-' . $this->channelName()];
    }

    /**
     * Get the data that should be sent with the broadcasted event.
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }

    /**
     * Get the broadcast channel name for the event.
     */
    protected function channelName(): string
    {
        $class = str_replace('\\', '.', get_class($this->notifable));

        return $class . '.' . $this->notifable->getKey();
    }
}
