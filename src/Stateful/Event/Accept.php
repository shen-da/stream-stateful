<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\Stateful\Server\Connection\Connection;

/**
 * 有连接状态的流服务端事件：接收流客户端连接
 */
class Accept
{
    public function __construct(public readonly Connection $connection)
    {
    }
}
