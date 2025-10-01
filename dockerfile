FROM alpine:3.20 AS jsmin

RUN apk add --no-cache \
    git \
    gcc \
    libc-dev

RUN git clone https://github.com/douglascrockford/JSMin /tmp/jsmin && \
    gcc /tmp/jsmin/jsmin.c -o /usr/bin/jsmin && \
    rm -rf /tmp/jsmin

FROM php:8.0-fpm-alpine AS development

# add other deps for dev here
RUN apk add --no-cache \
    libzip-dev \
    bash

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=jsmin /usr/bin/jsmin /usr/bin/jsmin

WORKDIR /

COPY ./composer.json .
COPY ./composer.lock .

RUN composer install --no-interaction

COPY exe/build.sh /exe/build.sh

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM php:8.0-fpm-alpine AS prod-builder

RUN apk add --no-cache bash

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=jsmin /usr/bin/jsmin /usr/bin/jsmin

WORKDIR /app

COPY . .

FROM php:8.0-fpm-alpine AS production

WORKDIR /app

COPY --from=prod-builder /app .

COPY exe/build.sh /exe/build.sh
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm", "-F"]
