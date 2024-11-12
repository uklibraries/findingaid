# PHP build
FROM composer:2.8.2 AS php-build

WORKDIR /app

COPY composer.json composer.lock /app/

RUN composer install --no-dev --optimize-autoloader

# JS build
FROM node:lts AS js-minify

WORKDIR /app

RUN corepack enable

RUN yarn add esbuild 

COPY ./app/assets/js /app

RUN ./node_modules/.bin/esbuild manifest.js --minify --keep-names --bundle --sourcemap --target=chrome58,firefox57,safari11,edge16 --format=iife --tree-shaking=true --outfile=public/js/app.js

# Put the JS and PHP builds together
FROM php:7.4-apache

WORKDIR /app

COPY . .

COPY --from=php-build /app /app
COPY --from=js-minify /app/public/js /app/public/js

RUN ln -s /app/public /var/www/html/findingaid

RUN mkdir ./public/cache
RUN chown -R www-data:www-data ./public/cache
RUN chown -R www-data:www-data /var/www/html/

CMD ["apache2-foreground"]
