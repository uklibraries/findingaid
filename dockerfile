FROM composer:lts AS composer

FROM alpine:latest AS builder

RUN apk add --no-cache \
	git \
	gcc \
	libc-dev

WORKDIR /tmp

RUN git clone https://github.com/douglascrockford/JSMin && \
	cd JSMin && \
	gcc jsmin.c -o jsmin && \
	mv jsmin /usr/bin/jsmin

FROM php:8.0-fpm-alpine AS dev

RUN apk add --no-cache \
	libzip-dev \
	bash

RUN docker-php-ext-install zip

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=builder /usr/bin/jsmin /usr/bin/jsmin

WORKDIR /app

COPY entrypoint.sh /usr/local/bin
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY ./composer.json .
COPY ./composer.lock .

RUN composer install

COPY . .

RUN bash exe/build.sh

RUN mkdir -p public/cache && \
	chown -R www-data:www-data public/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["php-fpm"]

