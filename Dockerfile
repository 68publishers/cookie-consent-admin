FROM 68publishers/php:8.3-cli-dev-1.0.0 AS worker

USER root

RUN apk add --update --no-cache supervisor \
    && mkdir -p /etc/supervisor/logs \
    && chown www-data:www-data /etc/supervisor/logs

COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisor/supervisord.conf"]

USER www-data
