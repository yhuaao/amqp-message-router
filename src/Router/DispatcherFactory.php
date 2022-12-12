<?php declare(strict_types=1);

namespace AMQPRouter\Router;


use AMQPRouter\Annotation\AMQPController;
use AMQPRouter\Annotation\AMQPMapping;
use AMQPRouter\Annotation\AMQPMiddleware;
use AMQPRouter\Context\AMQPContext;
use AMQPRouter\Event\AMQPMessageHandleFailedEvent;
use AMQPRouter\Event\AMQPMessageHandleSuccessEvent;
use AMQPRouter\Event\AMQPMessageIgnoreHandleEvent;
use AMQPRouter\Event\AMQPMessageRetryHandleFailedEvent;
use AMQPRouter\Exception\AMQPMessageHandleFailedException;
use AMQPRouter\Exception\IgnoreAMQPMessageException;
use AMQPRouter\Interface\AMQPMiddlewareInterface;
use AMQPRouter\Message\ParseMessage;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\ApplicationContext;
use PhpAmqpLib\Message\AMQPMessage;
use phpseclib3\Math\BigInteger\Engines\PHP;
use Psr\EventDispatcher\EventDispatcherInterface;
use Exception;

class DispatcherFactory
{


    /**
     * 处理消息
     * @param array $message
     * @param AMQPMessage $AMQPmessage
     * @throws \Exception
     */
    public function handle(array $message, AMQPMessage $AMQPmessage): void
    {
        //收取信息
        $dispatched = ApplicationContext::getContainer()->get(Dispatched::class);
//        $queue = $dispatched->getQueue($message);
//        $action = $dispatched->getAction($message);
//        $from = $dispatched->getFrom($message);
        $routeAMQP = $dispatched->routeAMQP($message);
//        throw new AMQPMessageHandleFailedException('开始业务崇拜你是');
        //获取routerCollector
        $routerCollector = ApplicationContext::getContainer()->get(RouteCollector::class);
        $AMQPClassMethods = $routerCollector->queueActionFromMappingClassMethods($routeAMQP);
        //消息分发
        $route = $dispatched->msgMappingAMQPRouter($AMQPClassMethods, $routeAMQP);
        //获取routerObj class method
        $routerAMQP = ApplicationContext::getContainer()->get(Router::class);

        $controllerClassName = $routerAMQP->routerRuntimeControllerClassName($route);
        $actionMethodName = $routerAMQP->routerRuntimeActionMethodName($route);
        $routerAMQP->existsClass($controllerClassName);
        $routerAMQP->existsMethod($controllerClassName, $actionMethodName);
        //执行控制器类 对应的 middleware
        $routerCollector->controllerMiddlewares($controllerClassName, $message);
        $routerCollector->methodMiddlewares($controllerClassName, $actionMethodName, $message);
        //通过方法触发middleware，这个需要整理 controller-method 的middleware
        try {
            ApplicationContext::getContainer()->get($controllerClassName)->$actionMethodName($AMQPmessage, $message);
            //消息处理成功
            ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                ->dispatch(new AMQPMessageHandleSuccessEvent($message));
        } catch (Exception $e) {
            if($e instanceof IgnoreAMQPMessageException){
                throw new IgnoreAMQPMessageException($e->getMessage());
            }
            throw new AMQPMessageHandleFailedException(
                $e->getMessage()
//                'file:' . $e->getFile()   .
//                'line:' . $e->getLine()
            );
        }
    }


    /**
     * 处理失败的消息分发
     */
    public function handleFailed(array $message, AMQPMessage $AMQPmessage, \Exception $e): string
    {
        //两种异常 - 忽略异常  - 重试异常
        switch (true) {
            case $e instanceof IgnoreAMQPMessageException:
                //忽略异常 - 消息格式不满足
                ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                    ->dispatch(new AMQPMessageIgnoreHandleEvent($message, $e));
                return Result::DROP;  //丢进失败队列
            case $e instanceof AMQPMessageHandleFailedException:
                ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                    ->dispatch(new AMQPMessageHandleFailedEvent($message, $e));
                $dispatched = ApplicationContext::getContainer()->get(Dispatched::class);
                $retry_num = ParseMessage::getRuntimeRetryNum($message);
                return $dispatched->dispatchedHandleFailedMessage($retry_num, $message, $e);
            case $e instanceof Exception:
                ApplicationContext::getContainer()->get(EventDispatcherInterface::class)
                    ->dispatch(new AMQPMessageIgnoreHandleEvent($message, $e));
                return Result::DROP;  //丢进失败队列
        }
        return Result::DROP;
    }

}
