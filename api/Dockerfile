FROM node:16.9.1-alpine3.14

RUN mkdir -p /usr/src/app

WORKDIR /usr/src/app

RUN npm install -g cnpm --registry=https://registry.npm.taobao.org

COPY ./package.json ./package.json

RUN cnpm install --production

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories

# 修改时区
RUN apk update && apk add bash tzdata \
  && cp -r -f /usr/share/zoneinfo/Asia/Shanghai /etc/localtime

# 复制应用代码
COPY ./server.js ./server.js
COPY ./config ./config
COPY ./controllers ./controllers
COPY ./.env ./.env

CMD [ "node", "./server.js"]