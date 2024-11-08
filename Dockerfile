# PHP build
FROM composer:2.8.2 AS php-build

WORKDIR /app

COPY composer.json composer.lock /app/

RUN composer install --no-dev --optimize-autoloader

# JS build
FROM node:lts AS js-build

WORKDIR /app

RUN npm install -g esbuild

COPY . /app
RUN npx esbuild app/assets/js/manifest.js --bundle --minify --outfile=public/js/app.js

# Put the JS and PHP builds together
FROM php:7.4-apache

WORKDIR /app

COPY --from=php-build /app /app
COPY --from=js-build /app/public/js/app.js /app/public/js/app.js

RUN ln -s /app/public /var/www/html/findingaid

RUN mkdir ./public/cache
RUN chown -R www-data:www-data ./public/cache
RUN chown -R www-data:www-data /var/www/html/

CMD ["apache2-foreground"]