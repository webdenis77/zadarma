# zadarma
## Installation
Edit you composer.json
```json
"nabarabane/zadarma": "dev-master"
```
or just
```sh
composer require nabarabane/zadarma
```
## Usage
Refer to [official documentaion](https://zadarma.com/en/support/api) for methods description

First you should create "config" folder in package directory and put there two files:
- "key.key"
- "secret.key"

containing private keys you got in your Zadarma profile ([https://ss.zadarma.com/api/](https://ss.zadarma.com/api/))

Example: request for a callback
```
try {
	$handler = new \Zadarma\Handler();
	$result = $handler->request(\Zadarma\Handler::TYPE_GET, '/v1/request/callback/', [
		'from' => '71111111111',
		'to' => '72222222222'
	])->result;
	echo('Success');
} catch (\Exception $e) {
	echo 'Error: '.$e->getMessage();
}
```
