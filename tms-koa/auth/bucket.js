/**
 * bucket验证函数
 */
module.exports = function (ctx, client) {
  const { bucket } = ctx.request.query

  if (!client) {
    const fnCreateClient = require('./client')
    let [success, reqClient] = fnCreateClient(ctx)
    if (success !== true) throw Error(`获得用户身份信息失败：${reqClient}`)
    client = reqClient
  }

  const xxtUser = client.data
  console.log('xxt', bucket, xxtUser)

  // 检查用户是否是平台或站点管理员
  // if ($this->get['site'] === 'platform') {
  //   $modelAcnt = TMS_MODEL::model('account');
  //   if (!$modelAcnt->canManagePlatform($who->unionid)) {
  //       unset($this->session['siteid']);
  //       $this->errorMsg("无权访问");
  //   }
  // } else {
  //   $modelAdmin = TMS_MODEL::model('site\admin');
  //   $rst = $modelAdmin->byUid($this->get['site'], $who->unionid);
  //   if ($rst === false) {
  //       unset($this->session['siteid']);
  //       $this->errorMsg("无权访问");
  //   }
  // }

  if (bucket) return [true, bucket]

  return [false]
}
