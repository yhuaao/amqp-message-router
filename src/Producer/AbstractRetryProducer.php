<?php declare(strict_types=1);

namespace AMQPRouter\Producer;

use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * 死信队列 -- 的过期时间，将消息重新丢入消费队列中
 * Class AbstractRetryProducer
 * @package App\Amqp\Producer
 */
class AbstractRetryProducer extends ProducerMessage
{
    protected $type = Type::TOPIC;

    public function __construct($data, int $count = 1)
    {
        //设置头部属性
        $this->properties['application_headers'] = new AMQPTable(
            [
                'retry' => $count
            ]
        );
        $this->setExpiration($count);
        $this->payload = $data;
    }

    /**
     * 设置消息过期时间
     * @param int $count
     */
    protected function setExpiration(int $count)
    {
        if ($count == 1) {
            $this->properties['expiration'] = 5 * 1000;
        } else if ($count == 2) {
            $this->properties['expiration'] = 20 * 1000;
        }
    }
}