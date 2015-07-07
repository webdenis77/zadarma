<?php

namespace Zadarma;

use Zadarma\Exception as Exception;

class Handler
{
	private $key;
	private $secret;

	public $result;

	const TYPE_GET = 1;
	const TYPE_POST = 2;
	const TYPE_PUT = 3;

	public function __construct()
	{
		$key = __DIR__.'/../config/key.key';
		$secret = __DIR__.'/../config/secret.key';

		if (!file_exists($key)) {
			throw new Exception('Отсутсвует key-файл');
		}

		if (!file_exists($secret)) {
			throw new Exception('Отсутсвует secret-файл');
		}

		$this->key = trim(file_get_contents($key));
		$this->secret = trim(file_get_contents($secret));

		if (empty($this->key)) {
			throw new Exception('Key-файл пуст');
		}

		if (empty($this->secret)) {
			throw new Exception('Secret-файл пуст');
		}
	}

	public function request($type, $method, $data = [])
	{
		if (empty($type)) {
			throw new Exception('Не указан тип запроса');
		}

		if (empty($method)) {
			throw new Exception('Не указан метод запроса');
		}

		ksort($data);
		$params = http_build_query($data);
		$sign = base64_encode(hash_hmac('sha1', $method.$params.md5($params), $this->secret));
		$headers = ['Authorization: '.$this->key.':'.$sign];
		$post = false;
		$put = false;
		$url = '';

		switch ($type) {
			case Handler::TYPE_GET:
				$url = (empty($data)) ? '' : '?'.$params;
				break;
			case Handler::TYPE_POST:
				break;
			case Handler::TYPE_PUT:
				$headers[] = 'Content-Length: '.strlen($params);
				break;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.zadarma.com'.$method.$url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		if ($post) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		if ($put) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}


		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);

		curl_close($ch);

		if ($error) {
			throw new Exception($error);
		}

		$this->result = json_decode($result);

		if ($this->result->status == 'error') {
			throw new Exception($this->result->message);
		}

		return $this;
	}
}
