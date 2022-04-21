<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\{Client\StatefulClient, Stateful\Server\Connection\Connection};

/**
 * 有状态的连接端事件：发送失败
 */
class SendFail
{
    public const CODE_REMOTE_CLOSED = 1;

    public const CODE_BUFFER_IS_FULL = 2;

    public function __construct(public readonly StatefulClient|Connection $connection, public readonly int $code)
    {
    }
}
