NEOBOARD 
========================================================
![alt text](docs/img/attention.png "Warning")
Currently the project is not completed.

[Demo : neochan](https://neochan.net/test "Demo")



Description
------------
Anonymous imageboard, continued development of the infinity engine.


Requirements
------------

* Linux like OS
* PHP >= 7.0
* MYSQL / MariaDB 
* ffmpeg, memcached (optional: graphicsmagick, gifsicle, imagemagick,  exiftool)



Nginx config for embed images:
```
	location ~ ^/embed/ {
		rewrite ^/embed/([\w\d_-]+)/([\w\d_-]+).jpg$ /embed.php?service=$1&id=$2 last;
	}
```




