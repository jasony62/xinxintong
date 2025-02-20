define(['frame'], function (ngApp) {
  ngApp.provider.controller('ctrlMain', [
    '$scope',
    'http2',
    'mediagallery',
    'noticebox',
    'srvTag',
    'srvSite',
    'tkEntryRule',
    '$uibModal',
    function (
      $scope,
      http2,
      mediagallery,
      noticebox,
      srvTag,
      srvSite,
      tkEntryRule,
      $uibModal
    ) {
      var modifiedData = {}
      $scope.modified = false
      $scope.urlsrcs = {
        0: '外部链接',
        2: '频道',
        3: '频道（授权）',
      }
      $scope.linkparams = {
        '{{openid}}': '用户标识(openid)',
        '{{site}}': '公众号标识',
        '{{member.name}}': '通讯录用户名称',
        '{{member.mobile}}': '通讯录用户手机',
        '{{member.email}}': '通讯录用户邮箱',
      }
      var getInitData = function () {
        http2
          .get(
            '/rest/pl/fe/matter/link/get?site=' +
              $scope.siteId +
              '&id=' +
              $scope.id
          )
          .then(function (rsp) {
            editLink(rsp.data)
          })
      }
      var editLink = function (link) {
        if (link.params) {
          var p
          for (var i in link.params) {
            p = link.params[i]
            p.customValue = $scope.linkparams[p.pvalue] ? false : true
          }
        }
        if (link.matter_mg_tag !== '') {
          link.matter_mg_tag.forEach(function (cTag, index) {
            $scope.oTag.forEach(function (oTag) {
              if (oTag.id === cTag) {
                link.matter_mg_tag[index] = oTag
              }
            })
          })
        }
        srvSite.memberSchemaList(link).then(function (aMemberSchemas) {
          $scope.memberSchemas = aMemberSchemas
          $scope.mschemasById = {}
          $scope.memberSchemas.forEach(function (mschema) {
            $scope.mschemasById[mschema.id] = mschema
          })
        })
        srvSite.snsList().then(function (oSns) {
          $scope.tkEntryRule = new tkEntryRule(link, oSns, false, ['enroll'])
        })
        $scope.editing = link
        !$scope.editing.attachments && ($scope.editing.attachments = [])
        $scope.persisted = angular.copy(link)
        $('[ng-model="editing.title"]').focus()
        var r = new Resumable({
          target:
            '/rest/pl/fe/matter/link/attachment/upload?site=' +
            $scope.editing.siteid +
            '&linkid=' +
            $scope.editing.id,
          testChunks: false,
        })
        r.assignBrowse(document.getElementById('addAttachment'))
        r.on('fileAdded', function (file, event) {
          $scope.$apply(function () {
            noticebox.progress('开始上传文件')
          })
          r.upload()
        })
        r.on('progress', function (file, event) {
          $scope.$apply(function () {
            noticebox.progress(
              '正在上传文件：' + Math.floor(r.progress() * 100) + '%'
            )
            if (Math.floor(r.progress() * 100) === 100) {
              noticebox.close()
            }
          })
        })
        r.on('complete', function () {
          var f, lastModified, posted
          f = r.files.pop().file
          lastModified = f.lastModified
            ? f.lastModified
            : f.lastModifiedDate
            ? f.lastModifiedDate.getTime()
            : 0
          posted = {
            name: f.name,
            size: f.size,
            type: f.type,
            lastModified: lastModified,
            uniqueIdentifier: f.uniqueIdentifier,
          }
          http2
            .post(
              '/rest/pl/fe/matter/link/attachment/add?site=' +
                $scope.editing.siteid +
                '&id=' +
                $scope.editing.id,
              posted
            )
            .then(function (rsp) {
              $scope.editing.attachments.push(rsp.data)
            })
        })
      }
      window.onbeforeunload = function (e) {
        var message
        if ($scope.modified) {
          ;(message = '修改还没有保存，是否要离开当前页面？'),
            (e = e || window.event)
          if (e) {
            e.returnValue = message
          }
          return message
        }
      }
      $scope.remove = function () {
        let { siteId, id } = $scope
        http2
          .get(`/rest/pl/fe/matter/link/update?site=${siteId}&id=${id}`)
          .then(function () {
            location.href = `/rest/pl/fe/site/console?site=${siteId}`
          })
      }
      $scope.submit = function () {
        let { siteId, id } = $scope
        http2
          .post(
            `/rest/pl/fe/matter/link/update?site=${siteId}&id=${id}`,
            modifiedData
          )
          .then(function () {
            modifiedData = {}
            $scope.modified = false
          })
      }
      $scope.update = function (names) {
        angular.isString(names) && (names = [names])
        names.forEach(function (n) {
          modifiedData[n] = $scope.editing[n]
          if (n === 'urlsrc' && $scope.editing.urlsrc != 0) {
            $scope.editing.open_directly = 'N'
            modifiedData.open_directly = 'N'
          } else if (n === 'method' && $scope.editing.method === 'POST') {
            $scope.editing.open_directly = 'N'
            modifiedData.open_directly = 'N'
          }
          $scope.modified = true
        })
      }
      $scope.setPic = function () {
        var options = {
          callback: function (url) {
            $scope.editing.pic = url + '?_=' + new Date() * 1
            $scope.update('pic')
          },
        }
        mediagallery.open($scope.siteId, options)
      }
      $scope.removePic = function () {
        $scope.editing.pic = ''
        $scope.update('pic')
      }
      $scope.$on('xxt.tms-datepicker.change', function (event, data) {
        $scope.editing[data.state] = data.value
        $scope.update(data.state)
      })
      $scope.addParam = function () {
        http2
          .get(
            '/rest/pl/fe/matter/link/paramAdd?site=' +
              $scope.siteId +
              '&linkid=' +
              $scope.editing.id
          )
          .then(function (rsp) {
            var oNewParam = {
              id: rsp.data,
              pname: 'newparam',
              pvalue: '',
            }
            if ($scope.editing.urlsrc === '3' && $scope.editing.url === '9')
              oNewParam.pname = 'channelid'
            $scope.editing.params.push(oNewParam)
          })
      }
      $scope.updateParam = function (updated, name) {
        // 参数中有额外定义，需清除
        var p = {
          pname: updated.pname,
          pvalue: encodeURIComponent(updated.pvalue),
        }
        http2.post(
          '/rest/pl/fe/matter/link/paramUpd?site=' +
            $scope.siteId +
            '&id=' +
            updated.id,
          p
        )
      }
      $scope.removeParam = function (removed) {
        http2
          .get(
            `/rest/pl/fe/matter/link/removeParam?site=${$scope.siteId}&id=${removed.id}`
          )
          .then(() => {
            let params = $scope.editing.params
            let i = params.indexOf(removed)
            params.splice(i, 1)
          })
      }
      $scope.changePValueMode = function (p) {
        p.pvalue = ''
      }
      $scope.tagMatter = function (subType) {
        var oTags
        oTags = $scope.oTag
        srvTag._tagMatter($scope.editing, oTags, subType)
      }
      $scope.assignMission = function () {
        var _this = this
        srvSite
          .openGallery({
            matterTypes: [
              {
                value: 'mission',
                title: '项目',
                url: '/rest/pl/fe/matter',
              },
            ],
            singleMatter: true,
          })
          .then(function (missions) {
            var matter
            if (missions.matters.length === 1) {
              matter = {
                id: $scope.id,
                type: 'link',
              }
              http2
                .post(
                  '/rest/pl/fe/matter/mission/matter/add?site=' +
                    $scope.siteId +
                    '&id=' +
                    missions.matters[0].id,
                  matter
                )
                .then(function (rsp) {
                  var mission = rsp.data

                  $scope.editing.mission = mission
                  $scope.editing.mission_id = mission.id
                  modifiedData['mission_id'] = mission.id
                  if (!$scope.editing.pic || $scope.editing.pic.length === 0) {
                    $scope.editing.pic = mission.pic
                    modifiedData['pic'] = mission.pic
                  }
                  if (
                    !$scope.editing.summary ||
                    $scope.editing.summary.length === 0
                  ) {
                    $scope.editing.summary = mission.summary
                    modifiedData['summary'] = mission.summary
                  }
                  _this.submit()
                })
            }
          })
      }
      $scope.alterChannel = function () {
        $uibModal
          .open({
            templateUrl: 'alterChannel.html',
            controller: [
              '$scope',
              '$uibModalInstance',
              function ($scope2, $mi) {
                $scope2.ok = function () {
                  $mi.close('reload')
                }
                $scope2.cancel = function () {
                  $mi.dismiss()
                }
                let { protocol, hostname, port } = location
                let baseUrl = `${protocol}//${hostname}`
                if (port) baseUrl += `:${port}`
                let linkId = $scope.editing.id
                http2
                  .post(
                    '/api/pl/fe/dispatch/create',
                    {
                      title: '请允许链接使用频道',
                      apiUrl: `${baseUrl}/rest/pl/fe/matter/link/wrap/channel?id=${linkId}`,
                      provider: { id: linkId, type: 'link' },
                    },
                    { parseResponse: false }
                  )
                  .then((rsp) => {
                    if (rsp.code === 0 && rsp.result) {
                      $scope2.dispatchUrl = `${baseUrl}/api/pl/fe/dispatch/execute?token=${rsp.result}`
                    } else {
                      noticebox.error(rsp.msg)
                    }
                  })
              }.bind(this),
            ],
            backdrop: 'static',
          })
          .result.then((state) => {
            if (state === 'reload') {
              $scope.getLink().then((rsp) => {
                let { url } = rsp.data
                if (url) {
                  $scope.editing.url = url
                  getWrapChannel()
                }
              })
            }
          })
      }
      $scope.quitMission = function () {
        var that = this
        ;(matter = {
          id: $scope.editing.id,
          type: 'link',
          title: $scope.editing.title,
        }),
          http2
            .post(
              '/rest/pl/fe/matter/mission/matter/remove?site=' +
                $scope.siteId +
                '&id=' +
                $scope.editing.mission_id,
              matter
            )
            .then(function (rsp) {
              delete $scope.editing.mission
              $scope.editing.mission_id = 0
              modifiedData['mission_id'] = 0
              that.submit()
            })
      }
      $scope.assignNavApp = function () {
        var oOptions = {
          matterTypes: [
            {
              value: 'enroll',
              title: '记录活动',
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
              value: 'link',
              title: '链接',
              url: '/rest/pl/fe/matter',
            },
            {
              value: 'topic',
              title: '公共专题',
              url: '/rest/pl/fe/matter',
            },
          ],
          singleMatter: true,
        }
        srvSite.openGallery(oOptions).then(function (result) {
          if (result.matters && result.matters.length === 1) {
            !$scope.editing.config.nav && ($scope.editing.config.nav = {})
            !$scope.editing.config.nav.app &&
              ($scope.editing.config.nav.app = [])
            if (result.matters[0].type === 'topic') {
              $scope.editing.config.nav.app.push({
                id: result.matters[0].id,
                title: result.matters[0].title,
                siteid: result.matters[0].siteid,
                type: result.matters[0].type,
                aid: result.matters[0].aid,
              })
            } else {
              $scope.editing.config.nav.app.push({
                id: result.matters[0].id,
                title: result.matters[0].title,
                siteid: result.matters[0].siteid,
                type: result.matters[0].type,
              })
            }
            $scope.update('config')
          }
        })
      }
      $scope.removeNavApp = function (index) {
        $scope.editing.config.nav.app.splice(index, 1)
        if ($scope.editing.config.nav.app.length === 0) {
          delete $scope.editing.config.nav.app
        }
        $scope.update('config')
      }
      $scope.delAttachment = function (index, att) {
        http2
          .get(
            '/rest/pl/fe/matter/link/attachment/del?site=' +
              $scope.siteid +
              '&id=' +
              att.id
          )
          .then(function (rsp) {
            $scope.editing.attachments.splice(index, 1)
            $scope.modified = false
          })
      }
      $scope.downloadUrl = function (att) {
        return (
          '/rest/site/fe/matter/link/attachmentGet?site=' +
          $scope.siteid +
          '&linkid=' +
          $scope.editing.id +
          '&attachmentid=' +
          att.id
        )
      }
      function getWrapChannel() {
        http2
          .get(
            `/rest/pl/fe/matter/channel/get?site=${$scope.siteId}&id=${$scope.editing.url}`
          )
          .then(function (rsp) {
            $scope.channel = rsp.data
          })
      }
      $scope.$watch('editing.urlsrc', function (nv) {
        switch (nv) {
          case '2': // 选择的团队内频道
            if ($scope.channels === undefined) {
              http2
                .get(
                  '/rest/pl/fe/matter/channel/list?site=' +
                    $scope.siteId +
                    '&cascade=N'
                )
                .then(function (rsp) {
                  $scope.channels = rsp.data.docs
                })
            }
            break
          case '3': // 通过授权方式关联的频道，url对应的是channel的id
            if (parseInt($scope.editing.url) > 0) {
              getWrapChannel()
            }
            break
        }
      })
      http2
        .post('/rest/script/time', {
          html: {
            entryRule: '/views/default/pl/fe/_module/entryRule',
          },
        })
        .then(function (rsp) {
          $scope.frameTemplates = {
            html: {
              entryRule:
                '/views/default/pl/fe/_module/entryRule.html?_=' +
                rsp.data.html.entryRule.time,
            },
          }
        })
      getInitData()
    },
  ])
})
