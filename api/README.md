提供 API

# API

> curl -X POST "http://localhost:3000/api/site/fe/matter/article/event/logAccess" -H "content-type: application/json" -d '{"articleId":1, "uid":"jasony62","uname":"hello"}'

# 配置

| 项目   | 说明     | 默认值 |
| ------ | -------- | ------ |
| 端口号 | 服务端口 | 3000   |

# 容器操作

## 更新代码

在项目目录下执行

> docker cp $PWD/api/controllers xxt_backapi:/usr/src/app/

> docker-compose restart backapi
