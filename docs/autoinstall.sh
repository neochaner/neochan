#!/bin/sh
OSV=""
PASS=""
STATE="DEBUG"
MYSQL_ROOT_PASSWORD="123456"
MYSQL_DB_NAME="neochan"
WWW="/var/www/neochan"
PHPVER=""

update() {
clear
echo "+-------------------------------------------+"
echo "| 0%   APT UPDATE                           |"
echo "+-------------------------------------------+"
apt-get -qq update
}


finish() {
clear
echo "+-------------------------------------------+"
echo "| 100%  FINISH!                             |"
echo "+-------------------------------------------+"
exit 0
}

get(){
clear
echo "+-------------------------------------------+"
echo "  $1%   install: $2                         "
echo "+-------------------------------------------+"
if [[ $OSV == "UBUNTU16" ]]; then
apt-get install $2 -y > /dev/null 2>&1
else
apt-get install $2 -y
sleep 5
fi
}

get_mysql_server() {
clear
sleep 20
if [[ $OSV == "UBUNTU16" ]]; then
echo "+-------------------------------------------+"
echo "  $1%   install: mysql5.7, pass: $MYSQL_ROOT_PASSWORD "
echo "+-------------------------------------------+"
echo "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
apt install mysql-server -y
usermod -d /var/lib/mysql/ mysql



elif [[ $OSV == "UBUNTU18" ]]; then
echo "+-------------------------------------------+"
echo "  $1%   install: mysql5.7, pass: $MYSQL_ROOT_PASSWORD "
echo "+-------------------------------------------+"
echo "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
apt install mysql-server -y
usermod -d /var/lib/mysql/ mysql

elif [[ $OSV == "DEBIAN8" ]]; then
echo "+-------------------------------------------+"
echo "  $1%   install: mysql5.7, pass: $MYSQL_ROOT_PASSWORD "
echo "+-------------------------------------------+"
echo "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
apt install mysql-server -y
usermod -d /var/lib/mysql/ mysql

elif [[ $OSV == "DEBIAN9" ]]; then
echo "+-------------------------------------------+"
echo "  $1%   install: mysql5.7, pass: $MYSQL_ROOT_PASSWORD "
echo "+-------------------------------------------+"
echo "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD" | debconf-set-selections
apt install mysql-server -y
usermod -d /var/lib/mysql/ mysql
fi
sleep 1
}




setup_php(){
clear
if [[ $OSV == "UBUNTU16" ]]; then
echo "+-------------------------------------------+"
echo "| $1%   prepare php 7.0                     |"
echo "+-------------------------------------------+"
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/7.0/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/' /etc/php/7.0/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 30M/' /etc/php/7.0/fpm/php.ini
elif [[ $OSV == "UBUNTU18" ]]; then
echo "+-------------------------------------------+"
echo "| $1%   prepare php 7.2                     |"
echo "+-------------------------------------------+"
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/7.2/fpm/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/' /etc/php/7.2/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 30M/' /etc/php/7.2/fpm/php.ini
fi
sleep 1
}

get_neochan() {
clear
echo "+-------------------------------------------+"
echo "  $1%  download neochan source to dir $WWW "
echo "+-------------------------------------------+"
if [ ! -d "$WWW" ]; then
git clone --progress https://github.com/neochaner/neochan $WWW
cp "$WWW/inc/secrets.example.php" "$WWW/inc/secrets.php"
fi
}

setup_neochan() {
clear
echo "+-------------------------------------------+"
echo "  $1%  create board database $MYSQL_DB_NAME "
echo "+-------------------------------------------+"
mysql -uroot -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE $MYSQL_DB_NAME;"
mysql -uroot -p$MYSQL_ROOT_PASSWORD $MYSQL_DB_NAME < "$WWW/install.sql"
sleep 1
clear
echo "+-------------------------------------------+"
echo "  $1%  setup board configuration "
echo "+-------------------------------------------+"
php "$WWW/install.php" "$WWW" "$MYSQL_DB_NAME" "root" $MYSQL_ROOT_PASSWORD
sleep 1
}

remove_apache() {
service apache2 stop
apt-get remove apache2 -y
}

setup_nginx() {
clear
echo "+-------------------------------------------+"
echo "  $1%  setup nginx server"
echo "+-------------------------------------------+"
echo "server {
    listen 80 default_server;
    listen [::]:80 default_server;
    root $WWW;
    index index.php index.html index.htm index.nginx-debian.html;
    server_name _;
    location / {
        
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHPVER-fpm.sock;
    }
    location ~ /\.ht {
        deny all;
    }
}" > /etc/nginx/sites-available/default

sleep 1
}

setup_cron() {
echo "*/10 * * * * cd $WWW; /usr/bin/php $WWW/boards.php
*/5 * * * * cd $WWW; /usr/bin/php $WWW/claim.php
*/20 * * * * cd $WWW; /usr/bin/php -r 'include \"inc/functions.php\"; rebuildThemes(\"bans\");'
*/5 * * * * cd $WWW; /usr/bin/php $WWW/index.php" > /etc/cron.d/neochan
}

gen_pass() {
    MATRIX='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'
    LENGTH=10
    while [ ${n:=1} -le $LENGTH ]; do
        PASS="$PASS${MATRIX:$(($RANDOM%${#MATRIX})):1}"
        let n+=1
    done
    echo "$PASS"
}


post_install() {
clear
echo "+-------------------------------------------+"
echo "  RESTARTING SERVICES..."
echo "+-------------------------------------------+"
echo "setup cron...";
setup_cron
echo "restart nginx...";
service nginx restart
echo "restart php-fpm..."
if [[ $OSV == "UBUNTU16" ]]; then
service php7.0-fpm restart
elif [[ $OSV == "UBUNTU18" ]]; then
service php7.2-fpm restart
fi
echo "restart mysql...";
service nginx mysql-server
}

setup_repos() {
clear
echo "+-------------------------------------------+"
echo "  $1% UPDATE REPOS"
echo "+-------------------------------------------+"
if [[ $OSV == "UBUNTU16" ]]; then
#add-apt-repository "deb https://deb.torproject.org/torproject.org xenial main"
#add-apt-repository "deb-src https://deb.torproject.org/torproject.org xenial main"
elif [[ $OSV == "UBUNTU18" ]]; then
#add-apt-repository "deb https://deb.torproject.org/torproject.org bionic main"
#add-apt-repository "deb-src https://deb.torproject.org/torproject.org bionic main"
elif [[ $OSV == "DEBIAN8" ]]; then
#add-apt-repository "deb https://deb.torproject.org/torproject.org jessie main"
#add-apt-repository "deb-src https://deb.torproject.org/torproject.org jessie main"
add-apt-repository "deb http://www.deb-multimedia.org jessie main non-free"
add-apt-repository "deb-src http://www.deb-multimedia.org jessie main non-free"
apt-get -qq update
apt-get install deb-multimedia-keyring
apt-get -qq update
elif [[ $OSV == "DEBIAN9" ]]; then
#add-apt-repository "deb https://deb.torproject.org/torproject.org stretch main"
#add-apt-repository "deb-src https://deb.torproject.org/torproject.org stretch main"
elif [[ $OSV == "DEBIAN10" ]]; then
#add-apt-repository "deb https://deb.torproject.org/torproject.org buster main"
#add-apt-repository "deb-src https://deb.torproject.org/torproject.org buster main"
fi
sleep 1
}

install() { 
get "1" "software-properties-common"
setup_repos "2"
get "3" "git"
get "4" "curl"
get "7" "ffmpeg"
get "9" "exiftool"
get "12" "imagemagick"
get "17" "memcached"
get "19" "expect"
get "20" "php"
get "25" "php-fpm"
get "28" "php-mysql"
get "30" "php-mbstring"
get "32" "php-gd"
get "34" "php-bcmath"
get "36" "php-curl"
get "38" "php-mysql"
get "40" "php-memcached"
setup_php "48"
get_mysql_server "50"
get_neochan "60"
setup_neochan "65"
remove_apache
get "70" "nginx"
setup_nginx "75" "85.209.91.92" 
}




lsb=$(lsb_release -r|awk '{print $2}')
echo "$lsb"
if [[ $lsb == "16.04" ]]; then
	echo "OS: Ubuntu 16.04"
	OSV="UBUNTU16"
	PHPVER="7.0"
elif [[ $lsb == "18.04" ]]; then
	echo "OS: Ubuntu 18.04"
	OSV="UBUNTU18"
	PHPVER="7.2"
elif [[ $lsb == "8.11" ]]; then
	echo "OS: DEBIAN jessie"
	OSV="DEBIAN8"
	PHPVER="7.0"
elif [[ $lsb == "9.8" ]]; then
	echo "OS: DEBIAN stretch"
	OSV="DEBIAN9"
	PHPVER="7.2"
else
	echo "OS $lsb NOT SUPPORTED!"
	exit 0
fi

sleep 3

update
install
post_install
finish



























