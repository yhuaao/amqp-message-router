<?php declare(strict_types=1);

namespace AMQPRouter\Event;

use Hyperf\Event\EventDispatcher;

class AMQPMessageRetryHandleFailedEvent
{
    public array $data = [];

    public \Exception $e;

    public function __construct(array $message, \Exception $e)
    {
        $this->data = $message;
        $this->e = $e;
    }
}