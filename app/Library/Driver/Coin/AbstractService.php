<?php

namespace Driver\Coin;

abstract class AbstractService
{
	protected $config; //配置
	protected $message;
	protected $code;
	protected $account;

	protected function error(int $code, string $message)
	{
		$this->code = $code;
		$this->message = $message;
		return $this;
	}

	public function _notify()
	{
		return ($this->code == 0) ? true : false;
	}

	/* 获取地址接口 */
	abstract public function address();
	/* 同步通知接口 */
	abstract public function _return();
	/* 异步通知接口 */
	// abstract public function _notify();
}
