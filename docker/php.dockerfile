FROM sirajul/php:worker-74-latest

RUN apt-get update

RUN docker-php-ext-install bcmath

RUN docker-php-ext-install sockets

COPY ./docker/worker.conf /etc/supervisor/conf.d/worker.conf

RUN mkdir /app

WORKDIR /app
