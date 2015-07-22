<?php

namespace Zadarma;

class Handler
{
	private $key;
	private $secret;
	private $limits;
	private $headers;

	public $result;

	const TYPE_GET = 1;
	const TYPE_POST = 2;
	const TYPE_PUT = 3;

	/**
	 * Конструктор
	 *
	 * @param bool $limits Добавление информации о текущих лимитах к ответу
	 * @return object $this
	 */
	public function __construct($limits = false)
	{
		$this->limits = $limits;
		$this->headers = array();

		$key = __DIR__.'/../config/key.key';
		$secret = __DIR__.'/../config/secret.key';

		if (!file_exists($key)) {
			throw new \Exception('Отсутствует key-файл');
		}

		if (!file_exists($secret)) {
			throw new \Exception('Отсутствует secret-файл');
		}

		$this->key = trim(file_get_contents($key));
		$this->secret = trim(file_get_contents($secret));

		if (empty($this->key)) {
			throw new \Exception('Key-файл пуст');
		}

		if (empty($this->secret)) {
			throw new \Exception('Secret-файл пуст');
		}
	}

	/**
	 * Выполняет запрос к API
	 *
	 * Возможные значения для $type:
	 * self::TYPE_GET
	 * self::TYPE_POST
	 * self::TYPE_PUT
	 *
	 * @param int $type
	 * @param type $method Полная строка метода из документации (например: "/v1/info/balance/")
	 * @param type $data Массив параметров array(ключ => значение)
	 * @return object $this
	 */
	public function request($type, $method, $data = array())
	{
		if (empty($type)) {
			throw new \Exception('Не указан тип запроса');
		}

		if (empty($method)) {
			throw new \Exception('Не указан метод запроса');
		}

		foreach ($data as $key => $value) {
			if (empty($value)) {
				unset($data[$key]);
			}
		}

		ksort($data);
		$params = http_build_query($data);
		$sign = base64_encode(hash_hmac('sha1', $method.$params.md5($params), $this->secret));
		$headers = array('Authorization: '.$this->key.':'.$sign);
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

		if ($this->limits) {
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, 'parseHeaderLine'));
		}

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
			throw new \Exception($error);
		}

		$this->result = json_decode($result, true);

		if (!isset($this->result['status'])) {
			throw new \Exception($this->result['message']);
		}

		if ($this->result['status'] == 'error') {
			throw new \Exception($this->result['message']);
		}

		if ($this->limits) {
			$this->result['limits'] = $this->headers;
		}

		return $this;
	}

	private function parseHeaderLine($curl, $header_line)
	{
		if (preg_match_all('/^X-(RateLimit-[a-zA-Z]+):\s([0-9]+)/', $header_line, $m)) {
			$this->headers[$m[1][0]] = (int)$m[2][0];
		}

		return strlen($header_line);
	}
}
