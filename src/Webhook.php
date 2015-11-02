<?php

namespace Zadarma;

class Webhook
{
	private $hooks;

	public function __construct()
	{
		$this->hooks = array();
	}

	public function onCall($callback)
	{
		if (!is_callable($callback, true)) {
			throw new \InvalidArgumentException(sprintf('Invalid callback: %s.', print_r($callback, true)));
		}

		$this->hooks[] = $callback;
	}

	public function listen()
	{
		if (isset($_GET['zd_echo'])) {
			die($_GET['zd_echo']);
		}

		if (!$listen = isset($_POST['caller_id'])) {
			return false;
		}

		$data = $_POST;

		$phone_from = $data['caller_id'];
		$phone_to = $data['called_did'];
		$time = $data['call_start'];

		foreach($this->hooks as $callback) {
			call_user_func($callback, $phone_from, $phone_to, $time, $this);
		}
	}

	public function respond($response = array())
	{
		if (count($response)) {
			header('Content-Type: application/json; charset=utf-8');
			die(json_encode($response));
		}
	}
}
