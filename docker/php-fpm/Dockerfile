FROM php:7.3-fpm

COPY sources.list /etc/apt/sources.list 

RUN apt-get update && apt-get install -y \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    ffmpeg \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include \
    && docker-php-ext-install -j$(nproc) gd

RUN docker-php-ext-install mysqli bcmath zip

RUN docker-php-ext-install exif && docker-php-ext-enable exif 

# 安装composeer

RUN php -r "copy('https://install.phpcomposer.com/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    mv composer.phar /usr/local/bin/composer && \
    composer config -g repo.packagist composer https://packagist.phpcomposer.com

WORKDIR /usr/share/nginx/html

ADD ./start_php-fpm.sh /usr/local/bin/start_php-fpm.sh

RUN chmod +x /usr/local/bin/start_php-fpm.sh

CMD ["start_php-fpm.sh"]