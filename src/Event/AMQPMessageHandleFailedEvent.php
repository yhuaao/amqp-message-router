<?php declare(strict_types=1);

namespace AMQPRouter\Event;

use Hyperf\Event\EventDispatcher;
use Exception;

class AMQPMessageHandleFailedEvent
{
    public array $data = [];

    public Exception $e;

    public function __construct(array $data, Exception $e)
    {
        $this->data = $data;
        $this->e = $e;
    }
}