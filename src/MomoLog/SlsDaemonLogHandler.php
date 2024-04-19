<?php

namespace Ym\MomoLog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Container\ContainerInterface;
use Swoole\Timer;
use Ym\AliyunSls\ClientInterface;
use Ym\AliyunSls\LogItem;
use Swoole\Coroutine\Channel;

class SlsDaemonLogHandler extends AbstractProcessingHandler
{
    protected Channel $channel;
    public function __construct(int|string|Level $level ,ContainerInterface $containerInterface)
    {
        parent::__construct($level, true);
        $this->client = $containerInterface->get(ClientInterface::class);
        
    }
    
    protected ClientInterface $client;
    
 
    protected function write(LogRecord $record): void
    {
        SlsRecordContainer::push($record);
    }
    
}