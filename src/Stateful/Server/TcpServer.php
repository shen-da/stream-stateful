<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server;

use Loner\Stream\Stateful\Server\Connection\TcpConnection;

/**
 * TCP 流服务端
 */
class TcpServer extends BaseTcpServer
{
    /**
     * @inheritDoc
     */
    protected function createConnection($socket): TcpConnection
    {
        return new TcpConnection($this, $socket);
    }
}
