FROM node:18-alpine

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.ustc.edu.cn/g' /etc/apk/repositories

# 安装基础工具和修改时区
RUN apk update && apk add bash tzdata \
  && cp -r -f /usr/share/zoneinfo/Asia/Shanghai /etc/localtime

# 修改npm源
RUN npm config set registry https://registry.npmmirror.com

# 安装基础包
RUN npm install --location=global pnpm typescript

# 应用目录
RUN mkdir -p /usr/src/app
WORKDIR /usr/src/app

# 安装应用包
COPY ./package.json ./package.json
RUN pnpm i

# 复制应用代码
COPY ./tsconfig.json ./tsconfig.json
COPY ./tsconfig.build.json ./tsconfig.build.json
COPY ./src ./src
RUN pnpm build

COPY ./config ./config
# COPY ./.env ./.env
COPY ./lowdb.json ./_lowdb.json

CMD [ "node", "./dist/server"]