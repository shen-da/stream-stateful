<?php

declare(strict_types=1);

namespace Loner\Stream\Communication;

use Loner\Stream\Utils\DatasetInterface;
use Stringable;

/**
 * 有状态的
 */
interface StatefulInterface extends DatasetInterface
{
    /**
     * 状态：初始
     */
    public const STATUS_INITIAL = 0;

    /**
     * 状态：连接中
     */
    public const STATUS_CONNECTING = 1;

    /**
     * 状态：已稳定
     */
    public const STATUS_ESTABLISHED = 2;

    /**
     * 状态：关闭中
     */
    public const STATUS_CLOSING = 3;

    /**
     * 状态：已关闭
     */
    public const STATUS_CLOSED = 4;

    /**
     * 最大读取缓冲
     */
    public const DEFAULT_READ_BUFFER_SIZE = (64 << 10) - 1;

    /**
     * 默认接收缓冲区上限（10M）
     */
    public const DEFAULT_MAX_RECEIVE_BUFFER_SIZE = 10 << 10 << 10;

    /**
     * 默认发送缓冲区上限（1M）
     */
    public const DEFAULT_MAX_SEND_BUFFER_SIZE = 1 << 10 << 10;

    /**
     * 返回请求数量
     *
     * @return int
     */
    public function getRequests(): int;
    /**
     * 返回读字节数
     *
     * @return int
     */
    public function getReadBytes(): int;

    /**
     * 返回写字节数
     *
     * @return int
     */
    public function getWrittenBytes(): int;

    /**
     * 发送信息
     *
     * @param Stringable|string $message
     * @return bool|null
     */
    public function send(Stringable|string $message): ?bool;

    /**
     * 关闭通信网络，释放资源；若指定发送消息，会在之前执行发送操作
     *
     * @param Stringable|string|null $message
     * @return void
     */
    public function close(Stringable|string $message = null): void;
}
