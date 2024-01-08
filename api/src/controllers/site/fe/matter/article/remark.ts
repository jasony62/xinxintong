import { Ctrl, ResultData, ResultFault } from 'tms-koa'

const MAX_ADD_REMARK_RETRIES = 3 // 添加评论最大重试次数

class ModelRemark {
  clRemark: any
  user?: any
  token?: any
  constructor(mongoClient, user?, token?) {
    this.clRemark = mongoClient.db('xxt').collection('article_remark')
    this.user = user
    this.token = token
  }

  async createTaget(target) {
    let { upsertedId } = await this.clRemark.updateOne(
      { 'target.id': target.id },
      {
        $set: {
          target,
        },
        $inc: {
          total: 0,
          lastRemarkId: 0,
        },
      },
      { upsert: true }
    )
    return upsertedId
  }

  async findTargetByTarget(target) {
    return await this.clRemark.findOne(
      { 'target.id': target.id },
      { _id: 1, lastRemarkId: 1 }
    )
  }

  async findTargetById(targetId) {
    return await this.clRemark.findOne(
      { _id: targetId },
      { _id: 1, lastRemarkId: 1 }
    )
  }

  async addRemark(content, targetInDb, retries = 0) {
    let { clRemark, token, user } = this
    let current = Date.now()
    let lastRemarkId = targetInDb ? targetInDb.lastRemarkId : 0
    let newRemarkId = lastRemarkId + 1
    let { matchedCount } = await clRemark.updateOne(
      {
        _id: targetInDb._id,
        lastRemarkId,
      },
      {
        $set: { lastRemarkId: newRemarkId },
        $inc: { total: 1 },
        $push: {
          remarks: {
            id: newRemarkId,
            content,
            state: 1,
            user,
            create: { time: current, token },
          },
        },
      }
    )
    if (matchedCount === 1) return newRemarkId

    if (retries < MAX_ADD_REMARK_RETRIES - 1) {
      targetInDb = await this.findTargetById(targetInDb._id)
      newRemarkId = await this.addRemark(content, targetInDb, retries + 1)

      return newRemarkId
    }

    return false
  }
  async closeRemark(remarkId) {
    let { token } = this
    let current = Date.now()
    let { matchedCount } = await this.clRemark.updateOne(
      { 'remarks.id': remarkId },
      {
        $set: {
          'remarks.$.state': 0,
          'remarks.$.close': { time: current, token },
        },
        $inc: {
          total: -1,
        },
      }
    )
    return matchedCount === 1
  }

  async byTarget(targetId) {
    let record = await this.clRemark.findOne(
      { 'target.id': targetId },
      { total: 1, remarks: 1 }
    )
    /* 没有评论返回空数组 */
    if (!record || record.total <= 0) return []

    let remarks = record.remarks.filter((r) => r.state === 1)

    return remarks
  }
}

/**
 * 单图文评论
 */
export default class ArticleRemark extends Ctrl {
  constructor(ctx, client, dbContext, mongoClient, pushContext, fsContext?) {
    super(ctx, client, dbContext, mongoClient, pushContext, fsContext)
  }
  /**
   * @swagger
   *
   *  /site/fe/matter/article/remark/publish:
   *    post:
   *      description: 阅读用户提交评论
   *      requestBody:
   *        content:
   *          application/json:
   *            schema:
   *              type: object
   *              properties:
   *                user:
   *                  type: object
   *                  properties:
   *                    id:
   *                      type: string
   *                    name:
   *                      type: string
   *                  required: true
   *                target:
   *                  type: object
   *                  properties:
   *                    id:
   *                      type: integer
   *                    title:
   *                      type: string
   *                  required: true
   *                content:
   *                  type: string
   *                  required: true
   *            examples:
   *              basic:
   *                value: {"user":{"id":"u0001","name":"用户0001"},target:{"id":1,"title":"单图文1"},"content":"评论1"}
   *        required: true
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
   *                  data:
   *                    type: string
   *                    description: 记录的id
   */
  async publish() {
    let { user, target, content } = this.request.body

    // 检查remark的有效性
    if (!content || typeof content !== 'string')
      return new ResultFault('输入的评论的内容无效')

    // 检查输入的被评论目标和用户信息

    // 检查token有效性
    let token = '123abc'

    // 数据库操作
    let mRemark = new ModelRemark(this.mongoClient, user, token)

    // 查找被评论的对象
    let targetInDb = await mRemark.findTargetByTarget(target)
    /* 如果是首次提交评论，先创建一个不包含评论数据的记录。 */
    if (targetInDb === null) {
      /* 创建新记录。再次检查记录是否存在，避免并发造成的重复插入。 */
      let upsertedId = await mRemark.createTaget(target)
      /* 已经存在，需要改为更新 */
      if (upsertedId) {
        /* 其他用户已经先提交了数据，重新获得数据。 */
        targetInDb = await mRemark.findTargetById(upsertedId)
      }
    }

    /* 插入新评论 */
    let newRemarkId = await mRemark.addRemark(content, targetInDb)

    if (newRemarkId) return new ResultData(newRemarkId)

    return new ResultFault('系统忙，请再次提交')
  }
  /**
   * @swagger
   *
   *  /site/fe/matter/article/remark/close:
   *    post:
   *      description: 关闭指定的评论
   *      parameters:
   *        - name: remark
   *          description: 评论的id
   *          in: query
   *          required: true
   *          schema:
   *            type: integer
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
   *                    type: boolean
   *                    description: 是否关闭成功
   */
  async close() {
    let { remark } = this.request.query
    remark = parseInt(remark)

    // 检查token有效性
    let token = '789xyz'

    // 数据库操作
    let mRemark = new ModelRemark(this.mongoClient, null, token)

    let isok = await mRemark.closeRemark(remark)

    return new ResultData(isok)
  }
  /**
   * @swagger
   *
   *  /site/fe/matter/article/remark/list:
   *    get:
   *      description: 获得指定单图文的评论
   *      parameters:
   *        - name: target
   *          description: 单图文的id
   *          in: query
   *          required: true
   *          schema:
   *            type: integer
   *      responses:
   *        '200':
   *          description: 返回评论列表
   */
  async list() {
    let { target } = this.request.query
    target = parseInt(target)

    // 数据库操作
    let mRemark = new ModelRemark(this.mongoClient)

    let remarks = await mRemark.byTarget(target)

    return new ResultData(remarks)
  }
}
