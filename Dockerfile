# syntax=docker/dockerfile:1

FROM php:8.3-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php", "./app.php" ]

# parse-statistic

# docker run -it --rm statistic-aggregator php app.php --dates=...
