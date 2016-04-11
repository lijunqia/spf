<?php
namespace spf\Process;
class Control
{
	static function set_name($name)
	{
		return (PHP_OS!=='Darwin')?cli_set_process_title($name):FALSE;
	}
	static function exists($name)
	{
		$shellCmd = "ps aux | grep " . $name . " | grep -v grep ";
		$ret = `{$shellCmd}`;
		return empty($ret)?FALSE:TRUE;
	}

	/**
	 * 改变进程的用户ID
	 *
	 * @param $user
	 */
	static function change_user($user)
	{
		$user = posix_getpwnam($user);
		if ($user) {
			posix_setuid($user['uid']);
			posix_setgid($user['gid']);
			return true;
		}else{
			return false;
		}
	}
	static function get_opt($cmd)
	{
		$cmd = trim($cmd);
		$arr = explode(' ', $cmd);
		$ret = ['args'=>[],'opt'=>[]];
		foreach ($arr as $arg) {
			$arg = trim($arg);
			if (empty($arg)) continue;
			if ($arg{0} === '\\' or $arg{0} === '-'){
				$ret['opt'][] = substr($arg, 1);
			} else {
				$ret['args'][] = $arg;
			}
		}
		return $ret;
	}
	static function check_pid_is_running($pid)
	{
		return posix_kill($pid, 0);
	}
}