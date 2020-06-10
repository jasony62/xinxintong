#!/bin/sh

echo "启动 Nginx server for xxt"

envsubst '$NGINX_TMS_FINDER_BASE $NGINX_TMS_FINDER_ADDRESS' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && exec nginx -g 'daemon off;'
