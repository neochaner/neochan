<?php



///////////////  MYSQL DATABASE  ///////////////
$config['db']['server'] = 'localhost';
$config['db']['database'] = 'neochan';
$config['db']['prefix'] = '';
$config['db']['user'] = 'root';
$config['db']['password'] = 'pass';



///////////////  DEDICATED STORAGE SYSTEM  ///////////////
$config['fat_system'] = false;                  // активация
$config['fat_size'] = 10 * 1024 * 1024;         // файлы больше этого размера будут загружатся в хранилище
$config['fat_server'] = 'http://s.site.ru';     // домен для обращения к хранилищу (!заменить на https в будущем при принудительном https)
$config['fat_ftpip'] = '15.18.183.3';           // ip адрес хранилища
$config['fat_ftpuser'] = 'ftpuser';             // ftp пользователь
$config['fat_ftppass'] = 'ftppass';             // ftp пароль
$config['fat_ftp_timeout'] = 5;

// defaul timezone
$config['timezone'] = 'UTC';
// defaul cache
$config['cache']['enabled'] = 'memcached';



// Consider generating these from the following command.
// $ cat /proc/sys/kernel/random/uuid
$config['secure_trip_salt'] = '2b6e265d-de1e-465a-8967-a5a9e1f51572';
$config['secure_salt'] = '6b49fa06-e9c1-4f65-8d9e-74e22336d9c1';
$config['cookies']['salt'] = '41ab5e79-a9c1-4ba3-ab1c-a2eee296d2a3';


$config['encryption']['key_length'] = 2048;
$config['encryption']['key_alg'] = 'sha512';
$config['encryption']['public_key'] = '';
$config['encryption']['private_key'] = '';