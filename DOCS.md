# Документация

## Инструкция по установке парсера на TorrentPier Cattle

Открыть файл `library/includes/parser/functions.php` и найти:
```php
// Раскомментировать для версий v2.4.0 и выше
```
Строчки ниже раскомментируйте:
```php
// $tor = \Arokettu\Bencode\Bencode::decode($torrent, dictType: \Arokettu\Bencode\Bencode\Collection::ARRAY);
// $info_hash = pack('H*', sha1(\Arokettu\Bencode\Bencode::encode($tor['info'])));
```

## Подробное описание настроек парсера в $trackers

Данный шаблон можно использовать при добавлении своего парсера

```php
'название_трекера' => array(
	'enabled' => true, // включен (true) или выключен (false) парсер
	'info' => array(
		// Данные для отображения в списке трекеров
		'name' => 'Название трекера',
		'href' => 'Ссылка на трекер',
		'icon' => BB_ROOT . 'styles/images/trackers/иконка.ico', // оставьте поле пустым чтобы не было иконки
	),
	'settings' => array(
		'regex' => '', // регулярное выражение для валидации URL адреса раздачи
		'dl_url' => '', // ссылка на файл загрузки
		'target_element' => '', // HTML-элемент начиная с которого будет парсится страница
	),
	'auth' => array(
		'enabled' => false, // используется ли обязательная авторизация на ресурсе (опциональный)
		'login_url' => 'https://example.com/login.php', // ссылка на страницу с авторизацией
		'login_input_name' => 'username', // названия поля в форме, в котором указывается логин (необходимо если включен 'auth')
		'password_input_name' => 'password', // названия поля в форме, в котором указывается пароль (необходимо если включен 'auth')
		'login_has_error_element' => '/<div class="info_msg_wrap">.*?<\/div>/s', // элемент по которому будем определять, что авторизация не прошла успешно (необходимо если включен 'auth')
	),
	'redirect' => array( // (опциональный)
		'from' => array('http://example1.com/', 'http://example2.com/', 'http://example3.com/'), // с каких адресов делать переадресацию
		'to' => 'http://example.com/', // адрес на который будет происходить переадресация
	),
),
```
