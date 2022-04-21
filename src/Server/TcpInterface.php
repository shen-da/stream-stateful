<?php

declare(strict_types=1);

namespace Loner\Stream\Server;

/**
 * TCP 相关
 */
interface TcpInterface
{
    /**
     * 默认心跳（收到新的完整请求）检测时间间隔（秒）
     */
    public const DEFAULT_KEEPALIVE_POLLING_INTERVAL = 10;

    /**
     * 默认心跳（收到新的完整请求）时间间隔上限（秒）
     */
    public const DEFAULT_KEEPALIVE_TIMEOUT = 55;
}
