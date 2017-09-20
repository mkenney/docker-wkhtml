FROM php:apache

ENV TERM "xterm"

ENV WK_HASH "049b2cdec9a8254f0ef8ac273afaf54f7e25459a273e27189591edc7d7cf29db"
ENV WK_URL "https://downloads.wkhtmltopdf.org/0.12/0.12.4"
ENV WK_PKG "wkhtmltox-0.12.4_linux-generic-amd64.tar.xz"

# System update and configuration
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
        libjpeg62-turbo \
        libxext6 \
        libxrender-dev \
        wget \
        xauth \
        xfonts-100dpi \
        xfonts-75dpi \
        xfonts-base \
        --no-install-recommends \

    # Allow header overrides in .htaccess files
    && a2enmod headers \
    && a2enmod rewrite

# Install patched wkhtml
RUN set -x \
    && cd /usr/share \
    && wget -nv "$WK_URL/$WK_PKG" \
    && echo "$WK_HASH  $WK_PKG" > wkhtml.hsh \
    && sha256sum "$WK_PKG" \
    && sha256sum -c wkhtml.hsh \
    && mkdir wkhtmltox \
    && tar -xf $WK_PKG -C wkhtmltox --strip-components 1 \
    && rm -f $WK_PKG \
    && ln -s /usr/share/wkhtmltox/bin/wkhtmltoimage /usr/bin/wkhtmltoimage \
    && ln -s /usr/share/wkhtmltox/bin/wkhtmltopdf /usr/bin/wkhtmltopdf

# Add resources
COPY _image/etc/apache2/sites-enabled/vhost.conf /etc/apache2/sites-enabled/wkhtml.conf
COPY _image/etc/php.ini /usr/local/etc/php/php.ini
COPY _image/var/www /var/www
WORKDIR /var/www/html
EXPOSE 80

CMD . /etc/apache2/envvars && /usr/sbin/apache2 -D FOREGROUND
