<?php

declare(strict_types=1);

namespace Loner\Stream\Communication;

use Loner\Stream\{Exception\DecodedException, Utils\Dataset};
use Loner\Stream\Stateful\Event\{DecodeFail,
    Establish,
    Message,
    ReceiveBufferFull,
    SendBufferDrain,
    SendBufferFull,
    SendFail
};
use Stringable;

/**
 * 有连接状态的
 *
 * @property int $readBufferSize        读取缓冲字节上限
 * @property int $maxReceiveBufferSize  接收缓冲区上限
 * @property int $maxSendBufferSize     发送缓冲区上限
 */
trait Stateful
{
    use Dataset;

    /**
     * 请求数
     *
     * @var int
     */
    private int $requests = 0;

    /**
     * 读字节数
     *
     * @var int
     */
    private int $readBytes = 0;

    /**
     * 写字节数
     *
     * @var int
     */
    private int $writtenBytes = 0;

    /**
     * 接收缓冲区
     *
     * @var string
     */
    private string $receiveBuffer = '';

    /**
     * 接收缓冲区
     *
     * @var string
     */
    private string $sendBuffer = '';

    /**
     * @inheritDoc
     */
    public static function dataset(): array
    {
        return [
            'readBufferSize' => static::DEFAULT_READ_BUFFER_SIZE,
            'maxReceiveBufferSize' => static::DEFAULT_MAX_RECEIVE_BUFFER_SIZE,
            'maxSendBufferSize' => static::DEFAULT_MAX_SEND_BUFFER_SIZE
        ];
    }

    /**
     * @inheritDoc
     */
    public function resumeReceive(): void
    {
        if ($this->status === self::STATUS_ESTABLISHED && $this->receiving === false) {
            $this->receiving = true;
            $this->setReadListener(fn() => $this->read());
            $this->read(false);
        }
    }

    /**
     * @inheritDoc
     */
    public function getRequests(): int
    {
        return $this->requests;
    }

    /**
     * @inheritDoc
     */
    public function getReadBytes(): int
    {
        return $this->readBytes;
    }

    /**
     * @inheritDoc
     */
    public function getWrittenBytes(): int
    {
        return $this->writtenBytes;
    }

    /**
     * @inheritDoc
     */
    public function send(Stringable|string $message): ?bool
    {
        if ($this->status !== self::STATUS_ESTABLISHED) {
            return false;
        }

        if ($this->sendBuffer === '') {
            return $this->write((string)$message);
        }

        if (strlen($this->sendBuffer) >= $this->maxSendBufferSize) {
            $this->eventDispatch(SendFail::class, $this, SendFail::CODE_BUFFER_IS_FULL);
            return false;
        }

        $this->sendBuffer .= $message;
        return null;
    }

    /**
     * @inheritDoc
     */
    public function close(Stringable|string $message = null): void
    {
        if ($this->status === self::STATUS_CONNECTING) {
            $this->destroy();
            return;
        }

        if ($this->status !== self::STATUS_ESTABLISHED) {
            return;
        }

        if ($message !== null) {
            $this->send($message);
        }

        $this->status = self::STATUS_CLOSING;

        $this->sendBuffer === '' ? $this->destroy() : $this->pauseReceive();
    }

    /**
     * 读监听操作，读取数据，尝试获取完整消息
     *
     * @param bool $checkEof
     */
    protected function read(bool $checkEof = true): void
    {
        $read = $this->readFromSocket();
        if ($read) {
            $this->readBytes += strlen($read);
            if ($this->protocol) {
                $this->receiveBuffer .= $read;
                $bufferSize = strlen($this->receiveBuffer);
                $packageSize = $this->protocol->input($this->receiveBuffer);
                if ($packageSize && $bufferSize >= $packageSize) {
                    $package = substr($this->receiveBuffer, 0, $packageSize);
                    $this->receiveBuffer = substr($this->receiveBuffer, $packageSize);
                    try {
                        $this->onMessage($this->protocol->decode($package));
                    } catch (DecodedException $e) {
                        $this->eventDispatch(DecodeFail::class, $this, $e);
                        $this->close();
                    }
                } elseif ($bufferSize >= $this->maxReceiveBufferSize) {
                    $this->eventDispatch(ReceiveBufferFull::class, $this);
                    $this->close();
                }
            } else {
                $this->onMessage($read);
            }
        } elseif ($checkEof && ($read === false || $this->socketInvalid())) {
            $this->destroy();
        }
    }

    /**
     * 写（监听）操作
     *
     * @param string|null $message 首次发送的消息，或者写监听操作
     * @return bool|null
     */
    protected function write(?string $message = null): ?bool
    {
        $isNew = $message !== null;

        $sendBuffer = $message ?? $this->sendBuffer;

        $len = $this->writeToSocket($sendBuffer);

        if ($len === false && $this->socketInvalid()) {
            $this->eventDispatch(SendFail::class, $this, SendFail::CODE_REMOTE_CLOSED);
            $this->destroy();
            return false;
        }

        $this->writtenBytes += $len;

        if ($len === strlen($sendBuffer)) {

            if (!$isNew) {
                // 清除写侦听
                $this->delWriteListener();

                $this->sendBuffer = '';
                $this->eventDispatch(SendBufferDrain::class, $this);

                // 处于关闭过程中，销毁通信网络
                if ($this->status === self::STATUS_CLOSING) {
                    $this->destroy();
                }
            }

            return true;
        }

        $this->sendBuffer = substr($sendBuffer, $len);

        if ($isNew) {
            if (strlen($this->sendBuffer) >= $this->maxSendBufferSize) {
                $this->eventDispatch(SendBufferFull::class, $this);
            }
            $this->setWriteListener(fn() => $this->write());
        }

        return null;
    }

    /**
     * 稳定通信连接
     */
    protected function establish(): void
    {
        $this->established();
    }

    /**
     * 通信连接稳定后处理
     */
    protected function established(): void
    {
        $this->status = self::STATUS_ESTABLISHED;
        $this->eventDispatch(Establish::class, $this);
        $this->resumeReceive();
    }

    /**
     * 消息事件响应
     *
     * @param Stringable|string $message
     * @return void
     */
    protected function onMessage(Stringable|string $message): void
    {
        ++$this->requests;
        $this->eventDispatch(Message::class, $this, $message);
    }

    /**
     * 主套接字设置
     */
    protected function setSocket(): void
    {
        // 非阻塞模式、兼容 hhvm 无缓冲
        stream_set_blocking($this->socket, false);
        stream_set_read_buffer($this->socket, 0);
    }

    /**
     * 读取数据
     *
     * @return bool|string
     */
    protected function readFromSocket(): bool|string
    {
        return fread($this->socket, $this->readBufferSize);
    }

    /**
     * 写入数据
     *
     * @param string $data
     * @return false|int
     */
    protected function writeToSocket(string $data): bool|int
    {
        return fwrite($this->socket, $data);
    }

    /**
     * 破坏连接
     */
    protected function destroy(): void
    {
        if ($this->status !== self::STATUS_CLOSED) {
            $this->receiving = false;
            $this->status = self::STATUS_CLOSED;
            $this->closeAll();
        }
    }
}
