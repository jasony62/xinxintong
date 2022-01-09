const log4js = require('log4js')
const logger = log4js.getLogger()
const Url = require('url-parse')
const ping = require('ping')
const axios = require('axios')

const { Ctrl, ResultData, ResultFault } = require('tms-koa')
const { nanoid } = require('nanoid')

class ModelDispatch {
  constructor(mongoClient) {
    this.clDisp = mongoClient.db('tms').collection('api_dispatch')
  }

  async create(title, apiUrl, provider, expire) {
    let token = nanoid()
    /* 检查token的唯一性，有必要吗？ */
    // let cnt
    // let tries = 0
    // do {
    //   cnt = await this.clDisp.countDocuments({ token })
    // } while (cnt > 0 && ++tries <= 3)
    // if (tries === 3) return false

    let createAt = parseInt(Date.now() / 1000)
    let expireAt = createAt + expire

    await this.clDisp.insertOne({
      title, // 授权说明
      apiUrl, // 要授权执行的url
      provider, // 授权申请人的描述
      token,
      createAt, // 精确到秒
      expireAt, // 精确到秒
    })

    return token
  }

  async finish(token, posted, executor, headers) {
    let finishAt = parseInt(Date.now() / 1000)
    let updated = { finishAt, posted, headers }
    if (executor) updated.executor = executor
    await this.clDisp.updateOne({ token }, { $set: updated })
  }

  async find(page, size) {
    let tasks = await this.clDisp
      .find({}, { projection: { _id: 0 } })
      .skip((page - 1) * size)
      .limit(size)
      .toArray()

    return tasks
  }

  async findByProvider(provider, page, size) {
    let q = {
      $or: [
        { 'provider.id': parseInt(provider.id) },
        { 'provider.type': String(provider.type) },
      ],
    }
    let tasks = await this.clDisp
      .find(q, { projection: { _id: 0, provider: 0 } })
      .skip((page - 1) * size)
      .limit(size)
      .toArray()

    let total = await this.clDisp.count(q)

    return { tasks, total }
  }

  async findByExecutor(executor, page, size) {
    let q = {
      $or: [
        { 'executor.id': parseInt(executor.id) },
        { 'executor.id': String(executor.id) },
      ],
      'executor.type': executor.type,
    }
    let tasks = await this.clDisp
      .find(q, { projection: { _id: 0, executor: 0 } })
      .skip((page - 1) * size)
      .limit(size)
      .toArray()

    let total = await this.clDisp.count(q)

    return { tasks, total }
  }

  async byToken(token) {
    return await this.clDisp.findOne(
      { token },
      {
        projection: {
          _id: 0,
        },
      }
    )
  }
}

/**
 * 管理API调度任务
 */
class Main extends Ctrl {
  constructor(...args) {
    super(...args)
    // 数据库操作
    this._mDisp = new ModelDispatch(this.mongoClient)
  }
  /**
   * @swagger
   *
   *  /pl/fe/dispatch/create:
   *    post:
   *      description: 创建调度任务
   *      requestBody:
   *        content:
   *          application/json:
   *            schema:
   *              type: object
   *              properties:
   *                title:
   *                  type: string
   *                apiUrl:
   *                  type: string
   *                  description: 要执行的api的url。地址必须以http或https开头，主机地址必须可访问，否则无法创建任务。
   *                provider:
   *                  type: object
   *                expire:
   *                  type: integer
   *                  description: 有效期，单位秒，默认值600
   *            examples:
   *              basic:
   *                value: {"title":"申请执行操作", "apiUrl":"apiUrl", "provider":{"id":123, "type":"link"}, "expire":600}
   *      responses:
   *        '200':
   *          description: 返回结果
   *          content:
   *            application/json:
   *              schema:
   *                type: object
   *                properties:
   *                  code:
   *                    type: integer
   *                  msg:
   *                    type: string
   *                  result:
   *                    type: string
   *                    description: 代表调度任务的字符串
   */
  async create() {
    let { title, apiUrl, provider, expire } = this.request.body

    /* 检查url是否为合法url */
    let oApiUrl = Url(apiUrl)
    let { protocol, hostname, port } = oApiUrl
    if (!protocol) return new ResultFault('地址中没有指定有效协议')
    if (!hostname) return new ResultFault('地址中没有指定有效主机地址')
    if (port && !parseInt(port))
      return new ResultFault('地址中没有指定有效端口')

    /*解决在开发环境下，浏览器中的服务地址转换为容器内地址*/
    if (hostname === 'localhost' || hostname === '127.0.0.1') {
      /*检查是否在docker容器中*/
      let { alive } = await ping.promise.probe('host.docker.internal')
      if (alive === true) {
        oApiUrl.set('hostname', 'host.docker.internal')
      }
    } else {
      let { alive } = await ping.promise.probe(hostname)
      if (alive === false)
        return new ResultFault(`指定的地址【${hostname}】无法访问`)
    }

    /* 检查有效期是否合法 */
    expire = parseInt(expire || 600)
    if (expire < 0)
      return new ResultFault(`参数【expire】错误，应该为大于0的整数`)

    let token = await this._mDisp.create(
      title,
      oApiUrl.toString(),
      provider,
      expire
    )

    return new ResultData(token)
  }
  /**
   * @swagger
   *
   *  /pl/fe/dispatch/list:
   *    get:
   *      description: 返回所有调度任务
   *      parameters:
   *        - name: page
   *          description: 分页号，默认值1
   *          in: query
   *        - name: size
   *          description: 分页大小，默认值30
   *          in: query
   *      responses:
   *        '200':
   *          description: 调度任务列表
   *          content:
   *            application/json:
   *              schema:
   *                type: object
   *
   */
  async list() {
    let { page, size } = this.request.query
    page = parseInt(page || 1)
    if (page < 0) return new ResultFault('参数【page】错误')
    size = parseInt(size || 30)
    if (size < 0) return new ResultFault('参数【size】错误')

    let tasks = await this._mDisp.find(page, size)

    return new ResultData({ tasks })
  }
  /**
   * @swagger
   *
   *  /pl/fe/dispatch/listByProvider:
   *    get:
   *      description: 返回任务提供者执行的所有调度任务
   *      parameters:
   *        - name: id
   *          description: 提供者的id
   *          in: query
   *          required: true
   *        - name: type
   *          description: 提供者的类型
   *          in: query
   *          required: true
   *        - name: page
   *          description: 分页号，默认值1
   *          in: query
   *        - name: size
   *          description: 分页大小，默认值30
   *          in: query
   *      responses:
   *        '200':
   *          description: 调度任务列表
   *          content:
   *            application/json:
   *              schema:
   *                type: object
   *
   */
  async listByProvider() {
    let { id, type, page, size } = this.request.query
    page = parseInt(page || 1)
    if (page < 0) return new ResultFault('参数【page】错误')
    size = parseInt(size || 30)
    if (size < 0) return new ResultFault('参数【size】错误')

    let { tasks, total } = await this._mDisp.findByProvider(
      { id, type },
      page,
      size
    )

    return new ResultData({ tasks, total })
  }
  /**
   * @swagger
   *
   *  /pl/fe/dispatch/listByExecutor:
   *    get:
   *      description: 返回任务执行者执行的所有调度任务
   *      parameters:
   *        - name: id
   *          description: 执行者的id
   *          in: query
   *          required: true
   *        - name: type
   *          description: 执行者的类型
   *          in: query
   *          required: true
   *        - name: page
   *          description: 分页号，默认值1
   *          in: query
   *        - name: size
   *          description: 分页大小，默认值30
   *          in: query
   *      responses:
   *        '200':
   *          description: 调度任务列表
   *          content:
   *            application/json:
   *              schema:
   *                type: object
   *
   */
  async listByExecutor() {
    let { id, type, page, size } = this.request.query
    page = parseInt(page || 1)
    if (page < 0) return new ResultFault('参数【page】错误')
    size = parseInt(size || 30)
    if (size < 0) return new ResultFault('参数【size】错误')

    let { tasks, total } = await this._mDisp.findByExecutor(
      { id, type },
      page,
      size
    )

    return new ResultData({ tasks, total })
  }
  /**
   * @swagger
   *
   *  /pl/fe/dispatch/find:
   *    get:
   *      description: 返回指定调度任务
   *      parameters:
   *        - name: token
   *          description: 调度任务令牌
   *          in: query
   *          required: true
   *      responses:
   *        '200':
   *          description: 返回调度任务
   *          content:
   *            application/json:
   *              schema:
   *                type: object
   *
   */
  async find() {
    let { token } = this.request.query

    let task = await this._mDisp.byToken(token)

    return new ResultData(task)
  }
  /**
   * @swagger
   *  /pl/fe/dispatch/execute:
   *    post:
   *      description: 执行调度任务。
   *      parameters:
   *        - name: token
   *          description: 调度任务令牌
   *          in: query
   *          required: true
   *      requestBody:
   *        content:
   *          application/json:
   *            schema:
   *              type: object
   *      responses:
   *        '200':
   *          description: 返回结果
   *          content:
   *            application/json:
   *              schema:
   *                type: object
   */
  async execute() {
    let { token } = this.request.query

    if (!token) return new ResultFault('没有指定有效的任务参数')

    let task = await this._mDisp.byToken(token)
    if (!task) return new ResultFault(`指定的任务（${token}）不存在`)

    let { finishAt, expireAt } = task
    if (finishAt) return new ResultFault(`指定的任务已经完成，不能重复执行`)

    let current = parseInt(Date.now() / 1000)
    if (expireAt < current) {
      return new ResultFault(`任务已过期（${current - task.expireAt}秒）`)
    }

    /*需要转发的数据*/
    let headers = {}
    headers.referer = this.request.header.referer
    headers['user-agent'] = this.request.header['user-agent']
    let posted = this.request.body

    let executor
    if (this.request.header['x-dispatch-executor'])
      try {
        executor = JSON.parse(this.request.header['x-dispatch-executor'])
      } catch (e) {
        return new ResultData(
          `【X-Dispatch-Executor】请求头中包含的信息格式错误：${e.message}`
        )
      }

    let { apiUrl } = task

    /* 执行api */
    try {
      let { status, statusText } = await axios.post(apiUrl, posted, {
        responseType: 'text',
      })
      this._mDisp.finish(token, posted, executor, headers)
      return new ResultData({ status, statusText })
    } catch (err) {
      return new ResultFault(err.message)
    }
  }
}
module.exports = Main
