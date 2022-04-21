<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server;

use Loner\Stream\Stateful\Server\Connection\SslConnection;

/**
 * SSL 流服务端
 */
class SslServer extends BaseTcpServer
{
    /**
     * @inheritDoc
     */
    protected function createConnection($socket): SslConnection
    {
        return new SslConnection($this, $socket);
    }
}
