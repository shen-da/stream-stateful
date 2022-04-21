<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\{Client\StatefulClient, Stateful\Server\Connection\Connection};

/**
 * 有状态的连接端事件：发送缓冲区清空
 */
class SendBufferDrain
{
    public function __construct(public readonly StatefulClient|Connection $connection)
    {
    }
}
