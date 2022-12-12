<?php declare(strict_types=1);

namespace AMQPRouter\Router;


use AMQPRouter\Context\QueueAttributeContext;
use AMQPRouter\Event\AMQPMessageRetryHandleEvent;
use AMQPRouter\Event\AMQPMessageRetryHandleFailedEvent;
use AMQPRouter\Exception\IgnoreAMQPMessageException;
use AMQPRouter\Message\ParseMessage;
use Hyperf\Amqp\Producer;
use Hyperf\Amqp\Result;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

class Dispatched
{

    public function getQueue(array $message): string
    {
        return $message['queue'];
    }

    public function getFrom(array $message): string
    {
        return $message['from'];
    }

    public function getAction(array $message): string
    {
        return $message['action'];
    }

    public function routeAMQP(array $message): string
    {
        return $this->getQueue($message) . '.' . $this->getAction($message) . '.' . $this->getFrom($message);
    }


    public function msgMappingAMQPRouter(array $AMQPClassMethods, string $routeAMQP): array
    {
        if (!isset($AMQPClassMethods[$routeAMQP])) {
            throw new IgnoreAMQPMessageException($routeAMQP . ' is not register');
        }
        return $AMQPClassMethods[$routeAMQP];
    }


    /******************************分发异常********************************/

    public function dispatchedHandleFailedMessage(int $retry_num, array $message, \Exception $e): string
    {
        //获取队列的进程的上下文
        $queueAttributeContext = QueueAttributeContext::get($message['queue']);
        //先检测是否设置了重试消费者
        if (isset($queueAttributeContext['retryProducer']) && $queueAttributeContext['retryProducer'] != '') {
            //开始重试业务
            //执行次数达到重试次数-丢进失败队列
            if ($retry_num >= ($queueAttributeContext['maxRetry'] ?? 0)) {
                //重试结束，触发重试结束事件，在这里可以监听事件处理业务
                ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                    ->dispatch(new AMQPMessageRetryHandleFailedEvent($message, $e));
                return Result::DROP;  // failed队列
            }
            //将异常信息保存进message中
            ParseMessage::appendErrorAttribute($message, $e->getMessage());
            $retry_num++;
            ParseMessage::appendRetryAttribute($message, $retry_num);
            //开始进入重试队列逻辑 1:5 2:30 3:60
            $after_time = ParseMessage::getAfterExecTime($retry_num);
            $result = ApplicationContext::getContainer()->get(Producer::class)
                ->produce(new $queueAttributeContext['retryProducer']($message, $after_time)
                    , true, 3);
            //触发事件
            if ($result) {
                ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                    ->dispatch(new AMQPMessageRetryHandleEvent($message));
                return Result::ACK;
            }
        } else {
            //没有重试队列直接，监听重试失败队列
            ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                ->dispatch(new AMQPMessageRetryHandleFailedEvent($message, $e));
        }
        return Result::DROP;
    }


}
