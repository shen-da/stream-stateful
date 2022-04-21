<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server\Connection;

use Loner\Stream\Communication\NetworkAddress;
use Loner\Stream\Stateful\Server\BaseTcpServer;
use Stringable;

/**
 * TCP 流服务端连接
 *
 * @property BaseTcpServer $server
 */
class TcpConnection extends Connection
{
    use NetworkAddress;

    /**
     * 心跳时间
     *
     * @var float
     */
    private float $heartbeatTime = 0;

    /**
     * 记录心跳时间
     *
     * @return void
     */
    public function setHeartbeatTime(): void
    {
        $this->heartbeatTime = microtime(true);
    }

    /**
     * 获取心跳时间
     *
     * @return float
     */
    public function getHeartbeatTime(): float
    {
        return $this->heartbeatTime;
    }
}
