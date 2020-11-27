<?php

namespace Driver\Coin;

use App\Constants\ErrorCode;

class CoinFactory
{
	public function __construct()
	{
	}
	
	/**
	 * 构造适配器
	 * @param  $adapterName 模块code
	 * @param  $adapterConfig 模块配置
	 */
	public function setAdapter(string $adapterName, array $adapterConfig = [])
	{
		if (empty($adapterName))
			throw new CoinException(ErrorCode::ADAPTER_EMPTY);

		$class_file = BASE_PATH . '/app/Library/Driver/Coin/' . $adapterName . '/Service.php';
		if (!file_exists($class_file))
			throw new CoinException(ErrorCode::FILE_NOT_FOUND);

		$class = "\Driver\Coin\\$adapterName\Service";
		$this->adapter_instance = new $class($adapterConfig);
		return $this->adapter_instance;
	}

	public function __call($method_name, $method_args)
	{
		if (method_exists($this, $method_name))
			return call_user_func_array(array(&$this, $method_name), $method_args);
		elseif (
			!empty($this->adapter_instance)
			&& ($this->adapter_instance instanceof AbstractService)
			&& method_exists($this->adapter_instance, $method_name)
		)
			return call_user_func_array(array(&$this->adapter_instance, $method_name), $method_args);
	}
}
