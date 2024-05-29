# syntax=docker/dockerfile:1

FROM php:8.3-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php", "./app.php" ]
