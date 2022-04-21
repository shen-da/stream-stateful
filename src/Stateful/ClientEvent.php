<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful;

use Loner\Stream\{Event\OpenFail, Stateful\Event\ConnectFail};

/**
 * 有连接状态的流客户端事件
 */
enum ClientEvent: string
{
    case OpenFail = OpenFail::class;
    case ConnectFail = ConnectFail::class;
}
