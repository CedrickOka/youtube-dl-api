FROM phpdockerio/php73-fpm:latest
WORKDIR "/app"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

# Install cron and nano
RUN apt-get update \
    && apt-get -y install cron nano \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN apt-get update \
    && apt-get -y --no-install-recommends install python software-properties-common wget \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN wget https://yt-dl.org/downloads/latest/youtube-dl -O /usr/local/bin/youtube-dl \
	&& chmod a+rx /usr/local/bin/youtube-dl \
	&& hash -r

# Install git
RUN apt-get update \
    && apt-get -y install git \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
	&& php composer-setup.php \
	&& php -r "unlink('composer-setup.php');" \
	&& mv composer.phar /usr/local/bin/composer \
	&& chmod +x /usr/local/bin/composer

RUN git clone -b master https://github.com/CedrickOka/youtube-dl-api.git ./ \
    && composer install --no-dev --no-interaction --optimize-autoloader --classmap-authoritative \
    && composer clear-cache

ENV APP_ENV=prod
ENV APP_SECRET=598d01f22edceea6bf7c5ace30929f41

COPY youtube-dl.conf /etc/youtube-dl.conf

RUN chmod 0755 /etc/youtube-dl.conf \
	&& mkdir /opt/data \
	&& mkdir /opt/data/youtube-dl \
	&& chmod -R 0777 /opt/data/youtube-dl

RUN php bin/console cache:clear -e prod --no-debug \
	&& chmod -R 0777 /app/var/

# create cron log
RUN touch /var/log/cron.log \
	&& ln -sf /dev/stdout /var/log/cron.log

# add crontab file
ADD cron /etc/cron.d/cron
RUN chmod 0644 /etc/cron.d/cron \
	&& /usr/bin/crontab /etc/cron.d/cron

ADD entrypoint.sh /entrypoint.sh
RUN chmod 0777 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
