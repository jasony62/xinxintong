import { Ctrl, ResultData, ResultFault } from 'tms-koa'
/**
 * 处理单图文用户事件
 */
export default class ArticleEvent extends Ctrl {
  /**
   * @swagger
   *
   *  /pl/fe/matter/article/event/users:
   *    parameters:
   *      - name: id
   *        description: 单图文的id
   *        in: query
   *        required: true
   *        schema:
   *          type: string
   *    post:
   *      description: 返回指定用户的行为数据
   *      requestBody:
   *        content:
   *          application/json:
   *            schema:
   *              type: array
   *        required: true
   */
  async users() {
    let { article } = this.request.query
    let { uids } = this.request.body

    if (isNaN(parseInt(article)))
      return new ResultFault('没有指定有效的单图文id参数[article]')

    if (!Array.isArray(uids) || uids.length === 0)
      return new ResultFault('没有指定有效的用户id数组')

    const client = this.mongoClient
    const clUser = client.db('xxt').collection('article_user')
    let q = { 'article.id': article, 'user.id': { $in: uids } }
    let o = { projection: { _id: 0, article: 0 } }
    let users = await clUser.find(q, o).toArray()

    /*转换为用户id到行为数据的映射*/
    let data = users.reduce((prev, curr) => {
      let { read } = curr
      prev[curr.user.id] = { read }
      return prev
    }, {})

    return new ResultData(data)
  }
}
