FROM php:7.1-fpm-alpine
LABEL owner="Giancarlos Salas"
LABEL maintainer="giansalex@gmail.com"

RUN apk update && apk add --no-cache \
    libgcc libstdc++ libx11 glib libxrender libxext libintl \
    libcrypto1.0 libssl1.0 \
    ttf-dejavu ttf-droid ttf-freefont ttf-liberation ttf-ubuntu-font-family && \
    apk add --no-cache --virtual .build-green-deps \
    openssl \
    git \
    unzip \
    curl \
    libxml2-dev \
    zlib-dev \
    ca-certificates \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev zlib-dev libxpm-dev && \
    update-ca-certificates

# wkhtmltopdf
RUN wget https://raw.githubusercontent.com/madnight/docker-alpine-wkhtmltopdf/master/wkhtmltopdf --no-check-certificate && \
    mv wkhtmltopdf /bin && \
    chmod +x /bin/wkhtmltopdf

RUN docker-php-ext-install soap && \
    docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install opcache && \
    docker-php-ext-install gd && \
    docker-php-ext-install zip && \
    curl --silent --show-error -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV APP_ENV prod
ENV APP_SECRET c4136a0540553455b122461ab6923e9d
ENV WKHTMLTOPDF_PATH wkhtmltopdf
ENV CLIENT_TOKEN 123456
ENV SOL_USER 20000000001MODDATOS
ENV SOL_PASS moddatos
ENV CORS_ALLOW_ORIGIN *
ENV FE_URL https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService
ENV RE_URL https://e-beta.sunat.gob.pe/ol-ti-itemision-otroscpe-gem-beta/billService
ENV GUIA_URL https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService

RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
#RUN echo 'PassEnv APP_ENV APP_SECRET' > /etc/apache2/conf-enabled/expose-env.conf
COPY docker/config/opcache.ini $PHP_INI_DIR/conf.d/
COPY docker/config/symfony.ini $PHP_INI_DIR/conf.d/
COPY . /var/www/html/

VOLUME /var/www/html/data

RUN chmod -R 777 ./data && \
    composer install --no-interaction --no-dev --optimize-autoloader && \
    php bin/console cache:clear --env=prod --no-debug  && \
    composer dump-autoload --optimize --no-dev --classmap-authoritative

RUN apk del .build-green-deps && \
    rm -rf /var/cache/apk/*

WORKDIR /var/www/html/public