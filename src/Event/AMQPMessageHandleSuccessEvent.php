<?php declare(strict_types=1);

namespace AMQPRouter\Event;

use Hyperf\Event\EventDispatcher;

class AMQPMessageHandleSuccessEvent
{
    public $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}