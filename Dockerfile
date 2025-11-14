FROM alpine:3.20 AS jsmin

RUN apk add --no-cache \
    git \
    gcc \
    libc-dev

RUN git clone https://github.com/douglascrockford/JSMin /tmp/jsmin && \
    gcc /tmp/jsmin/jsmin.c -o /usr/bin/jsmin && \
    rm -rf /tmp/jsmin

FROM php:8.3-fpm-alpine AS development

# add other deps for dev here
RUN apk add --no-cache \
    libzip-dev \
    bash

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=jsmin /usr/bin/jsmin /usr/bin/jsmin

WORKDIR /opt/findingaid

COPY ./composer.json .
COPY ./composer.lock .

RUN composer install --no-interaction

COPY /exe ./exe

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM php:8.3-fpm-alpine AS prod-builder

RUN apk add --no-cache bash

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
COPY --from=jsmin /usr/bin/jsmin /usr/bin/jsmin

WORKDIR /composer

COPY ./composer.json .
COPY ./composer.lock .

RUN composer install --no-interaction --no-dev

FROM php:8.3-fpm-alpine AS ci

RUN apk add --no-cache \
    libzip-dev \
    bash

WORKDIR /app

COPY --from=jsmin /usr/bin/jsmin /usr/bin/jsmin
COPY --from=development /opt/findingaid/vendor /opt/findingaid/vendor
COPY ./phpunit.xml /opt/findingaid/phpunit.xml
COPY /app .

COPY exe/build.sh /opt/findingaid/exe/build.sh
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm", "-F"]

FROM php:8.3-fpm-alpine AS production

RUN apk add --no-cache \
    libzip-dev \
    bash

COPY --from=jsmin /usr/bin/jsmin /usr/bin/jsmin
COPY --from=prod-builder /composer/vendor /opt/findingaid/vendor

WORKDIR /opt/findingaid

COPY ./app ./app
COPY ./public ./public
COPY ./exe ./exe

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN ./exe/build.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm", "-F"]
