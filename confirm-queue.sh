#!/bin/bash

# Путь к исполняемому файлу PHP, относительно текущей директории скрипта
PHP_BIN="$(dirname "$(readlink -f "$0")")/bin/php"

# Путь к файлу конфигурации php.ini, относительно текущей директории скрипта
PHP_INI="$(dirname "$(readlink -f "$0")")/bin/php.ini"

# Путь к скрипту PHP, который нужно выполнить
PHP_SCRIPT="$(dirname "$(readlink -f "$0")")/php/console.php"

# Запуск PHP скрипта с указанными параметрами
$PHP_BIN -c $PHP_INI -n $PHP_SCRIPT "confirm-queue"

# Ожидание нажатия любой клавиши перед выходом
read -n 1 -s -r -p "Press any key to continue"
echo # Добавляет перенос строки
