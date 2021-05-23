#!/bin/sh

echo "启动 Nginx server for xxt"

envsubst '$NGINX_HTTP_PROTOCOL,$NGINX_HTTP_HOST,$NGINX_HTTP_PORT' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'
