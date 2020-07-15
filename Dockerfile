FROM debian:jessie

ARG DEBIAN_FRONTEND=noninteractive

# install PHP 7.3 Repository
RUN apt-get update -q && \
    apt-get install -qqy --no-install-recommends wget apt-transport-https lsb-release ca-certificates
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# install packages
RUN apt-get update && apt-get install -y --fix-missing \
        vim \
        less \
        curl \
        php7.3-cli \
        php7.3-curl \
        php7.3-dev \
        php7.3-fpm \
        php7.3-gd \
        php7.3-intl \
        php7.3-json \
        php7.3-mbstring \
        php7.3-mysql \
        php7.3-readline \
        php7.3-redis \
        php7.3-tidy \
        php7.3-xdebug \
        php7.3-xml \
        php7.3-xmlrpc \
        php7.3-zip \
        nginx \
        ssl-cert \
        wget \
        telnet \
        git \
        iptables \
        sudo \
        gnupg2

# set php 7.3 as default cli
RUN update-alternatives --set php /usr/bin/php7.3

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Blackfire CLI tool and PHP Probe
RUN wget -q -O - https://packages.blackfire.io/gpg.key | apt-key add -
RUN echo "deb http://packages.blackfire.io/debian any main" | tee /etc/apt/sources.list.d/blackfire.list
RUN apt-get update && apt-get install -y blackfire-agent blackfire-php

# create some dirs
RUN mkdir -p /var/log/php7-fpm/tyres
RUN mkdir -p /run/php

# add tyres user, set dummy password for sudo access
RUN useradd -m --shell /bin/bash tyres
RUN echo "tyres ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

# fpm and nginx
RUN ln -s /home/tyres/backoffice/docker/nginx/sites-enabled/backoffice-dev.reifen.check24.de /etc/nginx/sites-enabled/backoffice-dev.reifen.check24.de
RUN ln -s /home/tyres/backoffice/docker/php/pool-tyres.conf /etc/php/7.3/fpm/pool.d/pool-tyres.conf
RUN rm /etc/nginx/sites-enabled/default
RUN rm /etc/php/7.3/fpm/pool.d/www.conf
RUN rm /etc/php/7.3/mods-available/xdebug.ini
RUN ln -s /home/tyres/backoffice/docker/php/xdebug.ini /etc/php/7.3/mods-available/xdebug.ini
RUN rm /etc/php/7.3/fpm/php.ini
RUN ln -s /home/tyres/backoffice/docker/php/php.ini /etc/php/7.3/fpm/php.ini
# increase variables hash max size in nginx
RUN sed -i -e"s/^http\\s.$/http \{\n\tvariables_hash_max_size 1024;/" /etc/nginx/nginx.conf

# apcu
#RUN pecl install apcu
#RUN ln -s /home/tyres/backoffice/docker/php/apcu.ini /etc/php/7.3/cli/conf.d/20-apcu.ini
#RUN ln -s /home/tyres/backoffice/docker/php/apcu.ini /etc/php/7.3/fpm/conf.d/20-apcu.ini

# ssl
RUN mkdir -p /etc/nginx/ssl
COPY docker/nginx/ssl/* /etc/nginx/ssl/
WORKDIR /etc/nginx/ssl
RUN chmod 640 *.key
RUN chmod 644 *.crt
RUN cp /etc/nginx/ssl/*.crt /usr/local/share/ca-certificates/
RUN update-ca-certificates

# set correct timezone
RUN echo "Europe/Berlin" > /etc/timezone && dpkg-reconfigure -f noninteractive tzdata

# su tyres
RUN echo "alias tyres='cd /home/tyres/backoffice;su tyres'" > /root/.bashrc

# git branch prompt
USER tyres
WORKDIR /home/tyres
RUN touch .bashrc
RUN echo "parse_git_branch() {" >> .bashrc
RUN echo '     git branch 2> /dev/null | sed -e "/^[^*]/d" -e "s/* \(.*\)/ (\\1)/"' >> .bashrc
RUN echo "}" >> .bashrc
RUN echo 'export PS1="\u@\h \[\033[32m\]\w\[\033[33m\]\$(parse_git_branch)\[\033[00m\] $ "' >> .bashrc
RUN echo 'export PHP_IDE_CONFIG="serverName=backoffice-dev.reifen.check24.de"' >> .bashrc
RUN echo 'export APP_ENV="dev"' >> .bashrc
RUN echo "alias ll='ls -la --color'" >> .bashrc

USER root
RUN echo "PS1='\[\033[1;33m\]\w\[\033[1;31m\] \u > \[\033[0m\]'" >> /root/.bashrc

ENTRYPOINT nginx && php-fpm7.3 && tail -f /dev/null
