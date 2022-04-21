<?php

declare(strict_types=1);

namespace Loner\Stream\Stateful\Client;

use Loner\Stream\{Client\Networked, Client\StatefulClient, Site\Tcp};

/**
 * TCP 流客户端
 */
class TcpClient extends StatefulClient
{
    use Networked, Tcp;
}
