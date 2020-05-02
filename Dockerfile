FROM php:7.4-alpine3.11
LABEL owner="Giancarlos Salas"
LABEL maintainer="giansalex@gmail.com"

EXPOSE 8000
WORKDIR /var/www/html
ENV APP_ENV prod
ENV APP_SECRET c4136a0540553455b122461ab6923e9d
ENV WKHTMLTOPDF_PATH wkhtmltopdf
ENV CLIENT_TOKEN 123456
ENV SOL_USER 20000000001MODDATOS
ENV SOL_PASS moddatos
ENV CORS_ALLOW_ORIGIN .
ENV FE_URL https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService
ENV RE_URL https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService
ENV GUIA_URL https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService

# Install deps
RUN apk update && apk add --no-cache wkhtmltopdf ttf-droid libzip

# Install php dev dependencies
RUN apk add --no-cache --virtual .build-green-deps \
    git \
    unzip \
    curl \
    libzip-dev libxml2-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev libxpm-dev

# Configure php extensions
RUN docker-php-ext-install soap && \
    docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install opcache && \
    docker-php-ext-install gd && \
    docker-php-ext-install zip && \
    docker-php-ext-install pcntl

COPY docker/config/* $PHP_INI_DIR/conf.d/
COPY . .

# Install Packages
RUN curl --silent --show-error -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-interaction --no-dev -o -a && \
    composer require php-pm/php-pm && \
    composer dump-autoload --optimize --no-dev --classmap-authoritative && \
    composer dump-env prod && \
    chmod -R 755 ./data && \
    chmod -R 755 ./var
