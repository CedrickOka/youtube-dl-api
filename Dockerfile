FROM ubuntu:bionic
LABEL vendor="drive/youtube-dl-api" maintainer="cedric.baidai@veone.net" version="1.0.0"

# Fix debconf warnings upon build
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
	&& apt-get install -y cron ffmpeg nano python software-properties-common wget \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN wget https://yt-dl.org/downloads/latest/youtube-dl -O /usr/local/bin/youtube-dl \
	&& chmod a+rx /usr/local/bin/youtube-dl \
	&& hash -r

ARG DATA_PATH=/opt/data/youtube-dl

COPY youtube-dl.conf /etc/youtube-dl.conf
RUN chmod 0755 /etc/youtube-dl.conf

# create cron log
RUN touch /var/log/cron.log \
	&& ln -sf /dev/stdout /var/log/cron.log

# add crontab file
ADD cron /etc/cron.d/cron
RUN chmod 0644 /etc/cron.d/cron
RUN /usr/bin/crontab /etc/cron.d/cron

ADD entrypoint.sh /entrypoint.sh
RUN chmod 0777 /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
