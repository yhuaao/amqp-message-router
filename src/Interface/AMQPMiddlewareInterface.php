<?php declare(strict_types=1);

namespace AMQPRouter\Interface;


interface AMQPMiddlewareInterface
{
    public function handle(array $message): bool;

}