<?php

declare(strict_types=1);

namespace Loner\Stream\Client;

use Loner\Stream\Communication\{Stateful, StatefulInterface};
use Loner\Stream\Stateful\{ClientEvent, ConnectionEvent, Event\Close, Event\ConnectFail};

/**
 * 有连接状态的流客户端
 */
abstract class StatefulClient extends Client implements StatefulInterface
{
    use Stateful {
        established as baseEstablished;
    }

    /**
     * 当前状态
     *
     * @var int
     */
    protected int $status = self::STATUS_INITIAL;

    /**
     * @inheritDoc
     */
    final protected static function flags(): int
    {
        return STREAM_CLIENT_ASYNC_CONNECT;
    }

    /**
     * @inheritDoc
     */
    protected function dispatchClear(): void
    {
        $this->eventDispatch(Close::class, $this);
        $this->eventListeners = [];
    }

    /**
     * @inheritDoc
     */
    protected function listening(): void
    {
        $this->status = self::STATUS_CONNECTING;
        $this->setWriteListener(function () {
            if (stream_socket_get_name($this->socket, true)) {
                $this->setSocket();
                $this->establish();
            } else {
                $this->eventDispatch(ConnectFail::class, $this);
                $this->close();
            }
        });
    }

    /**
     * @inheritDoc
     */
    protected function established(): void
    {
        $this->delWriteListener();
        $this->baseEstablished();
    }

    /**
     * 设置事件响应
     *
     * @param ConnectionEvent|ClientEvent $event
     * @param callable|null $listener
     * @return static
     */
    public function on(ConnectionEvent|ClientEvent $event, ?callable $listener): static
    {
        $this->eventListeners[$event->value] = $listener;
        return $this;
    }
}
