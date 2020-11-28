FROM comhon/comhon:0.2

RUN apt-get install -y postgresql postgresql-client \
    && localedef -i en_US -f UTF-8 en_US.UTF-8 

USER postgres

RUN /etc/init.d/postgresql start \
    && psql --command "CREATE USER root WITH SUPERUSER PASSWORD 'root';" \
    && createdb -O root database

USER root

COPY ./docker/sample/start ./start
RUN chmod -R 755 ./start

COPY ./docker/sample/assets .
RUN chown -R www-data:www-data ./config/
RUN chown -R www-data:www-data ./manifests/manifest
RUN chown -R www-data:www-data ./manifests/serialization
RUN chown    www-data:www-data ./manifests/options
RUN chown -R www-data:www-data ./manifests/options/Sample
RUN chown -R www-data:www-data ./sample/

ENTRYPOINT ["/var/www/html/start/script/start.sh"]
