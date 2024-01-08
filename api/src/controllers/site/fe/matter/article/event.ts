import { Ctrl, ResultData } from 'tms-koa'
/**
 * 处理单图文用户事件
 */
export default class ArticleEvent extends Ctrl {
  /**
   * @swagger
   *
   *  /site/fe/matter/article/event/logAccess:
   *    post:
   *      description: 记录用户行为数据
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
   *                article:
   *                  type: object
   *                  properties:
   *                    id:
   *                      type: string
   *                    title:
   *                      type: string
   *                  required: true
   */
  async logAccess() {
    let { user, article } = this.request.body

    // 检查bucket是否存在
    const client = this.mongoClient
    const clUser = client.db('xxt').collection('article_user')

    let current = Date.now()
    let rst = await clUser.updateOne(
      { 'article.id': article.id, 'user.id': user.id },
      {
        $set: { article, user, 'read.latest': current },
        $inc: { 'read.num': 1 },
      },
      { upsert: true }
    )

    return new ResultData(rst)
  }
}
