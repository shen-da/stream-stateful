<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\{Client\StatefulClient, Stateful\Server\Connection\Connection};

/**
 * 有状态的连接端事件：发送缓冲区满
 */
class SendBufferFull
{
    public function __construct(public readonly StatefulClient|Connection $connection)
    {
    }
}