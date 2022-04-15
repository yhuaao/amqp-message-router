<?php declare(strict_types=1);

namespace AMQPRouter\Annotation;



use Hyperf\Amqp\Annotation\Consumer as ConsumerAnnotation;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Attribute;
use Hyperf\Di\Annotation\AnnotationCollector;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::TARGET_METHOD)]
class AMQPMapping extends AbstractAnnotation
{
    /**
     * action
     * @var string
     */
    public string $action;

    /**
     * from
     * @var string
     */
    public string $from = '';

    public function __construct(...$value)
    {
        parent::__construct(...$value);
        //检测控制器方法是否重复
//        echo 23312312312312312312323;
        //在这里检测消费者队列，不能随意的设置注解
        $classes = AnnotationCollector::getClassesByAnnotation(ConsumerAnnotation::class);
        $queueNames = [''];
        foreach ($classes as $class => $annotation) {
            $queueNames[] = $annotation->queue;
        }

//        if (!in_array($this->queue, $queueNames)) {
//            throw new AMQPRuntimeException($this->queue . ' is not exists___');
//        }
    }
}


