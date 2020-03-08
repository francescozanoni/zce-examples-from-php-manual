# zce-examples-from-php-manual
PHP example extractor from PHP manual for ZCE exam

Execution:
```sh
wget https://www.php.net/manual/en/php_manual_en.tar.gz -O storage\php_manual_en.tar.gz
php src/extract.php
php src/write_db.php
php src/filter.php
php src/read_relevant.php
```