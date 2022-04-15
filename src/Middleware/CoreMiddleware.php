<?php declare(strict_types=1);

namespace AMQPRouter\Middleware;

use AMQPRouter\Interface\AMQPMiddlewareInterface;

class CoreMiddleware implements AMQPMiddlewareInterface
{
    public function handle(array $message): bool
    {

        return true;
    }
}