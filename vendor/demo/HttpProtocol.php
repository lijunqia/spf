<?php
namespace demo;

use spf\Network\Protocol\Base;

class HttpProtocol extends Base
{
	/**
	 * 创建worker进程时,进行初始化,后续请求都可直接使用这里加载的资源
	 */
	function init()
	{
		//\Loader::get_instance()->set_include_path($this->config['root']);
	}

	/**
	 * 用户请求回调
	 *
	 * @param \swoole_http_request  $request
	 * @param \swoole_http_response $response
	 */
	function on_request(\swoole_http_request $request, \swoole_http_response $response)
	{
		new App($request, $response, $this->config);
	}
}