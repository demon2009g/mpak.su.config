#!/bin/bash

#sudo apt-get update
#sudo apt-get upgrade

# Управление апачем
# Перезагрузка /etc/init.d/apache2 restart
# Остановка /etc/init.d/apache2 stop
# Запуск /etc/init.d/apache2 start

sudo apt-get update # Обновление всех источников
sudo apt-get upgrade # Обновление всех программ
sudo apt-get install aptitude # Устанавливает манагер приложений под линукс

sudo aptitude install console-cyrillic

sudo aptitude install nano 	# редактор
sudo aptitude install iftop # Программа монитор сетевых соединений
sudo aptitude install atop  # Программа монитор ресурсов
sudo aptitude install git-core # Контроль версий
sudo aptitude install sed 	# потоковый текстовый редактор
sudo aptitude install lynx 	# один из первых текстовых браузеров.

sudo aptitude install apache2 apache2-doc libapache2-mod-php5 # Apache2
sudo aptitude install php5 php5-cli php-pear				  # PHP
sudo aptitude install mysql-server mysql-client php5-mysql	  # MySQL
# Дополнительные модули к php
sudo aptitude install php5-curl php5-gd php5-idn php5-imagick \
php5-ldap php5-imap php5-memcache php5-mhash php5-ps php5-pspell \
php5-sqlite php5-suhosin php5-tidy imagemagick php5-xcache \
php5-xdebug php5-xmlrpc php5-xsl 

sudo aptitude install phpmyadmin # Ставим phpMyAdmin

sudo a2enmod rewrite 

#export LANGUAGE=ru_RU.UTF-8 # Что то с кодировкой
#export LC_ALL=ru_RU.UTF-8
#locale-gen ru_RU.UTF-8
#dpkg-reconfigure locales

mkdir /srv/www
mkdir /srv/www/ssl
mkdir /srv/www/vhosts
mkdir /srv/www/vhosts.conf
mkdir /srv/www/sslhosts
mkdir /srv/www/sslhosts.conf

# mkdir "/srv/www/vhosts/`lynx --dump http://ipecho.net/plain | sed 's/^[ \t]*//'`";

if grep "alias 'l=ls -l'" ~/.bashrc;
then
	echo "уже установлен\n"
else
	echo "alias 'l=ls -l'" >> ~/.bashrc
fi

if grep "Include /srv/www/vhosts.conf/" /etc/apache2/apache2.conf; then
	echo "уже установлен\n"
else
	echo "Include /srv/www/vhosts.conf/" >> /etc/apache2/apache2.conf
	echo "Include /srv/www/sslhosts.conf/" >> /etc/apache2/apache2.conf
fi

if grep "Include /etc/phpmyadmin/apache.conf" /etc/apache2/apache2.conf; then
	echo "уже установлен\n"
else
	echo "# Include phpmyadmin configurations:" >> /etc/apache2/apache2.conf
	echo "Include /etc/phpmyadmin/apache.conf" >> /etc/apache2/apache2.conf
fi

/etc/init.d/mysql restart
/etc/init.d/apache2 restart

#Скачиваем конфигурационные скрипты
git clone https://github.com/demon2009g/mpak.su.config.git /srv/www/mpak.cms.config
#Скачиваем движок
git clone https://github.com/mpak2/mpak.su.git /srv/www/mpak.cms

# Запуск фтп сервера для хранилища
# /usr/local/bin/ftpcloudfs -b 62.76.1.1 -p 2021 -a http://api.clodo.ru -l /var/log/ftpcloudfs.log --workers=4 --pid-file=/var/run/ftpcloudfs.pid
# Storage key: a1e60231a1e6ce3ceda0d00fb651aa37




















