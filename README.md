<h1 align="center">币种服务</h1>

## 概述

币种服务是微服务就是一个独立的实体，它可以独立被部署，也可以作为一个操作系统进程存在。服务与服务之间存在隔离性，服务之间均通过网络调用进行通信，从而加强服务之间的隔离性，避免紧耦合。

该服务基于 Hyperf2.0 框架实现。通过 JSON RPC 轻量级的 RPC 协议标准，可自定义基于 HTTP 协议来传输，或直接基于 TCP 协议来传输方式实现服务注册。客户端只需要通过协议标准直接调用已封装好的方法即可。

适用范围：生成地址、查询余额、交易转账、查询最新区块、交易接收状态...

## 特点

1. 一套写法兼容数字货币中常用功能
1. 接口方法可可灵活增减

### 支持协议

- [x] 以太坊(ETH)协议
- [x] 波场(TRON)协议
- [ ] 比特(BTC)协议
- [ ] 柚子(EOS)协议

## 支持方法

- 生成地址 `newAddress(array $config, int $protocol)`
- 根据私钥得到地址 `privateKeyToAddress(string $privateKey, array $config, int $protocol)`
- 查询余额 `balance(string $address, array $config, int $protocol)`
- 交易转账 `transfer(string $from, string $to, string $amount, array $config, int $protocol)`
- 查询最新区块 `blockByNumber(int $blockNumber, array $config, int $protocol)`
- 交易接收信息 `transactionReceipt(string $txHash, array $config, int $protocol)`
- 交易接收状态 `receiptStatus(string $txHash, array $config, int $protocol)`

## 快速开始

### 环境要求

- PHP >= 7.2
- Swoole PHP 扩展 >= 4.5，并关闭了 `Short Name`
- Nginx
- Consul

### 部署

``` bash
# 克隆项目
git clone ...

# 进入根目录
cd {file}

# 安装扩展包
composer install

# 配置env文件
cp .env.example .env

# 运行 Consul（若已运行，忽略）
consul agent -dev -bind 127.0.0.1

# 运行
php bin/hyperf.php start
```

## 计划

- 支持 BTC 协议
- 支持 EOS 协议
- ...

## 脚本命令
| 命令行 | 说明 | crontab |
| :-----| :---- | :---- |
| composer install | 安装扩展包 | -- |
| php bin/hyperf.php server:watch | 热更新（仅测试用） | -- |
| php bin/hyperf.php swagger:gen -o ./public/swagger/ -f json | 生成swagger文档 | -- |