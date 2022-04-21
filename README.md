## 有连接状态的流服务组件

用于构建有连接状态（基于 UNIX、TCP、SSL 协议）的流服务端及客户端

### 运行依赖

- php: ^8.1
- [loner/stream][1]: ^1.0

### 安装

```shell
composer require loner/stream-stateful
```

### 快速入门

* 服务端

  ```php
  #!/usr/bin/env php
  <?php
  
  declare(strict_types=1);
  
  use Loner\Reactor\Builder;
  use Loner\Stream\Stateful\{ConnectionEvent, Event\Message, ServerEvent};
  use Loner\Stream\Stateful\Server\{SslServer, TcpServer, UnixServer};
  use Loner\Stream\Event\{Start, Stop};
  
  // composer 自加载
  require_once __DIR__ . '/../vendor/autoload.php';
  
  // 事件轮询反应器
  $reactor = Builder::create();
  
  // 创建有连接状态的服务端
  // 1. 创建 Tcp 服务端，参数说明：事件轮询反应器、主机名、端口号、绑定上下文配置（默认空数组）、是否端口复用（默认 Linux >= 3.9）
  $server = new TcpServer($reactor, '0.0.0.0', 6957);
  // 2. 创建 SSL 服务端，参数同上
  //$server = new SslServer($reactor, '0.0.0.0', 6957, [
  //    'ssl' => [
  //        // 若是含私钥的 PEM 格式文件，则不需 local_pk；若是不含私钥的 crt 格式文件，需要 local_pk 指定私钥文件
  //        'local_cert' => '模拟本地证书文件路径',
  //        // 配合 local_cert 使用，补充独立的私钥文件路径
  //        'local_pk' => '模拟私钥文件路径',
  //        // 是否需要验证 SSL 证书，默认 true
  //        'verify_peer' => false,
  //        // 是否自签名证书，默认 false
  //        'allow_self_signed' => true
  //    ]
  //]);
  // 3. 创建 UNIX 服务端，参数说明：事件轮询反应器、监听地址（本地 sock 文件路径）、绑定上下文配置（默认空数组）
  //$server = new UnixServer($reactor, '/var/tmp/unix.sock');
  
  // 绑定事件响应
  $server
      // 服务器启动（创建监听网络后、进入事件循环前）
      ->on(ServerEvent::Start, function (Start $event): void {
          echo sprintf('Start: %s', $event->server->getSocketAddress()), PHP_EOL;
      })
      // 服务器停止（移除监听网络后、破坏事件循环前）
      ->on(ServerEvent::Stop, function (Stop $event): void {
          echo sprintf('Stop: %s', $event->server->getSocketAddress()), PHP_EOL;
      })
      // 收到客户端消息
      ->on(ConnectionEvent::Message, function (Message $event): void {
          echo sprintf('Message: %s', $event->message), PHP_EOL;
          $event->connection->send('Hi. I\'m the server.');
      });
  
  // 启动服务器
  $server->start();
  ```

* 客户端

  ```php
  #!/usr/bin/env php
  <?php
  
  declare(strict_types=1);
  
  use Loner\Reactor\{Builder, Timer\Timer};
  use Loner\Stream\Event\OpenFail;
  use Loner\Stream\Stateful\Client\{SslClient, TcpClient, UnixClient};
  use Loner\Stream\Stateful\{ClientEvent, ConnectionEvent};
  use Loner\Stream\Stateful\Event\{Close, ConnectFail, Establish, Message};
  
  require_once __DIR__ . '/../vendor/autoload.php';
  
  // 事件轮询处理器
  $reactor = Builder::create();
  
  // 创建客户端
  // 1. 创建 TCP 客户端，参数说明：事件轮询反应器、主机名、端口号、绑定上下文配置（默认空数组）
  $client = new TcpClient($reactor, '127.0.0.1', 6957);
  // 2. 创建 SSL 客户端，参数同上
  //$client = new SslClient($reactor, '127.0.0.1', 6957, [
  //    'ssl' => [
  //        // 包含本地的证书及私钥的 PEM 格式文件
  //        'local_cert' => '模拟本地证书文件路径',
  //        // local_cert 文件的密码
  //        'passphrase' => '模拟 local_cert 文件的密码',
  //        // 是否需要验证 SSL 证书，默认 true
  //        'verify_peer' => false,
  //        // 是否自签名证书，默认 false
  //        'allow_self_signed' => true
  //    ]
  //]);
  // 3. 创建 UNIX 客户端，参数说明：事件轮询反应器、监听地址（本地 sock 文件路径）
  //$client = new UnixClient($reactor, '/var/tmp/unix.sock');
  
  // 绑定事件响应
  $client
      // 打开到服务端的通信网络失败
      ->on(ClientEvent::OpenFail, function (OpenFail $event): void {
          echo sprintf('OpenFail: %s', $event->client->getSocketAddress()), PHP_EOL;
      })
      // 连接服务端失败
      ->on(ClientEvent::ConnectFail, function (ConnectFail $event): void {
          echo sprintf('ConnectFail: %s', $event->client->getSocketAddress()), PHP_EOL;
      })
      // 通信连接状态已稳定
      ->on(ConnectionEvent::Establish, function (Establish $event): void {
          $client = $event->connection;
          echo sprintf('Establish: %s', $client->getSocketAddress()), PHP_EOL;
  
          // 间隔 0.2s 发送 5 次消息，然后关闭连接
          $client->requestTimes = 0;
          $client->reactor->addTimer(0.2, function (Timer $timer) use ($client, &$times) {
              $client->send('Hi. I\'m a client.');
              if (++$client->requestTimes === 5) {
                  $client->reactor->delTimer($timer);
              }
          }, true);
      })
      // 收到服务端的消息
      ->on(ConnectionEvent::Message, function (Message $event): void {
          echo sprintf("Message: %s", $event->message), PHP_EOL;
          $client = $event->connection;
          if ($client->requestTimes === 5) {
              $client->close();
          }
      })
      // 通信网络已关闭
      ->on(ConnectionEvent::Close, function (Close $event): void {
          echo sprintf('Close: %s', $event->connection->getSocketAddress()), PHP_EOL;
      });
  
  // 监听网络
  $client->listen();
  
  // 进入事件轮询
  $reactor->loop();
  ```

### 继承有连接状态的流服务组件功能

继承【流服务基础组件】功能，详见【 [loner/stream][1] 】

补充说明：

* 有连接状态的服务端：Loner\Stream\StatefulServer

  ```php
  use Loner\Stream\{Exception\DecodedException, Server\StatefulServer};
  use Loner\Stream\Stateful\{ConnectionEvent, ServerEvent};
  use Loner\Stream\Stateful\Event\{Accept,
      Close,
      DecodeFail,
      Establish,
      Message,
      ReceiveBufferFull,
      SendBufferDrain,
      SendBufferFull,
      SendFail
  };
  use Loner\Stream\Event\{Start, Stop};

  /** @var StatefulServer $server */

  // 连接配置
  $server->setConnection([
      // 'readBufferSize' => 65535,                  // 最大读取缓冲，默认 65535
      // 'maxReceiveBufferSize' => 10 << 10 << 10,   // 接收缓冲区上限，默认 10M；仅有应用层协议时生效
      // 'maxSendBufferSize' => 1 << 10 << 10        // 发送缓冲区上限，默认 1M
  ]);

  // 绑定事件响应
  // $server->on(ServerEvent|ConnectionEvent $event, ?callable $listener): static;
  $server
      // 服务器启动（创建监听网络后、进入事件循环前）
      ->on(ServerEvent::Start, function (Start $event): void {
          // 当前服务端
          $server = $event->server;

          // 业务代码
      })
      // 服务器停止（移除监听网络后、破坏事件循环之前）
      ->on(ServerEvent::Stop, function (Stop $event): void {
          // 当前服务端
          $server = $event->server;

          // 业务代码
      })
      // 接收客户端连接
      ->on(ServerEvent::Accept, function (Accept $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;

          // 业务代码
      })
      // 服务端（接收的）连接已稳定
      ->on(ConnectionEvent::Establish, function (Establish $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;

          // 业务代码
      })
      // 服务端连接收到完整消息
      ->on(ConnectionEvent::Message, function (Message $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;
          /** @var Stringable|string $message 有应用层协议时，为消息实体；否则为接收数据字符串 */
          $message = $event->message;

          // 业务代码
      })
      // 服务端连接关闭
      ->on(ConnectionEvent::Close, function (Close $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;

          // 业务代码
      })
      // 服务端连接接收缓冲区满（仅当有应用层协议时生效）
      ->on(ConnectionEvent::ReceiveBufferFull, function (ReceiveBufferFull $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;

          // 业务代码
      })
      // 服务端连接接收数据包解码消息失败（仅当有应用层协议时生效）
      ->on(ConnectionEvent::DecodeFail, function (DecodeFail $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;
          /** @var DecodedException $exception 消息解码异常 */
          $exception = $event->exception;

          // 业务代码
      })
      // 服务端连接发送缓冲区满（同步发送时触发），仅作为通知，后续可能减少清空
      ->on(ConnectionEvent::SendBufferFull, function (SendBufferFull $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;

          // 业务代码
      })
      // 服务端连接发送缓冲区清空
      ->on(ConnectionEvent::SendBufferDrain, function (SendBufferDrain $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;

          // 业务代码
      })
      // 服务端连接发送数据失败
      ->on(ConnectionEvent::SendFail, function (SendFail $event): void {
          // 当前服务端连接
          $serverConnection = $event->connection;
          switch ($event->code) {
              // 发送缓冲区满（异步发送时触发），丢失当次要发送的消息
              case SendFail::CODE_BUFFER_IS_FULL:
                  // 业务代码1
                  break;
              // 远程关闭连接，丢失发送缓冲
              case SendFail::CODE_REMOTE_CLOSED:
                  // 业务代码2
                  break;
          }
      });
  ```

* 有状态的服务端连接：Loner\Stream\Stateful\Server\Connection\Connection

  ```php
  use Loner\Reactor\ReactorInterface;
  use Loner\Stream\Protocol\ProtocolInterface;
  use Loner\Stream\Stateful\Server\Connection\Connection;

  /** @var Connection $connection */

  // 所属服务端
  $server = $connection->server;

  /** @var ReactorInterface $reactor 事件轮询反应器 */
  $reactor = $connection->reactor;

  /** @var ProtocolInterface|null $protocol 应用层协议 */
  $protocol = $connection->protocol;

  // 直接修改最大读取缓冲，默认 65535；可读
  $connection->readBufferSize = 65535;
  // 直接修改接收缓冲区上限，默认 10M；仅有应用层协议时生效；可读
  $connection->maxReceiveBufferSize = 10 << 10 << 10;
  // 直接发送缓冲区上限，默认 1M；可读
  $connection->maxSendBufferSize = 1 << 10 << 10;

  // 接收请求数
  $requests = $connection->getRequests();
  // 读取字节数
  $readBytes = $connection->getReadBytes();
  // 写入字节数
  $writtenBytes = $connection->getWrittenBytes();

  // 开始（或继续）监听接收数据
  $connection->resumeReceive();
  // 停止（或暂停）监听接收数据
  $connection->pauseReceive();

  /** @var string $package */
  /** @var Stringable $message */
  /** @var string $remoteAddress */

  // 发送消息到客户端（返回消息是否发送完整，null 为异步发送中）
  // 1. 常规方式
  $connection->send($package);
  // 2. 存在应用层协议
  $connection->send($message);
  $connection->send((string)$message);

  // 关闭服务端连接
  // 1. 直接关闭
  $connection->close();
  // 2. 发送消息到服务端后关闭
  $connection->close($package);
  $connection->close($message);
  $connection->close((string)$message);
  ```

* 有连接状态的客户端：Loner\Stream\Client\StatefulClient

  ```php
  use Loner\Stream\Client\StatefulClient;
  use Loner\Stream\Event\OpenFail;
  use Loner\Stream\Exception\DecodedException;
  use Loner\Stream\Stateful\{ClientEvent, ConnectionEvent};
  use Loner\Stream\Stateful\Event\{Close,
      ConnectFail,
      DecodeFail,
      Establish,
      Message,
      ReceiveBufferFull,
      SendBufferDrain,
      SendBufferFull,
      SendFail
  };

  /** @var StatefulClient $client */

  // 直接修改最大读取缓冲，默认 65535；可读
  $client->readBufferSize = 65535;
  // 直接修改接收缓冲区上限，默认 10M；仅有应用层协议时生效；可读
  $client->maxReceiveBufferSize = 10 << 10 << 10;
  // 直接发送缓冲区上限，默认 1M；可读
  $client->maxSendBufferSize = 1 << 10 << 10;

  // 批量方式修改客户端默认配置
  $client->set([
      // 'readBufferSize' => 65535,                  // 最大读取缓冲，默认 65535
      // 'maxReceiveBufferSize' => 10 << 10 << 10,   // 接收缓冲区上限，默认 10M；仅有应用层协议时生效
      // 'maxSendBufferSize' => 1 << 10 << 10        // 发送缓冲区上限，默认 1M
  ]);

  // 接收请求数
  $requests = $client->getRequests();
  // 读取字节数
  $readBytes = $client->getReadBytes();
  // 写入字节数
  $writtenBytes = $client->getWrittenBytes();

  // 绑定事件响应
  // $client->on(ClientEvent|ConnectionEvent $event, ?callable $listener): static;
  $client
      // 开启通信网络失败
      ->on(ClientEvent::OpenFail, function (OpenFail $event): void {
          // 当前客户端
          $client = $event->client;
          // 错误信息
          $message = $event->message;
          // 错误码
          $code = $event->code;

          // 业务代码
      })
      // 连接服务端失败
      ->on(ClientEvent::ConnectFail, function (ConnectFail $event): void {
          // 当前客户端
          $client = $event->client;

          // 业务代码
      })
      // 通信连接已稳定
      ->on(ConnectionEvent::Establish, function (Establish $event): void {
          // 当前客户端
          $client = $event->connection;

          // 业务代码
      })
      // 收到服务端的完整消息
      ->on(ConnectionEvent::Message, function (Message $event): void {
          // 当前客户端
          $client = $event->connection;
          /** @var Stringable|string $message 有应用层协议时，为消息实体；否则为接收数据字符串 */
          $message = $event->message;

          // 业务代码
      })
      // 通信连接已关闭
      ->on(ConnectionEvent::Close, function (Close $event): void {
          // 当前客户端
          $client = $event->connection;

          // 业务代码
      })
      // 接收缓冲区满（仅当有应用层协议时生效）
      ->on(ConnectionEvent::ReceiveBufferFull, function (ReceiveBufferFull $event): void {
          // 当前客户端
          $client = $event->connection;

          // 业务代码
      })
      // 接收数据包解码消息失败（仅当有应用层协议时生效）
      ->on(ConnectionEvent::DecodeFail, function (DecodeFail $event): void {
          // 当前客户端
          $client = $event->connection;
          /** @var DecodedException $exception 消息解码异常 */
          $exception = $event->exception;

          // 业务代码
      })
      // 发送缓冲区满（同步发送时触发），仅作为通知，后续可能减少清空
      ->on(ConnectionEvent::SendBufferFull, function (SendBufferFull $event): void {
          // 当前客户端
          $client = $event->connection;

          // 业务代码
      })
      // 发送缓冲区清空
      ->on(ConnectionEvent::SendBufferDrain, function (SendBufferDrain $event): void {
          // 当前客户端
          $client = $event->connection;

          // 业务代码
      })
      // 发送数据失败
      ->on(ConnectionEvent::SendFail, function (SendFail $event): void {
          // 当前客户端
          $client = $event->connection;
          switch ($event->code) {
              // 发送缓冲区满（异步发送时触发），丢失当次要发送的消息
              case SendFail::CODE_BUFFER_IS_FULL:
                  // 业务代码1
                  break;
              // 服务端关闭连接，丢失发送缓冲
              case SendFail::CODE_REMOTE_CLOSED:
                  // 业务代码2
                  break;
          }
      });

  /** @var string $package */
  /** @var Stringable $message */
  /** @var string $remoteAddress */

  // 发送消息到客户端（返回消息是否发送完整，null 为异步发送中）
  // 1. 常规方式
  $client->send($package);
  // 2. 存在应用层协议
  $client->send($message);
  $client->send((string)$message);

  // 关闭服务端连接
  // 1. 直接关闭
  $client->close();
  // 2. 发送消息到服务端后关闭
  $client->close($package);
  $client->close($message);
  $client->close((string)$message);
  ```

### 补充说明

基于 UNIX 协议：

* UNIX 服务端：Loner\Stream\Stateful\Server\UnixServer
* UNIX 客户端：Loner\Stream\Stateful\Client\UnixClient

基于 TCP 协议：

* TCP 服务端：Loner\Stream\Stateful\Server\TcpServer

  ```php
  use Loner\Stream\Stateful\Server\TcpServer;
  
  /** @var TcpServer $server */
  
  // 监听主机地址
  $host = $server->getHost();
  // 监听端口号
  $port = $server->getPort();
  
  // 是否端口复用
  $reusable = $server->reusable();
  // 绑定上下文端口复用设置
  $server->reusePort();
  
  // 开启长连接检测（周期性检测，关闭超时无响应连接）：无响应超时秒数（默认 55）、检测周期秒数（默认 10）
  $server->onEkg(timeout: 75, interval: 15);
  
  // 关闭长连接检测
  $server->offEkg();
  ```

* TCP 服务端连接：Loner\Stream\Stateful\Server\Connection\TcpConnection

  ```php
  use Loner\Stream\Stateful\Server\Connection\TcpConnection;
  
  /** @var TcpConnection $connection */
  
  // 返回本地地址
  $connection->getLocalAddress();
  // 返回客户端地址
  $connection->getRemoteAddress();
  
  // 记录心跳时间（将当前时间作为准备接收下次消息的起始时间）
  $connection->setHeartbeatTime();
  // 获取心跳时间（上次收到消息时间）
  $heartbeatTime = $connection->getHeartbeatTime();
  ```

* TCP 客户端：Loner\Stream\Stateful\Client\TcpClient

  ```php
  use Loner\Stream\Stateful\Client\TcpClient;
  
  /** @var TcpClient $client */
  
  // 获取监听主机地址
  $host = $client->getHost();
  // 获取监听端口号
  $port = $client->getPort();
  
  // 返回本地地址
  $client->getLocalAddress();
  // 返回远程（服务端）地址
  $client->getRemoteAddress();
  ```

基于 SSL 协议（继承 TCP 协议）：

* SSL 服务端：Loner\Stream\Stateful\Server\SslServer
* SSL 服务端连接：Loner\Stream\Stateful\Server\Connection\SslConnection
* SSL 客户端：Loner\Stream\Stateful\Client\SslClient

[1]:https://github.com/shen-da/stream