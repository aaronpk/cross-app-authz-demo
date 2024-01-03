FROM mariadb:latest

COPY ./docker/sql /docker-entrypoint-initdb.d