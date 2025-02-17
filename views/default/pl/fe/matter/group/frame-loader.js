requirejs(['/static/js/tms.bootstrap.js'], function (tms) {
  define('frame/const', {
    notifyMatter: [
      {
        value: 'tmplmsg',
        title: '模板消息',
        url: '/rest/pl/fe/matter',
      },
      {
        value: 'article',
        title: '单图文',
        url: '/rest/pl/fe/matter',
      },
      {
        value: 'channel',
        title: '频道',
        url: '/rest/pl/fe/matter',
      },
      {
        value: 'enroll',
        title: '记录活动',
        url: '/rest/pl/fe/matter',
      },
    ],
    innerlink: [
      {
        value: 'article',
        title: '单图文',
        url: '/rest/pl/fe/matter',
      },
      {
        value: 'channel',
        title: '频道',
        url: '/rest/pl/fe/matter',
      },
    ],
    alertMsg: {
      'schema.duplicated': '不允许重复添加登记项',
    },
    importSource: [
      {
        v: 'mschema',
        l: '通讯录联系人',
      },
      {
        v: 'registration',
        l: '报名',
      },
      {
        v: 'signin',
        l: '签到',
      },
    ],
    naming: {
      is_leader: {
        N: { l: '组员' },
        Y: { l: '组长' },
        S: { l: '超级用户' },
        O: {
          l: '旁观者',
          t: '可以参与活动和进入分组，但是不需要完成任务。',
        },
      },
    },
  })
  var _oRawPathes
  _oRawPathes = {
    js: {
      frame: '/views/default/pl/fe/matter/group/frame',
      mainCtrl: '/views/default/pl/fe/matter/group/main',
      noticeCtrl: '/views/default/pl/fe/matter/group/notice',
      teamCtrl: '/views/default/pl/fe/matter/group/team',
      recordCtrl: '/views/default/pl/fe/matter/group/record',
      leaveCtrl: '/views/default/pl/fe/matter/group/leave',
    },
    html: {
      main: '/views/default/pl/fe/matter/group/main',
      notice: '/views/default/pl/fe/matter/group/notice',
      team: '/views/default/pl/fe/matter/group/team',
      record: '/views/default/pl/fe/matter/group/record',
      leave: '/views/default/pl/fe/matter/group/leave',
      compRecords: '/views/default/pl/fe/matter/group/component/records',
      compRecordPicker:
        '/views/default/pl/fe/matter/group/component/recordPicker',
      compLeaveEditor:
        '/views/default/pl/fe/matter/group/component/leaveEditor',
    },
  }
  tms.bootstrap(_oRawPathes)
})
