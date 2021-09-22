define(['frame'], function (ngApp) {
  'use strict'
  ngApp.provider.controller('ctrlUser', [
    '$scope',
    'srvUser',
    function ($scope, srvUser) {
      $scope.userapp = {}
      /*获取用户行为数据*/
      $scope.list = function () {
        srvUser.watch($scope.editing).then(({ userapp, readers }) => {
          /* 如果用户来源于分组活动，补充用户分组信息 */
          if (userapp && userapp.type === 'group') {
            if (Array.isArray(userapp.teams)) {
              let teamsById = userapp.teams.reduce((prev, curr) => {
                prev[curr.team_id] = curr
                return prev
              }, {})
              readers.forEach((reader) => {
                if (teamsById[reader.team_id])
                  reader.team = teamsById[reader.team_id]
              })
            }
          }
          $scope.readers = readers
        })
      }
      /*获得单图文数据*/
      $scope.$watch('editing', function (nv) {
        if (!nv) return
        let { scope, group, member } = nv.entryRule
        if ((scope && scope.group === 'Y') || scope.member === 'Y') {
          /*用户来源活动类型*/
          if (scope.group === 'Y' && group) $scope.userapp.type = 'group'
          else if (scope.member === 'Y' && member)
            $scope.userapp.type = 'mschema'
          $scope.list()
        }
      })
    },
  ])
})
