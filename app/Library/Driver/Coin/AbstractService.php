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
		return $data ? $data : (($this->code == 0) ? true : false);
	}

	/* 获取新地址接口 */
	abstract public function newAddress();
	/* 获取余额接口 */
	abstract public function balance(string $address);
	/* 发起转账接口 */
	abstract public function transfer(string $from, string $to, string $number);
	/* 同步通知接口 */
	abstract public function _return();
	/* 异步通知接口 */
	// abstract public function _notify();
}
