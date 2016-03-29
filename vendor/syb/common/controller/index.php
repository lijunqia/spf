<?php
namespace syb\common\controller;
class Index extends \syb\mvc\Controller
{
	function actionIndex()
	{
		echo "<h1>Hello World. #".rand(1000, 9999)."</h1><pre>";
		//print_r(\syb\phplib\App::getInstance());
		echo '</pre>';
	}
}