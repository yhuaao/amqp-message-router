<?php declare(strict_types=1);

namespace AMQPRouter\Annotation;


use Hyperf\Amqp\Annotation\Consumer as ConsumerAnnotation;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Attribute;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Annotation\Middleware;


#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class AMQPMiddleware extends AbstractAnnotation
{
    /**
     * action
     * @var string
     */
    public array|string $middlewares;

    /**
     * from
     * @var string
     */

    public function __construct(...$value)
    {
        parent::__construct(...$value);
//        $middlewares = [];
//        dump('__construct', $value);
//        if (is_array($value['middlewares'])) {
//            //数组
////            [
////                'middleware' => ['App\Amqp\Middleware\CoreMiddleware']
////            ];
//            foreach ($value['middlewares'] as $middlewareName) {
//                $middlewares[] = new AMQPMiddleware($middlewareName);
//            }
//        } else if (is_string($value['middlewares'])) {
////            [
////                'middleware' => 'App\Amqp\Middleware\CoreMiddleware'
////            ];
//            $middlewares[] = new AMQPMiddleware($value['middlewares']);
//        }
//        $value = ['value' => $middlewares];
//        $this->bindMainProperty('middlewares', $value);
    }
}


