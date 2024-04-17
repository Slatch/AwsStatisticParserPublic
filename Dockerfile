# syntax=docker/dockerfile:1

FROM php:8.3-cli
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
CMD [ "php", "./parse_statistic.php" ]

# parse-statistic

# docker run -it --rm statistic-aggregator php parse_statistic.php --dates=...
# docker run -it --rm statistic-aggregator php build_response.php

