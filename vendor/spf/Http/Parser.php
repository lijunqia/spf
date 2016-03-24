<?php
namespace spf\Http;

class Parser
{
	const HTTP_EOF = "\r\n\r\n";

	static function parseParams($str)
	{
		$params = array();
		$blocks = explode(";", $str);
		foreach ($blocks as $b) {
			$_r = explode("=", $b, 2);
			if (count($_r) == 2) {
				list ($key, $value) = $_r;
				$params[trim($key)] = trim($value, "\r\n \t\"");
			} else {
				$params[$_r[0]] = '';
			}
		}
		return $params;
	}

	static function parseBody($request)
	{
		$cd = strstr($request->head['Content-Type'], 'boundary');
		if (isset($request->head['Content-Type']) and $cd !== false) {
			self::parseFormData($request, $cd);
		} else {
			parse_str($request->body, $request->post);
		}
	}

	/**
	 * 解析Cookies
	 *
	 */
	static function parseCookie($request)
	{
		$request->cookie = self::parseParams($request->head['Cookie']);
	}

	/**
	 * 解析form_data格式文件
	 *
	 * @param $part
	 * @param $request
	 * @param $cd
	 *
	 */
	static function parseFormData($request, $cd)
	{
		$cd = '--' . str_replace('boundary=', '', $cd);
		$form = explode($cd, rtrim($request->body, "-")); //去掉末尾的--
		foreach ($form as $f) {
			if ($f === '') {
				continue;
			}
			$parts = explode("\r\n\r\n", trim($f));
			$head = self::parseHeaderLine($parts[0]);
			if (!isset($head['Content-Disposition'])) {
				continue;
			}
			$meta = self::parseParams($head['Content-Disposition']);
			//filename字段表示它是一个文件
			if (!isset($meta['filename'])) {
				if (count($parts) < 2) {
					$parts[1] = "";
				}
				//支持checkbox
				if (substr($meta['name'], -2) === '[]') {
					$request->post[substr($meta['name'], 0, -2)][] = trim(
						$parts[1]
					);
				} else {
					$request->post[$meta['name']] = trim($parts[1], "\r\n");
				}
			} else {
				$file = trim($parts[1]);
				$tmp_file = tempnam('/tmp', 'sw');
				file_put_contents($tmp_file, $file);
				if (!isset($meta['name'])) {
					$meta['name'] = 'file';
				}
				$request->file[$meta['name']] = array(
					'name' => $meta['filename'],
					'type' => $head['Content-Type'], 'size' => strlen($file),
					'error' => UPLOAD_ERR_OK, 'tmp_name' => $tmp_file
				);
			}
		}
	}
}