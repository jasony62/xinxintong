'use strict'
define(['frame'], function (ngApp) {
  ngApp.provider.service('srvEnrollee', [
    '$q',
    '$uibModal',
    'http2',
    'tmsRowPicker',
    'tkEnrollRound',
    'srvEnrollRecord',
    function ($q, $uibModal, http2, tmsRowPicker, tkEnlRnd, srvEnlRec) {
      var _oCriteria, _oRows, _oPage
      this.init = function (oApp, rids) {
        this.app = oApp
        this.rids = rids
        this.page = _oPage = {
          size: 20,
        }
        this.criteria = _oCriteria = {
          orderby: 'enroll_num',
          //onlyEnrolled: 'Y',
          filter: {},
        }
        this.rows = _oRows = new tmsRowPicker()
      }
      this.list = function (pageAt) {
        var url = '/rest/pl/fe/matter/enroll/user/enrollee?app=' + this.app.id
        _oRows.reset()
        pageAt && (_oPage.at = pageAt)
        http2
          .post(url, _oCriteria, {
            page: _oPage,
          })
          .then(
            function (rsp) {
              var users = rsp.data.users
              var oRounds = rsp.data.rounds || {
                ALL: {
                  title: '全部',
                },
              }
              srvEnlRec.init(this.app, _oPage, _oCriteria, users)
              users.forEach(function (oUser) {
                oUser.round = oRounds[oUser.rid] || {}
              })
              this.enrollees = users
            }.bind(this)
          )
      }
      this.chooseOrderby = function (orderby) {
        _oCriteria.orderby = orderby
        this.list(1)
      }
      this.advFilter = function () {
        http2
          .post('/rest/script/time', {
            html: {
              enrollee:
                '/views/default/pl/fe/matter/enroll/component/enrolleeFilter',
            },
          })
          .then(
            function (rsp) {
              $uibModal
                .open({
                  templateUrl:
                    '/views/default/pl/fe/matter/enroll/component/enrolleeFilter.html?_=' +
                    rsp.data.html.enrollee.time,
                  controller: [
                    '$scope',
                    '$uibModalInstance',
                    function ($scope2, $mi) {
                      $scope2.app = this.app
                      $scope2.criteria = _oCriteria
                      $scope2.page = {
                        size: 7,
                      }
                      $scope2.doSearchRound = function () {
                        http2
                          .get(
                            '/rest/pl/fe/matter/enroll/round/list?app=' +
                              this.app.id,
                            {
                              page: $scope2.page,
                            }
                          )
                          .then(function (rsp) {
                            $scope2.rounds = rsp.data.rounds
                          })
                      }
                      $scope2.ok = function () {
                        $mi.close($scope2.criteria)
                      }
                      $scope2.cancel = function () {
                        $mi.dismiss()
                      }
                      $scope2.doSearchRound()
                    }.bind(this),
                  ],
                  windowClass: 'auto-height',
                  backdrop: 'static',
                })
                .result.then(
                  function () {
                    this.list(1)
                  }.bind(this)
                )
            }.bind(this)
          )
      }
      this.notify = function (isBatch) {
        srvEnlRec.notify(isBatch ? _oRows : null)
      }
      this.repairEnrollee = function () {
        var url = '/rest/pl/fe/matter/enroll/repair/user'
        url += '?app=' + this.app.id
        http2.get(url).then(
          function () {
            this.list(1)
          }.bind(this)
        )
      }
      this.repairCoin = function () {
        var defer = $q.defer()
        tkEnlRnd
          .pick(this.app, {
            single: false,
          })
          .then(
            function (oResult) {
              function fnResetCoinByRound(i) {
                if (i < rids.length) {
                  var url = '/rest/pl/fe/matter/enroll/repair/userCoin'
                  url += '?app=' + this.app.id
                  url += '&rid=' + rids[i]
                  http2.get(url).then(
                    function () {
                      fnResetCoinByRound.call(this, ++i)
                    }.bind(this)
                  )
                } else {
                  defer.resolve(i)
                }
              }
              var rids = oResult.rid
              rids.length && fnResetCoinByRound.call(this, 0)
            }.bind(this)
          )
        return defer.promise
      }
      this.repairGroup = function () {
        var url = '/rest/pl/fe/matter/enroll/repair/userGroup'
        url += '?app=' + this.app.id
        http2.get(url).then(
          function () {
            this.list(1)
          }.bind(this)
        )
      }
      this.export = function () {
        var url = '/rest/pl/fe/matter/enroll/export/enrollee'
        url += '?app=' + this.app.id
        if (_oCriteria.rids) url += '&rids=' + _oCriteria.rids
        window.open(url)
      }
      this.taskList = function () {
        http2
          .get(
            'rest/pl/fe/matter/enroll/task/list?site=' +
              this.app.siteid +
              '&app=' +
              this.app.id
          )
          .then(
            function (rsp) {
              this.tasks = rsp.data
            }.bind(this)
          )
      }
    },
  ])
  ngApp.provider.service('srvGroup', [
    'http2',
    'tkEnrollRound',
    function (http2, tkEnlRnd) {
      this.init = function (oApp, rids) {
        this.app = oApp
        this.rids = rids
      }
      this.list = function (rid) {
        var that = this
        var url
        url = '/rest/pl/fe/matter/enroll/user/group?app=' + this.app.id
        this.rids !== undefined && (url += '&rids=' + this.rids)
        http2.post(url, {}).then(function (rsp) {
          if (rsp.data.groups && rsp.data.groups.length) {
            var groups = rsp.data.groups
            var oRounds = rsp.data.rounds || {
              ALL: {
                title: '全部轮次',
              },
            }
            groups.forEach(function (oGroup) {
              if (oGroup.data && oGroup.data.rid)
                oGroup.round = oRounds[oGroup.data.rid]
            })
            that.groups = groups
          }
        })
      }
      this.chooseRound = function () {
        var that = this
        tkEnlRnd
          .pick(this.app, {
            single: false,
          })
          .then(function (oResult) {
            that.rids = oResult.rid
            that.list()
          })
      }
    },
  ])
  ngApp.provider.service('srvUndone', [
    'http2',
    'tmsSchema',
    'tkEnrollRound',
    function (http2, tmsSchema, tkEnlRnd) {
      this.init = function (oApp, rids) {
        this.app = oApp
        this.rids = rids
      }
      this.list = function () {
        var that = this
        var url = '/rest/pl/fe/matter/enroll/user/undone?app=' + this.app.id
        this.rids !== undefined && (url += '&rids=' + this.rids)
        http2.post(url, {}).then(function (rsp) {
          var schemasById
          var absentUsers = []
          var oRunds = rsp.data.rounds
          if (oRunds && rsp.data.users) {
            var rids, users
            rids = Object.keys(oRunds)
            if (rids && rids.length) {
              rids.forEach(function (rid) {
                if ((users = rsp.data.users[rid])) {
                  users.forEach(function (oUser) {
                    oUser.round = oRunds[rid]
                    absentUsers.push(oUser)
                  })
                }
              })
            }
          }
          that.absentUsers = absentUsers
          that.absentRounds = oRunds
          if (rsp.data.app) {
            that.absentApp = rsp.data.app
            if (that.absentApp.dataSchemas) {
              schemasById = {}
              that.absentApp.dataSchemas.forEach(function (oSchema) {
                schemasById[oSchema.id] = oSchema
              })
              that.absentUsers.forEach(function (oUser) {
                tmsSchema.forTable(oUser, schemasById)
              })
            }
          }
        })
      }
      this.chooseRound = function () {
        var that = this
        tkEnlRnd
          .pick(this.app, {
            single: false,
          })
          .then(function (oResult) {
            that.rids = oResult.rid
            that.list()
          })
      }
      this.export = function () {
        var url = '/rest/pl/fe/matter/enroll/export/undone'
        url += '?app=' + this.app.id
        if (this.rids) url += '&rids=' + this.rids
        window.open(url)
      }
    },
  ])
  ngApp.provider.controller('ctrlEnrollee', [
    '$scope',
    'noticebox',
    'facListFilter',
    'srvEnrollee',
    'srvGroup',
    'srvUndone',
    function (
      $scope,
      noticebox,
      facListFilter,
      srvEnrollee,
      srvGroup,
      srvUndone
    ) {
      $scope.category = 'enrollee'
      $scope.categories = {
        enrollee: '用户',
        absent: '缺席',
      }
      $scope.tmsTableWrapReady = 'N'
      $scope.tmsTasks = {
        enroll_num: '默认任务',
        baseline: '目标',
        question: '提问',
        answer: '回答',
        vote: '投票',
        score: '打分',
      }
      $scope.$watch('srvEnrollee.rows.allSelected', function (nv) {
        if ($scope.enrollees) {
          srvEnrollee.rows.setAllSelected(nv, $scope.enrollees.length)
        }
      })
      $scope.shiftCategory = function (category) {
        $scope.category = category
      }
      $scope.repairCoin = function () {
        srvEnrollee.repairCoin().then(function (count) {
          noticebox.success('完成指定轮次数据的更新')
          srvEnrollee.list(1)
          let oRule = $scope.app.entryRule
          if (oRule.group && oRule.group.id) {
            srvGroup.list(1)
          }
        })
      }
      $scope.$watch('app.entryRule', function (oRule) {
        if (!oRule) return
        $scope.tmsTableWrapReady = 'Y'
        srvEnrollee.init($scope.app)
        srvEnrollee.list(1)
        srvEnrollee.taskList()
        $scope.srvEnrollee = srvEnrollee
        $scope.filter = facListFilter.init(function () {
          srvEnrollee.list(1)
        }, srvEnrollee.criteria.filter)
        // 用户组
        if (oRule.group && oRule.group.id) {
          $scope.categories.group = '用户组'
          srvGroup.init($scope.app)
          srvGroup.list()
          $scope.srvGroup = srvGroup
        }
        // 未完成
        srvUndone.init($scope.app)
        srvUndone.list()
        $scope.srvUndone = srvUndone
      })
    },
  ])
})
