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

