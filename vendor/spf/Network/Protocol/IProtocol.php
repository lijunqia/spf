<?php
namespace spf\Network\Protocol;
interface IProtocol
{
	function on_start($server, $worker_id);

	function on_connect($server, $client_id, $from_id);

	function on_receive($server, $client_id, $from_id, $data);

	function on_close($server, $client_id, $from_id);

	function on_shutdown($server, $worker_id);

	function on_task($server, $task_id, $from_id, $data);

	function on_finish($server, $task_id, $data);

	function on_timer($server, $interval);
}