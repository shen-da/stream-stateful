<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\{Client\StatefulClient, Stateful\Server\Connection\Connection};
use Stringable;

/**
 * 有状态的连接端事件：收到远程的完整消息
 */
class Message
{
    public function __construct(public readonly StatefulClient|Connection $connection, public readonly Stringable|string $message)
    {
    }
}