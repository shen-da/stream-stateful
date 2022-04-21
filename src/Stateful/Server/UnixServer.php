<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server;

use Loner\Reactor\ReactorInterface;
use Loner\Stream\{Server\StatefulServer, Site\Unix, Stateful\Server\Connection\Connection};

/**
 * UNIX 流服务端
 */
class UnixServer extends StatefulServer
{
    use Unix;

    /**
     * 初始化信息
     *
     * @param ReactorInterface $reactor
     * @param string $target
     * @param array $contextOptions
     */
    public function __construct(public readonly ReactorInterface $reactor, private string $target, array $contextOptions = [])
    {
        $this->contextualize($contextOptions);
        @unlink($this->target);
    }

    /**
     * @inheritDoc
     */
    protected function createConnection($socket): Connection
    {
        return new Connection($this, $socket);
    }

    /**
     * @inheritDoc
     */
    protected function closeSocket(): void
    {
        $this->closeSocket();
        @unlink($this->target);
    }
}
