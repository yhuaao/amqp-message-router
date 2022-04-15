<?php declare(strict_types=1);

namespace AMQPRouter\Annotation;



use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Annotation\Consumer as ConsumerAnnotation;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Attribute;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Utils\Str;
use PhpAmqpLib\Exception\AMQPRuntimeException;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AMQPController extends AbstractAnnotation
{
    /**
     * 队列
     * @var string
     */
    public string $queue;


    public function __construct(...$value)
    {
        parent::__construct(...$value);
        //检测注解是否冲突
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