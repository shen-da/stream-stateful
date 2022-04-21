<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server;

use Loner\Reactor\Timer\Timer;
use Loner\Stream\Server\{Networked, StatefulServer, TcpInterface};
use Loner\Stream\{Site\Tcp, Stateful\Server\Connection\TcpConnection};

/**
 * TCP 基础流服务端
 *
 * @property TcpConnection[] $connections
 */
abstract class BaseTcpServer extends StatefulServer implements TcpInterface
{
    use Networked, Tcp;

    /**
     * 心电图 ID
     *
     * @var Timer|null
     */
    private ?Timer $ekg = null;

    /**
     * 心跳监测
     *
     * @param int $timeout
     * @param int $interval
     * @return void
     */
    public function onEkg(int $timeout = self::DEFAULT_KEEPALIVE_TIMEOUT, int $interval = self::DEFAULT_KEEPALIVE_POLLING_INTERVAL): void
    {
        $this->ekg ??= $this->reactor->addTimer($interval, function () use (&$timeout) {
            $expire = time() - $timeout;
            foreach ($this->connections as $connection) {
                if ($connection->getHeartbeatTime() < $expire) {
                    $connection->close();
                }
            }
        }, true);
    }

    /**
     * 关闭心跳监测
     *
     * @return void
     */
    public function offEkg(): void
    {
        if ($this->ekg) {
            $this->reactor->delTimer($this->ekg);
            $this->ekg = null;
        }
    }
}
