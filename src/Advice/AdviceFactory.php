<?php declare(strict_types=1);

namespace AMQPRouter\Advice;

class AdviceFactory
{

    protected AdviceInterface $driver;

    public function __contruct(AdviceInterface $advice)
    {
        $this->driver = $advice;
    }
//    public function advice()
//    {
//        $this->driver->advice();
//    }

    public function __invoke()
    {
        $this->driver->advice();
    }

}
