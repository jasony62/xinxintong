FROM php:7.3-fpm

# 设置时区
RUN ln -sf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime 

COPY sources.list /etc/apt/sources.list 

RUN apt-get update && apt-get install -y --allow-downgrades --allow-remove-essential \
  zlib1g=1:1.2.11.dfsg-1+deb10u2 libtinfo6=6.1+20181013-2+deb10u5 \
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