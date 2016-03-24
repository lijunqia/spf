<?php
namespace ypf;
class Curl
{
	const CURL_CONNECTTIMEOUT = 30;
	const CURL_TIMEOUT = 30;
	public function http_connect($type, $url, $params = '', $cookie = '')
	{
		if (!extension_loaded('curl')) {
			return array('ret' => -1, 'msg' => 'please load the extension of php_curl');
		}
		if (!in_array($type, array('get', 'post'))) {
			return array('ret' => -2, 'msg' => 'type is required');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CURL_CONNECTTIMEOUT);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
		if (is_array($cookie)) {
			$cookie_str = '';
			foreach ($cookie as $key => $val) {
				$cookie_str .= "{$key}={$val};";
			}
			curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);
		}
		if ($type == 'post') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}
		$output = curl_exec($ch);
		if ($output == FALSE) {
			return array('ret' => -3, 'msg' => 'cURL Error:' . curl_error($ch));
		}
		curl_close($ch);
		return array('ret' => 0, 'data' => $output);
	}
	function sockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE, $encodetype = 'URLENCODE', $allowcurl = TRUE, $position = 0)
	{
		$return = '';
		$matches = parse_url($url);
		$scheme = $matches['scheme'];
		$host = $matches['host'];
		$path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
		$port = !empty($matches['port']) ? $matches['port'] : 80;
		if (function_exists('curl_init') && function_exists('curl_exec') && $allowcurl) {
			$ch = curl_init();
			$ip && curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: " . $host));
			curl_setopt($ch, CURLOPT_URL, $scheme . '://' . ($ip ? $ip : $host) . ':' . $port . $path);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if ($post) {
				curl_setopt($ch, CURLOPT_POST, 1);
				if ($encodetype == 'URLENCODE') {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
				} else {
					parse_str($post, $postarray);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $postarray);
				}
			}
			if ($cookie) {
				curl_setopt($ch, CURLOPT_COOKIE, $cookie);
			}
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
			$data = curl_exec($ch);
			$status = curl_getinfo($ch);
			$errno = curl_errno($ch);
			curl_close($ch);
			if ($errno || $status['http_code'] != 200) {
				return FALSE;
			} else {
				return !$limit ? $data : substr($data, 0, $limit);
			}
		}
		if ($post) {
			$post = http_build_query($post);//数组转成字符串，added by clzhu at 2013-11-26
			$out = "POST $path HTTP/1.0\r\n";
			$header = "Accept: */*\r\n";
			$header .= "Accept-Language: zh-cn\r\n";
			$boundary = $encodetype == 'URLENCODE' ? '' : '; boundary=' . trim(substr(trim($post), 2, strpos(trim($post), "\n") - 2));
			$header .= $encodetype == 'URLENCODE' ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data$boundary\r\n";
			$header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$header .= "Host: $host:$port\r\n";
			$header .= 'Content-Length: ' . strlen($post) . "\r\n";
			$header .= "Connection: Close\r\n";
			$header .= "Cache-Control: no-cache\r\n";
			$header .= "Cookie: $cookie\r\n\r\n";
			$out .= $header . $post;
		} else {
			$out = "GET $path HTTP/1.0\r\n";
			$header = "Accept: */*\r\n";
			$header .= "Accept-Language: zh-cn\r\n";
			$header .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$header .= "Host: $host:$port\r\n";
			$header .= "Connection: Close\r\n";
			$header .= "Cookie: $cookie\r\n\r\n";
			$out .= $header;
		}
		$fpflag = 0;
		if (!$fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout)) {//@fsocketopen改为@fsockopen，modified by clzhu at 2013-11-26
			$context = array(
				'http' => array(
					'method' => $post ? 'POST' : 'GET', 'header' => $header, 'content' => $post, 'timeout' => $timeout,
				),
			);
			$context = stream_context_create($context);
			$fp = @fopen($scheme . '://' . ($ip ? $ip : $host) . ':' . $port . $path, 'b', FALSE, $context);
			$fpflag = 1;
		}
		if (!$fp) {
			return '';
		} else {
			stream_set_blocking($fp, $block);
			stream_set_timeout($fp, $timeout);
			@fwrite($fp, $out);
			$status = stream_get_meta_data($fp);
			if (!$block) {
				usleep(10000);//延迟10ms保证请求已发出，added by clzhu at 2013-11-26
			} else if (!$status['timed_out']) {
				while (!feof($fp) && !$fpflag) {
					if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
						break;
					}
				}
				if ($position) {
					for ($i = 0; $i < $position; $i++) {
						$char = fgetc($fp);
						if ($char == "\n" && $oldchar != "\r") {
							$i++;
						}
						$oldchar = $char;
					}
				}
				if ($limit) {
					$return = stream_get_contents($fp, $limit);
				} else {
					$return = stream_get_contents($fp);
				}
			}
			@fclose($fp);
			return $return;
		}
	}

	/**
	 * proxy: 10.152.18.219:8080
	 */
	public function curl_poxy($proxy, $url, $timeout = 30)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		$data = curl_exec($ch);
		$status = curl_getinfo($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if ($errno) {
			throw new \Exception("Network Error,errno={$errno}");
			return array("errno" => $errno, "msg" => "Network Error");
		}
		if ($status['http_code'] != 200) {
			throw new \Exception("HTTP Error,status=" . $status['http_code'] . "\n");
			return array("errno" => $status['http_code'], "msg" => "HTTP Status Error");
		}
		return array("errno" => 0, "data" => $data);
	}
}