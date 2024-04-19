AliYun SLS Log For Hyperf

# 配置 Hyper的logger 
```php
<?php
 
use Psr\Container\ContainerInterface;

return [
    'default' => [
        'handler' => [
//            'class' => Monolog\Handler\StreamHandler::class,
//            'class' => Ym\MomoLog\SlsMomoLogHandler::class,
            'class' => Ym\MomoLog\SlsDaemonLogHandler::class,
            'constructor' => [
                'level' => Monolog\Logger::DEBUG,
                "containerInterface"=> \Hyperf\Context\ApplicationContext::getContainer(),
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];

```

# 配置 WorkerStart 事件
```php

#[Listener]
class WorkerBeforeStartListener implements ListenerInterface
{
/**
* @var LoggerInterface
*/
private $logger;
private ContainerInterface $container;
public function __construct(ContainerInterface $container)
{
$this->logger = $container->get(LoggerFactory::class)->get('work exit');
$this->container = $container;
}

    public function listen(): array
    {
        return [
            BeforeWorkerStart::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        SlsRecordContainer::init($this->container,100, 4, 10);
        
    }
}

```
 
# 配置 WorkerExit 事件
``php

#[Listener]
class WorkerExitListener implements ListenerInterface
{
/**
* @var LoggerInterface
*/
private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class);
    }

    public function listen(): array
    {
        return [
            OnWorkerExit::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        SlsRecordContainer::stop();
    }
}

```