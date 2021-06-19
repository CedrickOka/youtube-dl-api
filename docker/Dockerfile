ARG VERSION=7.4

FROM composer:2.0 as composer

FROM php:${VERSION}-fpm-alpine3.13 as dev
LABEL vendor="cedrickoka/youtube-dl-api" maintainer="okacedrick@gmail.com" version="4.0.0"

WORKDIR /app

## Install system dependencies
RUN apk update && \
    apk add --no-cache --virtual dev-deps \
	    autoconf=2.69-r3 \
	    gcc=10.2.1_pre1-r3 \
		git=2.30.2-r0 \
	    g++=10.2.1_pre1-r3 \
	    make=4.3-r0 && \
    apk add --no-cache \
		ffmpeg=4.3.1-r4 \
    	icu-dev=67.1-r2 \
    	gettext=0.20.2-r2 \
    	libxml2-dev=2.9.10-r7 \
    	libzip-dev=1.7.3-r2 \
## Uncomment the following line to install rabbitmq libs
## then don't forget to activate the amqp php extension
#    	rabbitmq-c-dev=0.10.0-r1 \
		python3=3.8.10-r0 \
		py3-setuptools=51.3.3-r0 \
    	supervisor=4.2.1-r0 \
    	zlib-dev=1.2.11-r3

SHELL ["/bin/sh", "-xo", "pipefail", "-c"]

## Install php extensions
RUN set -ex && \
	pecl install apcu xdebug && \
    docker-php-ext-enable apcu xdebug && \
    docker-php-ext-install intl opcache sysvmsg

## Copy php default configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
	echo "xdebug.mode=coverage" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"

COPY docker/php/overrides.ini "$PHP_INI_DIR/conf.d/99-overrides.ini"
COPY docker/php/overrides.conf /usr/local/etc/php-fpm.d/z-overrides.conf
#COPY docker/php/overrides.conf.template /usr/local/etc/php-fpm.d/z-overrides.conf.template
COPY docker/docker-symfony-entrypoint.sh /usr/local/bin/docker-symfony-entrypoint.sh

## Install youtube-dl
RUN wget -q https://yt-dl.org/latest/youtube-dl -O /usr/local/bin/youtube-dl && \
	wget -q https://raw.githubusercontent.com/eficode/wait-for/v2.1.2/wait-for -O /usr/local/bin/wait-for && \
	chmod a+x /usr/local/bin/youtube-dl /usr/local/bin/docker-symfony-entrypoint.sh /usr/local/bin/wait-for && \
	ln -s /usr/bin/python3 /usr/bin/python && \
	hash -r

ENTRYPOINT ["docker-symfony-entrypoint.sh"]
CMD ["php-fpm"]

ENV LC_ALL=C.UTF-8

ENV APP_ENV=dev
ENV MESSENGER_TRANSPORT_DSN=semaphore://localhost%kernel.project_dir%/.env
ENV ASSETS_DIR=/opt/youtube-dl/downloads
ENV FILE_UNIX_OWNER=www-data

ENV SUPERVISOR_NUMBER_PROCESSES=1

## Add youtube-dl and supervisor config file
COPY docker/youtube-dl.conf /etc/youtube-dl.conf
COPY docker/supervisor/messenger.template /etc/supervisor.d/messenger.template

RUN mkdir -p ${ASSETS_DIR} && \
	chown -R www-data:www-data ${ASSETS_DIR} && \
	chmod -R 0755 /etc/youtube-dl.conf ${ASSETS_DIR}

FROM dev as test

WORKDIR /app

ENV APP_ENV=test

## Install composer
COPY --from=composer /usr/bin/composer /usr/local/bin/

## Copy project files to workdir
COPY . .

## Install application dependencies for run test units
RUN set -ex && \
	composer install --no-interaction --optimize-autoloader --classmap-authoritative && \
    composer clear-cache

## Change files owner to php-fpm default user
RUN chown -R www-data:www-data .

FROM test as prod

WORKDIR /app

ENV APP_ENV=prod

## Install application without dev dependencies
RUN set -ex && \
	composer install --no-dev --no-interaction --optimize-autoloader --classmap-authoritative && \
    composer clear-cache

## Change files owner to php-fpm default user
RUN chown -R www-data:www-data .

## Configure crontab
COPY docker/crontab /crontab
RUN /usr/bin/crontab -u www-data /crontab

## Cleanup & Disable xdebug on production
RUN set -ex && \
	apk del dev-deps && \
	rm  -rf /tmp/pear && \
    rm /usr/local/bin/composer && \
	rm "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"