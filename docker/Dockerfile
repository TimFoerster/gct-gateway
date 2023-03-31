FROM bitnami/laravel:9

RUN pip install supervisor
COPY ./laravel-*.conf /etc/supervisor/conf.d/
COPY ./supervisord.conf /etc/supervisor/supervisord.conf
COPY ./entrypoint.sh /opt/entrypoint.sh
COPY ./run.sh /opt/run.sh
RUN chmod 770 /opt/entrypoint.sh
RUN chmod 770 /opt/run.sh

RUN echo "memory_limit = 4G" >> /opt/bitnami/php/etc/php.ini
RUN echo "post_max_size = 512M" >> /opt/bitnami/php/etc/php.ini
RUN echo "upload_max_filesize = 512M" >> /opt/bitnami/php/etc/php.ini
RUN echo "opcache.enable = 0" >> /opt/bitnami/php/etc/php.ini

RUN echo "opcache.enable = 0" >> /opt/bitnami/php/etc/php.ini

# ENTRYPOINT [ "/opt/entrypoint.sh" ]
CMD [ "/opt/run.sh" ]
