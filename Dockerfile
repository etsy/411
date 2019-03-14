FROM php:7-apache AS builder
ARG COMPOSER=composer-es2x.json
WORKDIR /app

RUN apt-get update && \
    apt-get -y install git sqlite3 gnupg

RUN curl -sL https://deb.nodesource.com/setup_11.x | bash -
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

RUN apt-get -y install unzip nodejs
RUN npm install -g grunt-cli bower

COPY . .

RUN COMPOSER=${COMPOSER} composer install --no-dev --ignore-platform-reqs --optimize-autoloader
RUN npm install
RUN bower --allow-root install
RUN grunt prod

WORKDIR /data
RUN sqlite3 /data/data.db < /app/db.sql
RUN ln -sf /data/data.db /app/data.db

RUN cp /app/config_example.php /data/config.php
RUN sed -i 's/localhost:9200/es:9200/g' /data/config.php
RUN ln -sf /data/config.php /app/config.php

RUN cp /app/411.conf /data/411.conf
RUN sed -i 's/HOSTNAME/fouroneone/g' /data/411.conf
RUN sed -i 's/# SetEnv FOURONEONEHOST/SetEnv FOURONEONEHOST/g' /data/411.conf
RUN sed -i 's$/var/www/411$/app$g' /data/411.conf

RUN cp /app/docker/supervisord.conf /data/supervisord.conf
RUN cp /app/docker/mail.ini /data/mail.ini
RUN cp /app/docker/411_cron /data/411_cron
RUN /app/docker/init.php

RUN rm -rf /app/docker


FROM php:7-apache
WORKDIR /app

RUN chown www-data:www-data .

RUN apt-get update && \
    apt-get -y install \
    libxml2-dev \
    libcurl4-openssl-dev \
    sqlite3 \
    libsqlite3-dev \
    cron \
    supervisor

RUN docker-php-ext-configure \
    pdo_mysql --with-pdo-mysql=mysqlnd
RUN docker-php-ext-install \
    xml \
    pdo_mysql \
    pdo_sqlite \
    mbstring \
    curl \
    pcntl

RUN a2enmod headers rewrite

COPY --chown=www-data --from=builder /app .
COPY --chown=www-data --from=builder /data /data

RUN ln -sf /data/411.conf /etc/apache2/sites-available/000-default.conf

RUN ln -sf /data/mail.ini /usr/local/etc/php/conf.d/
RUN ln -sf /data/411_cron /etc/cron.d/

VOLUME /data
EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/data/supervisord.conf"]
