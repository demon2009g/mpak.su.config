#!/bin/bash

#start:
# $ cd /tmp/; wget https://raw.githubusercontent.com/demon2009g/mpak.su.config/master/server.sh; chmod +x server.sh; ./server.sh;

sudo add-apt-repository ppa:ondrej/php -y # Репозитории php7.0
sudo apt-get update -y # Обновление всех источников
sudo apt-get upgrade -y # Обновление всех программ
sudo apt-get install aptitude -y # Устанавливает манагер приложений под линукс

sudo aptitude install nano -y 	# редактор
sudo aptitude install iftop -y # Программа монитор сетевых соединений
sudo aptitude install atop -y  # Программа монитор ресурсов
sudo aptitude install nload -y  # Программа монитор загрузки сети
sudo aptitude install git-core -y # Контроль версий
sudo aptitude install sed -y 	# потоковый текстовый редактор
sudo aptitude install lynx -y	# один из первых текстовых браузеров.
sudo aptitude install rar unrar zip unzip -y # Архиваторы 
sudo aptitude install duplicity ncftp lftp python python-dev \
python-paramiko python-pycryptopp python-boto make gcc \
dialog libssl-dev libffi-dev librsync-dev ca-certificates -y # duplicity + system
sudo aptitude install sqlite3 -y # sqlite3
sudo aptitude install libreoffice -y # LibreOffice  lowriter --convert-to pdf document.docx || soffice --headless --convert-to pdf document.docx

sudo aptitude install memcached -y # memcach
sudo aptitude install apache2 apache2-doc libapache2-mod-php7.0 -y # Apache2
sudo aptitude install php7.0 php-pear -y				 	 # PHP
sudo aptitude install mysql-server mysql-client php7.0-mysql -y	 # MySQL
# GeoIP php
cd /tmp && wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz
gunzip GeoLiteCity.dat.gz
sudo mkdir -p /usr/share/GeoIP
sudo mv GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat
# Дополнительные модули к php
sudo aptitude install php7.0-curl php7.0-gd php7.0-intl php7.0-imagick \
php7.0-ldap php7.0-imap php-memcache php7.0-pspell php7.0-mbstring \
php7.0-sqlite3 php7.0-tidy imagemagick php7.0-xdebug \
php7.0-xmlrpc php7.0-xsl php7.0-geoip php7.0-mcrypt -y

sudo aptitude install nginx -y #nginx
sudo aptitude install phpmyadmin -y # Ставим phpMyAdmin

sudo a2enmod remoteip
sudo a2enmod php7.0
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers
sudo phpenmod pdo_mysql
sudo phpenmod mysqlnd
sudo phpenmod pdo_sqlite

mkdir -p /srv/www/ssl
mkdir -p /srv/www/vhosts
mkdir -p /srv/www/sslhosts
mkdir -p /srv/www/sslhosts.conf/nginx
mkdir -p /srv/www/sslhosts.conf/apache
mkdir -p /srv/www/vhosts.conf/apache
mkdir -p /srv/www/vhosts.conf/nginx
mkdir -p /var/www/html/.well-known #letsencrypt

echo "alias 'l=ls -l'" >> ~/.bashrc
echo "alias 'vhosts=php -f /srv/www/mpak.cms.config/hosts.php'" >> ~/.bashrc
echo "alias 'letsencrypt=/srv/www/letsencrypt/certbot-auto certonly --webroot --agree-tos -w /var/www/html --email admin@it-impulse.ru -d '" >> ~/.bashrc

#apache
echo "Include /srv/www/vhosts.conf/apache" >> /etc/apache2/apache2.conf
echo "Include /srv/www/sslhosts.conf/apache" >> /etc/apache2/apache2.conf

sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf
sed -i 's/Listen 443/Listen 993/g' /etc/apache2/ports.conf
sed -i 's/VirtualHost \*:80/VirtualHost \*:8080/g' /etc/apache2/sites-available/000-default.conf
sed -i 's/VirtualHost _default_:443/VirtualHost _default_:993/g' /etc/apache2/sites-available/default-ssl.conf
sed -i 's/\%h/\%a/g' /etc/apache2/apache2.conf

#apache letsencrypt
echo "Alias /.well-known /var/www/html/.well-known" >> /etc/apache2/conf-enabled/letsencrypt.conf
echo "<Directory /var/www/html/.well-known>" >> /etc/apache2/conf-enabled/letsencrypt.conf
echo "Options Indexes FollowSymLinks MultiViews" >> /etc/apache2/conf-enabled/letsencrypt.conf
echo "Require all granted" >> /etc/apache2/conf-enabled/letsencrypt.conf
echo "</Directory>" >> /etc/apache2/conf-enabled/letsencrypt.conf

#nginx
sed -i '/tcp_nodelay on;/a\\tclient_max_body_size 100m;' /etc/nginx/nginx.conf
sed -i '/include \/etc\/nginx\/sites-enabled\/\*;/a\
\tinclude \/srv\/www\/vhosts.conf\/nginx\/\*.conf;\
\tinclude \/srv\/www\/sslhosts.conf\/nginx\/\*.conf;' /etc/nginx/nginx.conf

#mysql
echo "[mysqld]" >> /etc/mysql/conf.d/disable_strict_mode.cnf
echo "sql_mode=IGNORE_SPACE,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION" >> /etc/mysql/conf.d/disable_strict_mode.cnf

#php
sed -i 's/short_open_tag = Off/short_open_tag = On/g' /etc/php/7.0/cli/php.ini
sed -i 's/short_open_tag = Off/short_open_tag = On/g' /etc/php/7.0/apache2/php.ini
sed -i 's/;phar.readonly = On/phar.readonly = Off/g' /etc/php/7.0/cli/php.ini
sed -i 's/;phar.readonly = On/phar.readonly = Off/g' /etc/php/7.0/apache2/php.ini
sed -i 's/;phar.require_hash = On/phar.require_hash = Off/g' /etc/php/7.0/cli/php.ini
sed -i 's/;phar.require_hash = On/phar.require_hash = Off/g' /etc/php/7.0/apache2/php.ini

sed -i 's/display_errors = Off/display_errors = On/g' /etc/php/7.0/apache2/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 90/g' /etc/php/7.0/apache2/php.ini
sed -i 's/max_input_time = 60/max_input_time = 180/g' /etc/php/7.0/apache2/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 200M/g' /etc/php/7.0/apache2/php.ini
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/g' /etc/php/7.0/apache2/php.ini
sed -i 's/max_file_uploads = 20/max_file_uploads = 150/g' /etc/php/7.0/apache2/php.ini

#phpmyadmin
sed -i 's/mod_php.c/mod_php7.c/g' /etc/phpmyadmin/apache.conf
sed -i 's/mod_php5.c/mod_php7.c/g' /etc/phpmyadmin/apache.conf
#echo "# Include phpmyadmin configurations:" >> /etc/apache2/apache2.conf
#echo "Include /etc/phpmyadmin/apache.conf" >> /etc/apache2/apache2.conf

/etc/init.d/mysql restart
/etc/init.d/apache2 stop
/etc/init.d/nginx start
/etc/init.d/apache2 start

#Скачиваем конфигурационные скрипты
git clone https://github.com/demon2009g/mpak.su.config.git /srv/www/mpak.cms.config
#Скачиваем движок
git clone https://github.com/mpak2/mpak.su.git /srv/www/mpak.cms
#Скачиваем letsencrypt
git clone https://github.com/letsencrypt/letsencrypt /srv/www/letsencrypt

(crontab -u root -l; echo "30 2 * * 1 /srv/www/letsencrypt/certbot-auto renew >> /var/log/letsencrypt-renew.log #LetsenCrypt" ) | crontab -u root -
/srv/www/letsencrypt/letsencrypt-auto

# Запуск фтп сервера для хранилища
# /usr/local/bin/ftpcloudfs -b 62.76.1.1 -p 2021 -a http://api.clodo.ru -l /var/log/ftpcloudfs.log --workers=4 --pid-file=/var/run/ftpcloudfs.pid
# Storage key: a1e60231a1e6ce3ceda0d00fb651aa37

