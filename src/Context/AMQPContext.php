<?php declare(strict_types=1);



namespace AMQPRouter\Context;

/**
 * 保存整理好的，amqp注解树
 * Class AMQPContext
 * @package App\Amqp\Context
 */
class AMQPContext
{
    private static $container;

    /**
     * @throws \TypeError
     */
    public static function get(string $key): array
    {
        return self::$container[$key] ?? [];
    }


    /**
     * 非amqp模块，请不要随意修改
     * @param array $data
     * @return array
     */
    public static function set(string $key, array $data): array
    {
        self::$container[$key] = $data;

        return self::$container[$key];
    }

}
