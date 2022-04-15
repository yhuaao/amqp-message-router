<?php declare(strict_types=1);

namespace AMQPRouter\Message;

use AMQPRouter\Event\AMQPMessageIsSuccessFulEvent;
use AMQPRouter\Exception\IgnoreAMQPMessageException;
use Hyperf\Utils\ApplicationContext;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\EventDispatcher\EventDispatcherInterface;

class ParseMessage
{

    /**
     * 判断 message 是否为数组
     */
    public static function isArrayMessage(mixed $data): void
    {
        if (!is_array($data)) {
            throw new IgnoreAMQPMessageException('this message is not array , must be ignored');
        }
    }


    /**
     *检测是否为可消费的消息
     */
    public static function isSuccessEd(array $data): void
    {
//        [
//            'uuid'   => '236d1fc7bcce4b7ea112745627976698',
//            'queue'  => 'SCIGO.User',
//            'from'   => 'CRM',
//            'action' => 'audit'
//        ];
        if (!isset($data['uuid'])) {
            throw new IgnoreAMQPMessageException('uuid is not exists');
        }

        if (!isset($data['queue'])) {
            throw new IgnoreAMQPMessageException('queue is not exists');
        }

        if (!isset($data['from'])) {
            throw new IgnoreAMQPMessageException('from is not exists');
        }

        if (!isset($data['action'])) {
            throw new IgnoreAMQPMessageException('action is not exists');
        }

        //分发事件
        ApplicationContext::getContainer()->get(EventDispatcherInterface::class)->dispatch(new AMQPMessageIsSuccessFulEvent($data));

    }

    /**
     * 检测消息中是否存在 queue ,赋值 queue
     * @param array $data
     * @param string $queue
     */
    public static function addMessageQueue(array &$data, string $queue): void
    {
        if (!isset($data['queue'])) {
            $data['queue'] = $queue;
        }
    }


    /**
     * 通过AMQPMessage 获取执行次数
     * @param AMQPMessage $message
     * @return int
     */
    public static function getRuntimeRetryNum(AMQPMessage $AMQPmessage): int
    {
        if (!$AMQPmessage->has('application_headers')) {
            return 0;
        }
        $header = $AMQPmessage->get('application_headers');
        $properties = $header->getNativeData() ?? null;
        if (empty($properties)) {
            return 0;
        }
        $retry_num = $properties['retry'] ?? 0;
        return $retry_num;
    }

    public static function appendErrorAttribute(array &$message, string $err)
    {
        if (isset($message['error'])) {
            $message['error'] .= '{' . $err . '}';
        } else {
            $message['error'] = '{' . $err . '}';
        }
    }

    public static function appendRetryAttribute(array &$message, int $retry_num)
    {
        $message['retry'] = $retry_num;
    }
}