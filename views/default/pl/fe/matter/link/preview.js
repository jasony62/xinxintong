define(['frame'], function(ngApp) {
  ngApp.provider.controller('ctrlPreview',['$scope', 'http2', 'noticebox', function($scope, http2, noticebox){
        $scope.applyToHome = function() {
            var url = '/rest/pl/fe/matter/home/apply?site=' + $scope.editing.siteid + '&type=link&id=' + $scope.editing.id;
            http2.get(url, function(rsp) {
                noticebox.success('完成申请！');
            });
        };
  }]);
});