# syntax=docker/dockerfile:1

FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    redis-server \
    && pecl install redis && docker-php-ext-enable redis

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php", "./app.php" ]

# parse-statistic

# 868276123740.dkr.ecr.us-east-1.amazonaws.com/statistic/aggregate-s3-fs-logs

# docker build -t statistic-aggregator:v2 .
# docker run -it --rm statistic-aggregator:v2 php app.php --dates=...
