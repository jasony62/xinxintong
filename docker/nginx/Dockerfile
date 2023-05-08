# 标准基础镜像。添加了vts模块。
FROM jasony62/nginx-vts

# 设置时区
RUN sed -i 's?http://dl-cdn.alpinelinux.org/?https://mirrors.aliyun.com/?' /etc/apk/repositories && \
  apk add -U tzdata && \
  cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && \
  apk del tzdata

# 修改配置文件
ADD ./nginx.conf.template /etc/nginx/nginx.conf.template

ADD ./start_nginx.sh /usr/local/bin/start_nginx.sh

RUN chmod +x /usr/local/bin/start_nginx.sh

CMD ["start_nginx.sh"]