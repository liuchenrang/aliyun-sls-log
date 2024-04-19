<?php

namespace Ym\MomoLog;

use Monolog\LogRecord;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine\Channel;
use Swoole\Timer;
use Ym\AliyunSls\ClientInterface;
use Ym\AliyunSls\LogItem;

class SlsRecordContainer
{
    protected Channel $channel;
    protected static ?SlsRecordContainer $instance = null;
    protected ClientInterface $client;
    
    public static $isDebug = false;
    public int $groupCount = 0;
    
    public static function init(ContainerInterface $container, int $size, int $flushInterval, int $groupCount)
    {
        
        if (!self::$instance) {
            self::$instance = new SlsRecordContainer($container, $size, $flushInterval, $groupCount);
        }
        return self::$instance;
    }
    
    public static function push(LogRecord $item): bool
    {
        return self::$instance->push2($item);
    }
    
    public function push2($item)
    {
        $this->debug("push item log " . json_encode($item));
        if ($this->channel->isFull()) {
            return $this->writeSync($item);
        } else {
            return $this->channel->push($item);
        }
    }
    
    public function writeSync(LogRecord $item)
    {
        $logItem = $this->convertLogRecordToItem($item);
        return $this->client->putLogItems([$logItem]);
    }
    
    public static function stop()
    {
        return self::$instance->flushAll();
    }
    
    public function flushAll()
    {
        $groupSize = $this->channel->length() / $this->groupCount;
        for ($i = 0; $i < $groupSize; $i++) {
            $this->flushWrite($this->groupCount);
        }
    }
    
    protected function convertLogRecordToItem(LogRecord $record): LogItem
    {
        $contents = $record->toArray();
        if (isset($contents['datetime'])) {
            unset($contents['datetime']);
        }
        
        return new LogItem(time(), $contents);
    }
    
    public function __construct(ContainerInterface $containerInterface, int $channelSize, int $flushInterval, int $groupCount)
    {
        $this->groupCount = $groupCount;
        $this->client = $containerInterface->get(ClientInterface::class);
        $this->channel = new Channel($channelSize);
        
        Timer::tick($flushInterval, function (int $timer_id) {
            $this->flushAll();
        });
    }
    
    public function debug($msg)
    {
        if (self::$isDebug) {
            echo date("Y-m-d h:i:s") . $msg . " \r\n";
        }
    }
    
    public function flushWrite($groupCount)
    {
        $groups = [];
        
        for ($i = 0; $i < $groupCount; $i++) {
            $record = $this->channel->pop(1);
            if ($this->channel->errCode === SWOOLE_CHANNEL_TIMEOUT) {
                break;
            } else {
                $groups[] = $this->convertLogRecordToItem($record);
            }
        }
        if (count($groups) > 0) {
            $this->debug("flush logger");
            $this->client->putLogItems($groups);
        }
    }
    
}