<?php

declare(strict_types=1);

namespace Loner\Stream\Site;

/**
 * TCP 相关
 */
trait Tcp
{
    /**
     * @inheritDoc
     */
    public static function transport(): string
    {
        return 'tcp';
    }

    /**
     * @inheritDoc
     */
    protected function setSocket(): void
    {
        if (extension_loaded('sockets')) {
            // 转化底层 socket
            $socket = socket_import_stream($this->socket);
            // 开启连接状态检测（若套接字未响应检测信息，断开连接并用SIGPIPE信号通知进程）
            socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            // 禁用 tcp 的 Nagle（0.2秒内小包缓存拼接，共用40字节包头） 算法，强制数据立即发送
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        }
        parent::setSocket();
    }
}
