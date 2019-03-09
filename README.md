NEOBOARD 
========================================================
Находится в состоянии тестирования! Ждем релиз


Описание
------------
Neoboard - это анонимная имиджборда, разрабатывается на основе OpenIB, которая является наследником Infinity и vichan. 
Сейчас проекты (OpenIB/Infinity/vichan)  заброшены и не развиваются. За это время накопилось возможностей/функций требующих реализавции в современном интернет ресурсе.



Установка
------------
Требования:

* Unix or Unix-like OS
* Apache/Nginx
* MySQL
* PHP >= 5.6 (mbstring, apcu, apcu-bc)

Пример установки, ОС Ubuntu16-x64

```
apt-get update & upgrade
apt-get install software-properties-common
add-apt-repository ppa:ondrej/php
apt-get update & upgrade
apt-get install nginx php7.0 php7.0-fpm php7.0-mysql php7.0-mbstring php7.0-apcu php7.0-memcached php7.0-gd mysql-server memcached graphicsmagick gifsicle imagemagick ffmpeg exiftool
```

Создать базу и импортировать данные
```
mysql -uroot -p
CREATE DATABASE neochan
mysql -uroot -p neochan < install.sql
```


Скопируйте ./inc/secrets.example.php в ./inc/secrets.php
и заполните данные для подключения к базе mysql

```
$config['db']['server'] = 'localhost';
$config['db']['database'] = 'neochan';
$config['db']['prefix'] = '';
$config['db']['user'] = 'root';
$config['db']['password'] = 'password';
$config['timezone'] = 'UTC';
$config['cache']['enabled'] = 'memcached';
```



Генерация страниц
------------
Статические страницы (claim.html, boards.html, index.html) нуждаются в постоянной пересборке, 
поэтому необходимо добавить эти задачи в crontab 

```cron
*/10 * * * * cd /srv/http; /usr/bin/php /srv/http/boards.php
*/5 * * * * cd /srv/http; /usr/bin/php /srv/http/claim.php
*/20 * * * * cd /srv/http; /usr/bin/php -r 'include "inc/functions.php"; rebuildThemes("bans");'
*/5 * * * * cd /srv/http; /usr/bin/php /srv/http/index.php
```

Nginx locations
------------
```
location ~ ^/embed/ {
    rewrite ^/embed/([\w\d_-]+)/([\w\d_-]+).jpg$ /embed.php?service=$1&id=$2 last;
}
```



Файл main.js пустой по умолчанию, чтобы его создать нужно зайти в админку (site/mod.php логин/пароль admin/password)
Выбрать раздел rebuld и нажать кнопку rebulid   

