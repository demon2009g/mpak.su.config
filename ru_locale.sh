#!/bin/bash

# Меняем локализацию сервера с английского на русский
#данная проблемя частенько возникает на урезаных версиях линукс дистрибутива
#часто встечается у vps хостерах

sudo aptitude install console-cyrillic -y

export LANGUAGE=ru_RU.UTF-8 # Что то с кодировкой
export LC_ALL=ru_RU.UTF-8
locale-gen ru_RU.UTF-8
dpkg-reconfigure locales

echo 'LANG="ru_RU.UTF-8"' > /etc/environment
echo 'ru_RU.UTF-8 UTF-8' >> /etc/locale.gen
locale-gen #- запускаем команду для генерации локали.

# Перезаходим на сервер.