<?php declare(strict_types=1);

namespace AMQPRouter\Context;

class QueueAttributeContext
{
    protected static array $attributes = [];

    public static function get(string $key): array
    {
        return self::$attributes[$key] ?? [];
    }


    /**
     * 非amqp模块，请不要随意修改
     * @param array $data
     * @return array
     */
    public static function set(string $key, array $data): array
    {
        if (!isset(self::$attributes[$key])) {
            self::$attributes[$key] = $data;
        }
        return self::$attributes[$key];
    }
}