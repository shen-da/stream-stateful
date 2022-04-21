<?php

declare(strict_types=1);

namespace Loner\Stream\Server;

use Loner\Stream\Stateful\{ConnectionEvent, Event\Accept, Server\Connection\Connection, ServerEvent};

/**
 * 有连接状态的流服务端
 */
abstract class StatefulServer extends Server
{
    /**
     * 连接列表
     *
     * @var Connection[]
     */
    protected array $connections = [];

    /**
     * 连接配置
     *
     * @var array
     */
    private array $connectionConfigurations = [];

    /**
     * 生成连接
     *
     * @param resource $socket
     * @return Connection
     */
    abstract protected function createConnection($socket): Connection;

    /**
     * @inheritDoc
     */
    final protected static function flags(): int
    {
        return STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
    }

    /**
     * @inheritDoc
     */
    protected function accept(): void
    {
        if (false !== $socket = stream_socket_accept($this->socket, 0, $remoteAddress)) {
            $connection = $this->createConnection($socket, $remoteAddress);
            $this->acceptConnection($connection);
        }
    }

    /**
     * @inheritDoc
     */
    protected function closeSocket(): void
    {
        foreach ($this->connections as $connection) {
            $connection->close();
        }
        parent::closeSocket();
    }

    /**
     * 设置事件响应
     *
     * @param ConnectionEvent|ServerEvent $event
     * @param callable|null $listener
     * @return static
     */
    public function on(ConnectionEvent|ServerEvent $event, ?callable $listener): static
    {
        $this->eventListeners[$event->value] = $listener;
        return $this;
    }

    /**
     * 通信连接的事件分发
     *
     * @param ConnectionEvent $event
     * @param mixed ...$arguments
     * @return void
     */
    public function dispatch(ConnectionEvent $event, mixed ...$arguments): void
    {
        $this->eventDispatch($event->value, ...$arguments);
    }

    /**
     * 移除连接
     *
     * @param int $id
     * @return void
     */
    public function removeConnection(int $id): void
    {
        unset($this->connections[$id]);
    }

    /**
     * 配置连接相关项
     *
     * @param array $options
     * @return void
     */
    public function setConnection(array $options): void
    {
        $this->connectionConfigurations = $options;
    }

    /**
     * 接收连接
     *
     * @param Connection $connection
     * @return void
     */
    protected function acceptConnection(Connection $connection): void
    {
        $connection->set($this->connectionConfigurations);
        $this->connections[$connection->getId()] = $connection;
        $this->eventDispatch(Accept::class, $connection);
    }
}
