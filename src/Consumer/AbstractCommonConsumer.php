<?php declare(strict_types=1);

namespace AMQPRouter\Consumer;


use AMQPRouter\Context\QueueAttributeContext;
use AMQPRouter\Message\ParseMessage;
use AMQPRouter\Router\DispatcherFactory;
use App\Job\MailerJob;
use App\Service\Amqp\AmqpServiceProducer;
use App\Util\QueueUtil;
use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Producer;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;
use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractCommonConsumer extends ConsumerMessage
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


    public function test($message)
    {
//        //判断来源
//        $count = $this->getRetry($message);
//        dump(get_called_class());
//        dump('start:::::' . date('Y-m-d H:i:s', time()));
//        dump('retry:' . $count);
//        //逻辑处理
//        if ($count > $this->maxRetry) {
//            return Result::DROP;  //  failed队列
//        }
//        //设置shijian
//        $data = $this->setReceiveTime($data);
//        //第二次处理，失败丢进重试队列
//        try {
//            $this->handle($data);
//            $data['queue_name'] = $this->queue;
//            $this->amqpProducerService->writeLog($data, true);
//            //打印结构
//            dump(env('APP_ENV') . '_结果：True' . '_类名：' . get_called_class() . '_数据uuid：' . $data['uuid'] ?? '');
//        } catch (\Exception $e) {
//            //打印结构
//            $error = env('APP_ENV') . '_结果：False' . '_类名：' . get_called_class() .
//                '_数据uuid：' . $data['uuid'] . '_异常：' . $e->getMessage();
//            dump($error);
//            //丢进重试队列
//            if ($count >= $this->maxRetry) {
//                dump('sendQueueManageEmail');
//                //重试队列消费失败后,丢进失败队列,并且发送邮件
//                $this->sendQueueManageEmail($error);
//                return Result::DROP;  // failed队列
//            }
//            $count += 1;
//            //将异常信息保存到$data中
//            $data = $this->saveExceptionToData($data, $e->getMessage(), $count);
//            $result = app()->get(Producer::class)->produce(new $this->retryProducer($data, $count), true, 3);
//            if ($result) {
//                $this->amqpProducerService->writeLog($data, false, 'retry');
//                return Result::ACK;
//            } else {
//                //进入死信队列,记录失败日志
////                $this->amqpProducerService->writeLog($data, false, 'retry');
//                return Result::DROP; //failed队列
//            }
//        }
//        //处理成功消费消息
//        return Result::ACK;
    }

    /**
     * 异常保存到data中
     * @param array $data
     * @param string $ex
     * @param int $retry
     * @return array
     */
    protected function saveExceptionToData(array $data, string $ex, int $retry): array
    {
//        $data['error'] = ($data['error'] ?? '') . 'START:' . $ex . ':END' . PHP_EOL;
//        $data['queue_name'] = $this->queue;
//        $data['retry'] = $retry;
//        return $data;
    }

    /**
     * 获取消息来源
     * @param array $data
     * @return string
     */
    protected function getSource(array $data): string
    {
        return strtoupper($data['from']);
    }

    /**
     * 获取重试次数
     * @param AMQPMessage $message
     * @return int
     */
    protected function getRetry(AMQPMessage $message): int
    {
        if (!$message->has('application_headers')) {
            return 0;
        }
        $header = $message->get('application_headers');
        $properties = $header->getNativeData() ?? null;
        if (empty($properties)) {
            return 0;
        }
        $count = $properties['retry'] ?? 0;
        return $count;
    }

    /**
     * 设置死信队列-交换机和路由
     * @return QueueBuilder
     */
    public function getQueueBuilder(): QueueBuilder
    {
        $result = (new QueueBuilder())->setQueue($this->getQueue())
            //              'x-message-ttl'             => ['I', 10 * 1000],
            ->setArguments([
                'x-dead-letter-exchange'    => ['S', $this->exchange],
                'x-dead-letter-routing-key' => ['S', $this->queue . '.Failed'],
            ]);
        return $result;
    }

    /**
     * 设置接收时间
     * @param $data
     * @return array
     */
    protected function setReceiveTime($data): array
    {
        $data['receive_time'] = date('Y-m-d H:i:s', time());
        return $data;
    }


}