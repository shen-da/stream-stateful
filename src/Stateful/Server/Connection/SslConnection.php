<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Server\Connection;

use Loner\Stream\Communication\SSL;

/**
 * SSL 流服务端连接
 */
class SslConnection extends TcpConnection
{
    use SSL;

    /**
     * @inheritDoc
     */
    protected static function cryptoType(): int
    {
        return STREAM_CRYPTO_METHOD_SSLv2_SERVER | STREAM_CRYPTO_METHOD_SSLv23_SERVER;
    }
}
