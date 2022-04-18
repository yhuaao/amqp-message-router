<?php

declare(strict_types=1);

namespace AMQPRouter\Router;


use AMQPRouter\Annotation\AMQPController;
use AMQPRouter\Annotation\AMQPMapping;
use AMQPRouter\Annotation\AMQPMiddleware;
use AMQPRouter\Context\AMQPContext;
use AMQPRouter\Exception\AMQPMessageHandleFailedException;
use AMQPRouter\Exception\IgnoreAMQPMessageException;
use AMQPRouter\Interface\AMQPMiddlewareInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;

/**
 * 注册树收集-路由
 * Class RouteCollector
 * @package App\Amqp\Router
 */
class RouteCollector
{
    /**
     * queue-action-from 映射的class-method
     * 收集到重复信息，抛出异常
     */
    public function queueActionFromMappingClassMethods(string $routeAMQP): array
    {
        $AMQPClassMethods = AMQPContext::get('AMQPClassMethods');
        if (empty($AMQPClassMethods) || !isset($AMQPClassMethods[$routeAMQP])) {
            $AMQPClassMethods = [];
            //可以通过容器制作关联树
//        $AMQPMethods = AnnotationCollector::getContainer();
//        dd($AMQPMethods);
//        string $class, string $annotation, $value
            $AMQPActions = AnnotationCollector::getMethodsByAnnotation(
                AMQPMapping::class
            );
            foreach ($AMQPActions as $k => $AMQPAction) {
                $AMQPClass = AnnotationCollector::getClassAnnotation($AMQPAction['class'],
                    AMQPController::class);
                $AMQPKEY = $AMQPClass->queue . '.' . $AMQPAction['annotation']->action
                    . '.' . $AMQPAction['annotation']->from;
                if (isset($AMQPClassMethods[$AMQPKEY])) {
                    throw new IgnoreAMQPMessageException(
                        $AMQPKEY . ' is exists,' . PHP_EOL .
                        'exist class is ' . $AMQPClassMethods[$AMQPKEY]['_c'] . ',' . PHP_EOL .
                        'method is ' . $AMQPClassMethods[$AMQPKEY]['_m'] . '.' . PHP_EOL .
                        'register class is ' . $AMQPAction['class'] . ',' . PHP_EOL .
                        'method is ' . $AMQPAction['method'] . PHP_EOL
                    );
                }
                $AMQPClassMethods[$AMQPKEY] = [
                    '_c' => $AMQPAction['class'],
                    '_m' => $AMQPAction['method'],
                ];
            }
            AMQPContext::set('AMQPClassMethods', $AMQPClassMethods);
        }
        //再次获取
//        $AMQPClassMethods = AMQPContext::get('AMQPClassMethods');
        return $AMQPClassMethods;
    }

    /**
     * 收集控制器对应的middleware
     * 收集到重复信息，抛出异常
     */
    public function controllerMiddlewares(string $controllerClassName, array $message): void
    {
        $middlewareClassAnnotation = AnnotationCollector::getClassesByAnnotation(AMQPMiddleware::class);
//通过控制器，获取middleware
        if (isset($middlewareClassAnnotation[$controllerClassName])) {
            $controllerMiddlewares = $middlewareClassAnnotation[$controllerClassName]->middlewares;
            if (is_array($controllerMiddlewares)) {
                //处理middleware数组,没有继承中间件接口，抛出异常
                foreach ($controllerMiddlewares as $controllerMiddleware) {
                    //反射
                    $middlewareRefl = ReflectionManager::reflectClass($controllerMiddleware);
                    if (!$middlewareRefl->implementsInterface(AMQPMiddlewareInterface::class)) {
                        throw new \Exception($controllerMiddleware . ' must be implements AMQPMiddlewareInterface');
                    }
                    //实例化,触发
                    try {
                        $result = make($controllerMiddleware)->handle($message);
                        if (!$result) {
                            throw new AMQPMessageHandleFailedException($controllerMiddleware . ' middleware failed');
                        }
                    } catch (\Exception $e) {
                        throw new AMQPMessageHandleFailedException($e->getMessage());
                    }
                }
            } else if (is_string($controllerMiddlewares)) {
                $controllerMiddleware = $controllerMiddlewares;
                //处理middleware字符串
                $middlewareRefl = ReflectionManager::reflectClass($controllerMiddleware);
                if (!$middlewareRefl->implementsInterface(AMQPMiddlewareInterface::class)) {
                    throw new \Exception($controllerMiddleware . ' must be implements AMQPMiddlewareInterface');
                }
                //实例化,触发
                try {
                    $result = make($controllerMiddleware)->handle($message);
                    if (!$result) {
                        throw new AMQPMessageHandleFailedException($controllerMiddleware . ' middleware failed');
                    }
                } catch (\Exception $e) {
                    throw new AMQPMessageHandleFailedException($e->getMessage());
                }
            }
        }

    }

    /**
     * 收集控制器-method 对应的middleware
     * 收集到重复信息，抛出异常
     */
    public function methodMiddlewares(string $controllerClassName, string $actionMethodName, array $message): void
    {
        $middlewareMethodAnnotation = AnnotationCollector::getMethodsByAnnotation(AMQPMiddleware::class);
        $classmethodArr = [];
        foreach ($middlewareMethodAnnotation as $middlewareM) {
            $classMethodIndex = $middlewareM['class'] . '.' . $middlewareM['method'];
            $classmethodArr[$classMethodIndex] = $middlewareM['annotation']->middlewares;
        }
        //获取methodMiddleware
        if (isset($classmethodArr[$controllerClassName . '.' . $actionMethodName])) {
            $methodMiddlewares = $classmethodArr[$controllerClassName . '.' . $actionMethodName];
            //判断，顺序执行method-middleware
            if (is_array($methodMiddlewares)) {
                //处理middleware数组,没有继承中间件接口，抛出异常
                foreach ($methodMiddlewares as $methodMiddleware) {
                    //反射
                    $middlewareRefl = ReflectionManager::reflectClass($methodMiddleware);
                    if (!$middlewareRefl->implementsInterface(AMQPMiddlewareInterface::class)) {
                        throw new IgnoreAMQPMessageException($methodMiddleware . ' must be implements AMQPMiddlewareInterface');
                    }
                    //实例化,触发
                    try {
                        $result = make($methodMiddleware)->handle($message);
                        if (!$result) {
                            throw new AMQPMessageHandleFailedException($methodMiddleware . ' middleware failed');
                        }
                    } catch (\Exception $e) {
                        throw new AMQPMessageHandleFailedException($e->getMessage());
                    }
                }
            } else if (is_string($methodMiddlewares)) {
                $methodMiddleware = $methodMiddlewares;
                //处理middleware字符串
                $middlewareRefl = ReflectionManager::reflectClass($methodMiddleware);
                if (!$middlewareRefl->implementsInterface(AMQPMiddlewareInterface::class)) {
                    throw new IgnoreAMQPMessageException($methodMiddleware . ' must be implements AMQPMiddlewareInterface');
                }
                //实例化,触发
                try {
                    $result = make($methodMiddleware)->handle($message);
                    if (!$result) {
                        throw new AMQPMessageHandleFailedException($methodMiddleware . ' middleware failed');
                    }
                } catch (\Exception $e) {
                    throw new AMQPMessageHandleFailedException($e->getMessage());
                }
            }
        }

    }


}
