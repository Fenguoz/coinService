<?php

namespace Driver\Coin;

abstract class AbstractService
{
	protected $config; //配置
	protected $message;
	protected $code;

	protected function error(int $code, string $message = null)
	{
		$this->code = $code;
		$this->message = $message;
		throw new CoinException($code, $message);
	}

	public function _notify($data = null)
	{
		return $data;
	}

	/* 获取新地址接口 */
	abstract public function newAddress();
	/* 获取余额接口 */
	abstract public function balance(string $address);
	/* 发起转账接口 */
	abstract public function transfer(string $from, string $to, string $amount, string $fromPrivateKey);
	/* 获取最新区块高度 */
	abstract public function blockNumber();
	/* 根据区块高度获取区块交易 */
	abstract public function blockByNumber(int $blockNumber);
	/* 根据交易hash获取交易信息 */
	abstract public function transactionReceipt(string $txHash);
	/* 交易接受状态 */
	abstract public function receiptStatus(string $txHash);
	/* 同步通知接口 */
	abstract public function _return();
	/* 异步通知接口 */
	// abstract public function _notify();
}
