<?php

declare(strict_types=1);

namespace AMQPRouter\Router;


use AMQPRouter\Exception\IgnoreAMQPMessageException;
use Hyperf\Di\ReflectionManager;

/**
 * 路由
 * Class RouteCollector
 * @package App\Amqp\Router
 */
class Router
{

    /**
     * 通过反射检测method是否存在
     * @param string $controllerClassName
     * @param string $actionMethodName
     * @throws IgnoreAMQPMessageException
     */
    public function existsMethod(string $controllerClassName, string $actionMethodName): void
    {
        if (!ReflectionManager::reflectClass($controllerClassName)->hasMethod($actionMethodName)) {
            throw new IgnoreAMQPMessageException($actionMethodName . ' method is not exists');
        }
    }

    /**
     * 检测class 是否存在
     * @param string $controllerClassName
     * @throws IgnoreAMQPMessageException
     */
    public function existsClass(string $controllerClassName): void
    {
        if (!class_exists($controllerClassName)) {
            throw new IgnoreAMQPMessageException($controllerClassName . ' class is not exists');
        }
    }

    /**
     * 获取运行的class
     * @param $route
     * @return string
     */
    public function routerRuntimeControllerClassName($route): string
    {
//        $controllerClassName = $route['_c'];
        return $route['_c'];
    }

    /**
     * 获取运行的method
     * @param $route
     * @return string
     */
    public function routerRuntimeActionMethodName($route): string
    {
//        $actionMethodName = $route['_m'];
        return $route['_m'];
    }


    public function getControllerClassName(array $AMQPClassMethods, string $routeAMQP)
    {

        if (!isset($AMQPClassMethods[$routeAMQP])) {
            throw new \Exception($routeAMQP . ' is not found');
        }

        $route = $AMQPClassMethods[$routeAMQP];
        //获取class method
        $controllerClassName = $route['_c'];
        $actionMethodName = $route['_m'];
        //反射检测是否存在method
        //检测文件是否存在
        if (!class_exists($controllerClassName)) {
            throw new IgnoreAMQPMessageException($controllerClassName . ' class is not exists');
        }

        if (!ReflectionManager::reflectClass($controllerClassName)->hasMethod($actionMethodName)) {
            throw new IgnoreAMQPMessageException($actionMethodName . ' method is not exists');
        }

    }


}
