<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful;

use Loner\Stream\Stateful\Event\{Close,
    DecodeFail,
    Establish,
    Message,
    ReceiveBufferFull,
    SendBufferDrain,
    SendBufferFull,
    SendFail
};

/**
 * 有状态接的事件
 */
enum ConnectionEvent: string
{
    case Establish = Establish::class;
    case DecodeFail = DecodeFail::class;
    case Message = Message::class;
    case ReceiveBufferFull = ReceiveBufferFull::class;
    case SendBufferDrain = SendBufferDrain::class;
    case SendBufferFull = SendBufferFull::class;
    case SendFail = SendFail::class;
    case Close = Close::class;
}
