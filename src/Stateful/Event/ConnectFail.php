<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Event;

use Loner\Stream\Client\StatefulClient;

/**
 * 有连接状态的流客户端事件：连接流服务端失败
 */
class ConnectFail
{
    public function __construct(public readonly StatefulClient $client)
    {
    }
}
