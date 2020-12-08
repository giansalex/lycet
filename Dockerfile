FROM php:8.0-alpine AS build-env

LABEL owner="Giancarlos Salas"
LABEL maintainer="giansalex@gmail.com"

WORKDIR /app
ENV APP_ENV prod

# Install php dev dependencies
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    libxml2-dev

# Install php extensions
RUN docker-php-ext-install soap && \
    docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install opcache && \
    docker-php-ext-install pcntl
    
COPY . .

# Install Packages
RUN curl --silent --show-error -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-interaction --no-dev --no-autoloader --no-scripts --no-progress --ignore-platform-reqs && \
    composer require php-pm/php-pm php-pm/httpkernel-adapter --update-no-dev --no-scripts --no-progress --ignore-platform-reqs && \
    composer dump-autoload --optimize --no-dev --classmap-authoritative && \
    composer dump-env prod --empty && \
    find -name "[Tt]est*" -type d -exec rm -rf {} + && \
    find -type f -name '*.md' -delete;

FROM php:8.0-alpine

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

ARG PHP_EXT_DIR=/usr/local/lib/php/extensions/no-debug-non-zts-20200930

# Install wkhtmltopdf
RUN apk update && apk add --no-cache \
    wkhtmltopdf \
    ttf-droid

COPY --from=build-env $PHP_EXT_DIR $PHP_EXT_DIR
COPY --from=build-env $PHP_INI_DIR/conf.d/ $PHP_INI_DIR/conf.d/
COPY --from=build-env /app .
COPY docker/config/* $PHP_INI_DIR/conf.d/
COPY docker/docker-entrypoint.sh .
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    php bin/console cache:clear && \
    chmod -R 755 ./data

ENTRYPOINT ["sh", "./docker-entrypoint.sh"]
