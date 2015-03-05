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

sudo aptitude install iftop # Программа монитор сетевых соединений
sudo aptitude install atop  # Программа монитор ресурсов
sudo aptitude install apache2 # Стандартный веб сервер
sudo aptitude install php5 libapache2-mod-php5 php5-cli php5-mysql # ДОполнительные модули к апачу
sudo aptitude install libcurl3 libcurl3-dev php5-curl

#export LANGUAGE=ru_RU.UTF-8 # Что то с кодировкой
#export LC_ALL=ru_RU.UTF-8
#locale-gen ru_RU.UTF-8
#dpkg-reconfigure locales

mkdir /srv/www
mkdir /srv/www/vhosts
mkdir /srv/www/vhosts.conf
mkdir /srv/www/sslhosts
mkdir /srv/www/sslhosts.conf

apt-get install sed
apt-get install lynx
mkdir "/srv/www/vhosts/`lynx --dump http://ipecho.net/plain | sed 's/^[ \t]*//'`";

sudo a2enmod rewrite # 
aptitude install php5-gd # Библиотека которая занимается изменением размера изображений

if grep "alias 'l=ls -l'" ~/.bashrc;
then
	echo "уже установлен"
else
	echo "alias 'l=ls -l'" >> ~/.bashrc
fi

if grep "Include /srv/www/vhosts.conf/" /etc/apache2/apache2.conf; then
	echo "уже установлен"
else
	echo "Include /srv/www/vhosts.conf/" >> /etc/apache2/apache2.conf
fi

/etc/init.d/apache2 start
aptitude install git-core

aptitude install mysql-server
/etc/init.d/mysql start

#Скачиваем конфигурационные скрипты
cd /srv/www/
git clone https://github.com/demon2009g/mpak.su.config.git

#Скачиваем движок
cd /srv/www/vhosts/
git clone https://github.com/mpak2/mpak.su.git mpak.cms

# Запуск фтп сервера для хранилища
# /usr/local/bin/ftpcloudfs -b 62.76.1.1 -p 2021 -a http://api.clodo.ru -l /var/log/ftpcloudfs.log --workers=4 --pid-file=/var/run/ftpcloudfs.pid
# Storage key: a1e60231a1e6ce3ceda0d00fb651aa37