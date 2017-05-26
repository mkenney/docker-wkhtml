FROM php:7.0-apache

ENV TERM xterm

# System update
RUN set -x \
    && echo 'alias ll="ls -laF"' >> /root/.bashrc \
    && echo 'alias e="exit"' >> /root/.bashrc \
    && echo 'alias cls="clear"' >> /root/.bashrc \

    && apt-get -qqy update \
    && apt-get install -qqy \
        apt-utils \

    && apt-get install -qqy \
        less \
        fonts-roboto \
        fonts-averia-sans-gwf \
        fonts-beteckna \
        fonts-cabin \
        fonts-cantarell \
        fonts-crosextra-caladea \
        fonts-crosextra-carlito \
        fonts-dosis \
        fonts-fantasque-sans \
        fonts-freefont-otf \
        fonts-freefont-ttf \
        fonts-humor-sans \
        fonts-junction \
        fonts-jura \
        fonts-lato \
        fonts-mplus \
        fonts-okolaks \
        fonts-play \
        fonts-wqy-microhei \
        fonts-wqy-zenhei \
        libxrender1 \
        fontconfig \
        libxext6 \
        wkhtmltopdf \
        xvfb \

    # Allow header overrides in .htaccess files
    && a2enmod headers \
    && a2enmod rewrite

# Add resources
COPY _image/etc/apache2/sites-enabled/vhost.conf /etc/apache2/sites-enabled/wkhtml.conf
COPY _image/etc/php.ini /usr/local/etc/php/php.ini
COPY _image/var/www /var/www
WORKDIR /var/www/html
EXPOSE 80

CMD . /etc/apache2/envvars && /usr/sbin/apache2 -D FOREGROUND
