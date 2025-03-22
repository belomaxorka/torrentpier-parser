## NextGen Парсер раздач

Парсер раздач по ссылке. Код парсера написан очень хорошо и добавление новых парсеров делается очень просто.

**Совместимость:** все версии движка. [См. для v2.4.0](https://torrentpier.com/threads/nextgen-parser-razdach.42297/post-96551)

Ссылка на тему: [NextGen Парсер раздач](https://torrentpier.com/resources/nextgen-parser-razdach.303/)

### Достоинства

* Имеется поддержка трекеров с авторизацией
* Имеется система прав доступа к парсеру (см. конфиг)
* Имеется встроенная поддержка плеера [BBCode: Фильм по ID](https://torrentpier.com/resources/bbcode-film-po-id.302/)
* Легко расширяется новым функционалом
* Динамичный User-Agent (позволяет избежать бана)
* Поддержка редиректов (если трекер сменил свой адресс на новый, то при указании старого адресса будет происходить
  переадрессация на новый)
* Реализация с помощью Curl
* Расширеная система проверок вводимых данных
* Проверка авторизации на корректность

### Список поддерживаемых трекеров

* rutor.info
* xxxtor.net
* rutracker.ru
* rutracker.org
* z-torrents.ru
* booktracker.org
* riperam.org
* kinozal.tv
* windows-soft.info
* ddgroupclub.win
* megapeer.vip

### Полезные ссылки

* [Установка на TorrentPier Cattle](https://torrentpier.com/threads/nextgen-parser-razdach.42297/post-96551)
* [Как добавить трекер на основе TorrentPier](https://torrentpier.com/threads/nextgen-parser-razdach.42297/post-96559)
* [Набор BBCode тегов с рутрекера](https://torrentpier.com/resources/nabor-bbcode-tegov-s-rutrekera.283/)

### Используемые библиотеки

* [CurlHttpClient](https://github.com/dinke/curl_http_client). [LICENSE](library/includes/parser/curl/LICENSE)
* [random-user-agent](https://github.com/joecampo/random-user-agent). [LICENSE](library/includes/parser/random_user_agent/LICENSE)

### Авторство

* Участники [torrentpier.com](https://torrentpier.com/)
* Некоторые части кода принадлежат ivangord
* Код для парсера рутора взят [отсюда](https://torrentpier.com/resources/avtomaticheskij-parser-razdach-s-rutor-info.253/)
