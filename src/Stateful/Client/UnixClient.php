<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Client;

use Loner\Reactor\ReactorInterface;
use Loner\Stream\{Client\StatefulClient, Site\Unix};

/**
 * UNIX 流客户端
 */
class UnixClient extends StatefulClient
{
    use Unix;

    /**
     * 初始化信息
     *
     * @param ReactorInterface $reactor
     * @param string $target
     */
    public function __construct(public readonly ReactorInterface $reactor, private string $target)
    {
    }
}
