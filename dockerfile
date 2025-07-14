FROM alpine:3.20 AS builder

RUN apk add --no-cache \
	git \
	gcc \
	libc-dev

WORKDIR /tmp

RUN git clone https://github.com/douglascrockford/JSMin && \
	cd JSMin && \
	gcc jsmin.c -o jsmin && \
	mv jsmin /usr/bin/jsmin

FROM php:8.0-fpm-alpine AS deps

RUN apk add --no-cache \
	libzip-dev

RUN docker-php-ext-install zip

COPY --from=composer:lts /usr/bin/composer /usr/bin/composer
COPY --from=builder /usr/bin/jsmin /usr/bin/jsmin

WORKDIR /app

COPY ./composer.json .
COPY ./composer.lock .

FROM deps AS dev

# add other packages needed for dev here
RUN apk add --no-cache bash

WORKDIR /app

RUN composer install --no-interaction

COPY . .

RUN bash exe/build.sh

RUN mkdir -p public/cache && \
	chown -R www-data:www-data public/cache

EXPOSE 9000

CMD ["php-fpm"]

FROM deps AS prod

RUN apk add --no-cache \
	bash

WORKDIR /app

RUN composer install --no-dev --no-interaction

COPY . .

RUN mkdir -p public/cache && \
	chown -R www-data:www-data public/cache

RUN bash exe/build.sh

USER www-data

# Will be overwritten by docker-compose if port is specified there
EXPOSE 9000

CMD ["php-fpm"]
