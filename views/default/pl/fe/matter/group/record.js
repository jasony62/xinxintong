define(['frame'], function (ngApp) {
  ngApp.provider.controller('ctrlRecord', [
    '$scope',
    '$timeout',
    'srvGroupApp',
    'srvGroupTeam',
    'srvGroupRec',
    'srvMemberPicker',
    'facListFilter',
    function (
      $scope,
      $timeout,
      srvGrpApp,
      srvGrpTeam,
      srvGrpRec,
      srvMemberPicker,
      facListFilter
    ) {
      $scope.syncByApp = function (data) {
        srvGrpApp.syncByApp().then(function (count) {
          $scope.list('team')
        })
      }
      $scope.chooseAppUser = function () {
        var oSourceApp
        if ((oSourceApp = $scope.app.sourceApp)) {
          switch (oSourceApp.type) {
            case 'enroll':
              break
            case 'signin':
              break
            case 'mschema':
              var matter = {
                id: $scope.app.id,
                siteid: $scope.app.siteid,
                type: 'group',
              }
              srvMemberPicker.open(matter, oSourceApp).then(function () {
                $scope.list('team')
              })
              break
          }
        }
      }
      $scope.export = function () {
        srvGrpApp.export()
      }
      $scope.execute = function () {
        srvGrpRec.execute()
      }
      $scope.list = function (arg) {
        srvGrpRec
          .list(_oCriteria[arg].team_id === 'all' ? null : _oCriteria[arg], arg)
          .then(() => {
            $timeout(() => ($scope.tableReady = 'Y'), 500)
          })
      }
      $scope.openFilter = function () {
        srvGrpRec.filter()
      }
      $scope.editRec = function (oRecord) {
        srvGrpRec.edit(oRecord).then(function (oResult) {
          srvGrpRec.update(oRecord, oResult.record)
          srvGrpApp.dealData(oResult.record)
          srvGrpApp.update('tags')
        })
      }
      $scope.addRec = function () {
        srvGrpRec
          .edit({
            tags: '',
            role_teams: [],
          })
          .then(function (oResult) {
            srvGrpRec.add(oResult.record)
          })
      }
      $scope.removeRec = function (oRecord) {
        if (window.confirm('确认删除？')) {
          srvGrpRec.remove(oRecord)
        }
      }
      $scope.empty = function () {
        srvGrpRec.empty()
      }
      $scope.selectRec = function (oRecord) {
        var records = $scope.rows.records,
          i = records.indexOf(oRecord)
        i === -1 ? records.push(oRecord) : records.splice(i, 1)
      }
      // 选中或取消选中所有行
      $scope.selectAllRows = function (checked) {
        var index = 0
        if (checked === 'Y') {
          $scope.rows.records = []
          while (index < $scope.records.length) {
            $scope.rows.records.push($scope.records[index])
            $scope.rows.selected[index++] = true
          }
        } else if (checked === 'N') {
          $scope.rows.reset()
        }
      }
      $scope.quitTeam = function (records, type) {
        if (records.length)
          srvGrpRec.quitTeam(records, type).then(() => $scope.rows.reset())
      }
      $scope.joinGroup = function (oTeam, records) {
        if (records.length && oTeam) {
          srvGrpRec.joinGroup(oTeam, records).then(function () {
            $scope.rows.reset()
          })
        }
      }
      $scope.setIsLeader = function (isLeader, records) {
        if (records.length && isLeader !== undefined) {
          srvGrpRec.setIsLeader(isLeader, records).then(function () {
            $scope.rows.reset()
          })
        }
      }
      $scope.notify = function (isBatch) {
        srvGrpRec.notify(isBatch ? $scope.rows : undefined)
      }
      var _records, _oCriteria
      $scope.records = _records = []
      // 表格定义是否准备完毕
      $scope.tableReady = 'N'
      // 当前选中的行
      $scope.page = {} // 分页条件
      $scope.rows = {
        reset: function () {
          this.allSelected = 'N'
          this.selected = {}
          this.records = []
        },
      }
      $scope.rows.reset()
      $scope.criteria = _oCriteria = {
        team: {
          team_id: 'all',
        },
        roleTeam: {
          team_id: 'all',
        },
      }
      $scope.filter = facListFilter.init(function (
        oFilterData,
        filterByProp,
        filterByKeyword
      ) {
        if (/team|roleTeam/.test(filterByProp)) {
          $scope.list(filterByProp)
        } else if ('nickname' === filterByProp) {
          srvGrpRec.list(null, 'round', {
            by: filterByProp,
            kw: filterByKeyword,
          })
        }
      },
      _oCriteria)

      srvGrpApp.get().then(function (oApp) {
        if (oApp.assignedNickname) {
          $scope.bRequireNickname =
            oApp.assignedNickname.valid !== 'Y' || !oApp.assignedNickname.schema
        }
        srvGrpRec.init(_records).then(function () {
          $scope.list('team')
        })
      })
      srvGrpTeam.list().then(function (teams) {
        $scope.teams = []
        $scope.roleTeams = []
        teams.forEach(function (oTeam) {
          switch (oTeam.team_type) {
            case 'T':
              $scope.teams.push(oTeam)
              break
            case 'R':
              $scope.roleTeams.push(oTeam)
              break
          }
        })
      })
    },
  ])
})
