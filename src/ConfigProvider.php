<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace AMQPRouter;


use Hyperf\Amqp\Listener\BeforeMainServerStartListener;
use Hyperf\Amqp\Listener\MainWorkerStartListener;
use Hyperf\Amqp\Packer\Packer;
use Hyperf\Utils\Packer\JsonPacker;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [

            ],
            'listeners' => [

            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
//            'publish' => [
//                [
//                    'id' => 'config',
//                    'description' => 'The config for amqp.',
//                    'source' => __DIR__ . '/../publish/amqp.php',
//                    'destination' => BASE_PATH . '/config/autoload/amqp.php',
//                ],
//            ],
        ];
    }
}
