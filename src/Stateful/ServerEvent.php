<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful;

use Loner\Stream\{Event\Start, Event\Stop};
use Loner\Stream\Stateful\Event\Accept;

/**
 * 有连接状态的流服务端事件
 */
enum ServerEvent: string
{
    case Start = Start::class;
    case Stop = Stop::class;
    case Accept = Accept::class;
}
