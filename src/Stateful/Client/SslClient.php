<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Client;

use Loner\Stream\Communication\SSL;

/**
 * SSL 流客户端
 */
class SslClient extends TcpClient
{
    use SSL;

    /**
     * @inheritDoc
     */
    protected static function cryptoType(): int
    {
        return STREAM_CRYPTO_METHOD_SSLv2_CLIENT | STREAM_CRYPTO_METHOD_SSLv23_CLIENT;
    }
}
