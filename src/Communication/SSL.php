<?php

declare(strict_types=1);

namespace Loner\Stream\Communication;

/**
 * SSL 相关
 */
trait SSL
{
    /**
     * 返回加密类型
     *
     * @return int
     */
    abstract protected static function cryptoType(): int;

    /**
     * @inheritDoc
     */
    protected function establish(): void
    {
        $result = $this->doSslHandshake();
        if ($result) {
            $this->established();
        } elseif ($result === false) {
            $this->destroy();
        }
    }

    /**
     * SSL 握手
     *
     * @return bool|null
     */
    protected function doSslHandshake(): ?bool
    {
        if (feof($this->socket)) {
            return false;
        }

        $result = stream_socket_enable_crypto($this->socket, true, static::cryptoType());
        return $result === 0 ? null : $result;
    }
}
