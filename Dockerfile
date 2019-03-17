FROM php:7.3-apache
LABEL owner="Giancarlos Salas"
LABEL maintainer="giansalex@gmail.com"

RUN apt-get update && \
    apt-get install -y --no-install-recommends wkhtmltopdf wget libzip-dev libxml2-dev git unzip libfreetype6-dev libjpeg62-turbo-dev && \
    docker-php-ext-install soap && \
    docker-php-ext-install zip && \
    docker-php-ext-configure opcache --enable-opcache && \
    docker-php-ext-install opcache && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) gd && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite && \
    curl --silent --show-error -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN wget https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    tar xvf wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    mv wkhtmltox/bin/wkhtmlto* /usr/bin/ && \
    ln -nfs /usr/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf && \
    rm wkhtmltox-0.12.4_linux-generic-amd64.tar.xz && \
    rm -rf wkhtmltox

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

RUN echo 'PassEnv APP_ENV APP_SECRET' > /etc/apache2/conf-enabled/expose-env.conf 
COPY docker/config/opcache.ini $PHP_INI_DIR/conf.d/
COPY docker/config/php.ini /usr/local/etc/php/
COPY . /var/www/html/

VOLUME /var/www/html/data
WORKDIR /var/www/html

RUN composer install --no-interaction --no-dev --optimize-autoloader && \
    php bin/console cache:clear --env=prod --no-debug  && \
    composer dump-autoload --optimize --no-dev --classmap-authoritative && \
    composer dump-env prod && \
    chmod -R 777 ./data && \
    chmod -R 777 ./var
