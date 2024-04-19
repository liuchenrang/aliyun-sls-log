<?php

namespace Ym\MomoLog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Container\ContainerInterface;
use Ym\AliyunSls\ClientInterface;
use Ym\AliyunSls\LogItem;

class SlsMomoLogHandler extends AbstractProcessingHandler
{
    
    protected ClientInterface $client;
    public function __construct(int|string|Level $level ,ContainerInterface $containerInterface)
    {
        parent::__construct($level, true);
        $this->client = $containerInterface->get(ClientInterface::class);
       
    }

    
    protected function convertLogRecordToItem(LogRecord $record): LogItem
    {
        $contents = $record->toArray();
        if (isset($contents['datetime'])){
            unset($contents['datetime']);
        }
        
        return new LogItem(time(), $contents);
    }
    protected function write(LogRecord $record): void
    {
        
        $this->client->putLogItems([$this->convertLogRecordToItem($record)]);
        // TODO: Implement write() method.
    }
    
}