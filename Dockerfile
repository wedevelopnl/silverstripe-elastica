FROM php:7.2-fpm-alpine

RUN apk update && apk upgrade\
   wget

RUN apk add mysql-client --update --no-cac
RUN apk add wget curl git php php-curl php-openssl php-json php-phar php-dom php-intl --update && rm /var/cache/apk/*
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer
RUN composer global require hirak/prestissimo
RUN apk add --update nodejs nodejs-npm

ENV GD_DEPS freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev
ENV INTL_DEPS icu-dev
ENV XSL_DEPS libxslt-dev
ENV TIDY_DEPS tidyhtml-dev
ENV ZIP_DEPS zlib-dev
ENV SS_DEPS bash
ENV MAKE_DEPS make
RUN set -xe \
    && apk add --no-cache $GD_DEPS $INTL_DEPS $XSL_DEPS $TIDY_DEPS $ZIP_DEPS $SS_DEPS $MAKE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) bcmath gd intl mysqli pdo_mysql soap tidy xsl zip

WORKDIR /var/www

#COPY composer.json composer.lock ./
#
#RUN set -eux; \
#    composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress --no-suggest; \
#    composer clear-cache
#
#COPY app app/
#COPY public public/
#COPY themes themes/
#
#RUN set -eux; \
#    composer dump-autoload --classmap-authoritative --no-dev; \
#    composer run-script --no-dev post-install-cmd; \
#    composer vendor-expose copy
#
#RUN chown -R www-data:www-data public

CMD ["php-fpm"]
