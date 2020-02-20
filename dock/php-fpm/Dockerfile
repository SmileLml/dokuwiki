ARG PHP_VERSION

FROM php:${PHP_VERSION}-fpm

LABEL maintainer="SmileLml <liumingliang@qie.tv>"

RUN sed -i 's/deb.debian.org/mirrors.tuna.tsinghua.edu.cn/' /etc/apt/sources.list && \
    sed -i 's/security.debian.org/mirrors.tuna.tsinghua.edu.cn/' /etc/apt/sources.list && \
    sed -i 's/security-cdn.debian.org/mirrors.tuna.tsinghua.edu.cn/' /etc/apt/sources.list

ENV DEBIAN_FRONTEND noninteractive

RUN set -xe; \
    apt-get update -yqq && \
    apt-get upgrade -yqq && \
    pecl channel-update pecl.php.net

###########################################################################
# 设置时区
###########################################################################

RUN ln -snf /usr/share/zoneinfo/PRC /etc/localtime && echo PRC > /etc/timezone

###########################################################################
# zip:
###########################################################################

RUN apt-get install -yqq --assume-yes \
        apt-utils wget \
        libzip-dev zip unzip && \
        docker-php-ext-configure zip --with-libzip && \
        docker-php-ext-install zip

###########################################################################
# bcmath:
###########################################################################

ARG INSTALL_BCMATH=false

RUN if [ ${INSTALL_BCMATH} = true ]; then \
    docker-php-ext-install bcmath \
;fi

###########################################################################
# bz2:
###########################################################################

ARG INSTALL_BZ2=false

RUN if [ ${INSTALL_BZ2} = true ]; then \
    apt-get install -y libbz2-dev && \
    docker-php-ext-install bz2 \
;fi

###########################################################################
# exif:
###########################################################################

ARG INSTALL_EXIF=false

RUN if [ ${INSTALL_EXIF} = true ]; then \
    # Enable Exif PHP extentions requirements
    docker-php-ext-install exif \
;fi

###########################################################################
# gd:
###########################################################################

ARG INSTALL_GD=false
RUN if [ ${INSTALL_GD} = true ]; then \
    apt-get install -y libpng-dev && \
    docker-php-ext-install gd \
;fi

###########################################################################
# opcache:
###########################################################################

ARG INSTALL_OPCACHE=false

RUN if [ ${INSTALL_OPCACHE} = true ]; then \
    docker-php-ext-install opcache \
;fi

COPY ./opcache.ini /usr/local/etc/php/conf.d/opcache.ini

###########################################################################
# ssh2:
###########################################################################

ARG INSTALL_SSH2=false

RUN if [ ${INSTALL_SSH2} = true ]; then \
    # Install the ssh2 extension
    apt-get -y install libssh2-1-dev && \
    wget http://pecl.php.net/get/ssh2-1.2.tgz && \
    pecl install ssh2-1.2.tgz && \
    docker-php-ext-enable ssh2 \
;fi


###########################################################################
# xsl:
###########################################################################

ARG INSTALL_XSL=false

RUN if [ ${INSTALL_XSL} = true ]; then \
    apt-get -y install libxslt-dev && \
    docker-php-ext-install xsl \
;fi

###########################################################################
# libfaketime:
###########################################################################

ARG INSTALL_FAKETIME=false

RUN if [ ${INSTALL_FAKETIME} = true ]; then \
    apt-get install -y libfaketime && \
    echo "/usr/lib/x86_64-linux-gnu/faketime/libfaketime.so.1" > /etc/ld.so.preload \
;fi

COPY ./default.ini /usr/local/etc/php/conf.d

USER root

RUN apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* && \
    rm /var/log/lastlog /var/log/faillog

ARG PUID=1000
ENV PUID ${PUID}
ARG PGID=1000
ENV PGID ${PGID}

RUN groupmod -o -g ${PGID} www-data && \
    usermod -o -u ${PUID} -g www-data www-data

WORKDIR /var/www

CMD ["php-fpm"]

EXPOSE 9000
