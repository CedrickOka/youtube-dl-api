#!/bin/sh
set -e

printenv | sed 's/^\([^=]*\)\=\(.*\)$/\1\="\2"/g' | sed 's/^\(_\)\=\(.*\)$//g' > "/app/.env.${APP_ENV}.local"

if [ "${APP_ENV}" != "test" ]; then
	## Decomments here if database configuration is necessary
	#php /app/bin/console doctrine:database:create -e --if-not-exists && \
	#php /app/bin/console doctrine:schema:update -e --force && \
	
	## Install the bundle assets
	php /app/bin/console assets:install public -e --symlink --relative && \
	
	## Clear env cache
	php /app/bin/console cache:clear -e --no-debug && \
	
	## Configure php-fpm pool conf
	#if [ ! -z $(grep "PM_MAX_CHILDREN" "/usr/local/etc/php-fpm.d/z-overrides.conf.template") ]; then 
	#	export PM_MAX_CHILDREN=${PM_MAX_CHILDREN:-100} PM_MAX_REQUESTS=${PM_MAX_REQUESTS:-200} && \
	#	envsubst "$$PM_MAX_CHILDREN $$PM_MAX_REQUESTS" < /usr/local/etc/php-fpm.d/z-overrides.conf.template > /usr/local/etc/php-fpm.d/z-overrides.conf;
	#fi
	
	envsubst < /etc/youtube-dl.conf > /etc/youtube-dl.conf
	
	## Add messenger confid and decomments here if supervisor configuration is necessary
	envsubst < /etc/supervisor.d/messenger.template > /etc/supervisor.d/messenger.ini && \
	supervisord -c /etc/supervisord.conf && \
	
	## Decomments here if cron configuration is necessary
	/usr/sbin/crond -b -l 8 -L /dev/stdout && \
	
	chown -R www-data:www-data /app/var/
fi

## Start PHP-FPM daemon
# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- php-fpm "$@"
fi

exec "$@"
