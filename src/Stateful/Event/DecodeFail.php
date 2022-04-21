<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\{Client\StatefulClient, Exception\DecodedException, Stateful\Server\Connection\Connection};

/**
 * 有状态的连接端事件：解码失败（不符合应用层协议）
 */
class DecodeFail
{
    public function __construct(public readonly StatefulClient|Connection $connection, public readonly DecodedException $exception)
    {
    }
}
