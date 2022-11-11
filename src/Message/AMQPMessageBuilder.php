<?php declare(strict_types=1);

namespace AMQPRouter\Message;

use Swoole\Coroutine;

class AMQPMessageBuilder
{
    public function builder(array $data): array
    {
        if (!isset($data['queue'])) {
            throw new \Exception('请设置queue');
        } else if (!isset($data['action'])) {
            throw new \Exception('请设置action');
        } else if (!isset($data['data'])) {
            throw new \Exception('请设置data');
        }
        $data = [
            'uuid' => md5(uniqid() . microtime() . rand(1, 99999) . Coroutine::getCid(), true),
            'queue' => $data['queue'],
            'action' => $data['action'],
            'send_time' => date('Y-m-d H:i:s', time()),
            'receive_time' => null,
            'from' => $data['admin'] ?? '',
            'to' => 'queue',
            'data' => $data['data'],
        ];
        return $data;
    }
}
