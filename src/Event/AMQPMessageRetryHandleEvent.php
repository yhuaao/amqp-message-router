<?php declare(strict_types=1);

namespace AMQPRouter\Event;

use Hyperf\Event\EventDispatcher;

class AMQPMessageRetryHandleEvent
{
    public array $data = [];


    public function __construct(array $message)
    {
        $this->data = $message;
    }
}