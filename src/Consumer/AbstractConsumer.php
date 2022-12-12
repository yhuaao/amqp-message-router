<?php declare(strict_types=1);

namespace AMQPRouter\Consumer;


use AMQPRouter\Context\QueueAttributeContext;
use AMQPRouter\Exception\IgnoreAMQPMessageException;
use AMQPRouter\Message\ParseMessage;
use AMQPRouter\Router\DispatcherFactory;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Utils\ApplicationContext;
use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractConsumer extends ConsumerMessage
{
    protected int $maxRetry = 3;
    protected bool $requeue = true;

    /**
     * mq-consumer-代码
     * @param $message
     * @param AMQPMessage $message
     * @return string
     */
    public function consumeMessage($message, AMQPMessage $AMQPmessage): string
    {
        ParseMessage::isArrayMessage($message);
        try {
            //数据分发
            $queue = $this->queue;
            ParseMessage::addMessageQueue($message, $queue);
            ParseMessage::isSuccessEd($message);
            //收集数据
            QueueAttributeContext::set($queue, [
                'maxRetry'      => $this->maxRetry ?? 0,
                'requeue'       => $this->requeue ?? '',
                'exchange'      => $this->exchange ?? '',
                'routingKey'    => $this->routingKey ?? '',
                'retryProducer' => $this->retryProducer ?? ''
            ]);
            //获取当前的协程id
            ApplicationContext::getContainer()->get(DispatcherFactory::class)->handle($message, $AMQPmessage);
            return Result::ACK;  //  failed队列
        } catch (\Exception $e) {
            return ApplicationContext::getContainer()->get(DispatcherFactory::class)
                ->handleFailed($message, $AMQPmessage, $e);
        }
    }

}