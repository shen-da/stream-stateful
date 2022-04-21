<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server\Connection;

use Loner\Reactor\ReactorInterface;
use Loner\Stream\Communication\{CommunicationInterface, Receive, Stateful, StatefulInterface};
use Loner\Stream\{Protocol\ProtocolInterface, Server\StatefulServer};
use Loner\Stream\Stateful\{ConnectionEvent, Event\Close};
use Loner\Stream\Utils\Resources;

/**
 * 有状态的流服务端连接
 */
class Connection implements CommunicationInterface, StatefulInterface
{
    use Receive, Stateful, Resources {
        closeAll as baseCloseAll;
    }

    /**
     * 应用层协议
     *
     * @var ProtocolInterface|null
     */
    public readonly ?ProtocolInterface $protocol;

    /**
     * 事件反应器
     *
     * @var ReactorInterface
     */
    public readonly ReactorInterface $reactor;

    /**
     * 当前状态
     *
     * @var int
     */
    protected int $status = self::STATUS_CONNECTING;

    /**
     * @inheritDoc
     */
    protected function closeAll(): void
    {
        $this->baseCloseAll();
        $this->server->removeConnection($this->getId());
    }

    /**
     * @inheritDoc
     */
    protected function reactClear(): void
    {
        $this->delReadListener();
        $this->delWriteListener();
    }

    /**
     * @inheritDoc
     */
    protected function dispatchClear(): void
    {
        $this->eventDispatch(Close::class, $this);
    }

    /**
     * 初始化通信
     *
     * @param StatefulServer $server
     * @param resource|null $socket
     */
    public function __construct(public readonly StatefulServer $server, protected $socket)
    {
        $this->protocol = $this->server->getProtocol();
        $this->reactor = $this->server->reactor;
        $this->setSocket();
        $this->setReadListener($this->establish(...));
    }

    /**
     * 返回连接标识符
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id ??= (int)$this->socket;
    }

    /**
     * 事件分发
     *
     * @param string $event
     * @param mixed ...$arguments
     * @return void
     */
    protected function eventDispatch(string $event, mixed ...$arguments): void
    {
        $this->server->dispatch(ConnectionEvent::tryFrom($event), ...$arguments);
    }
}
