(function() {
    console.log('pppppp');
    xxtApp.register.controller('approveCtrl', ['$scope', 'http2', function($scope, http2) {
        $scope.$parent.subView = 'approve';
        var inlist = function(id) {
            for (var i in $scope.messages) {
                if ($scope.messages[i].id == id)
                    return true;
            }
            return false;
        };
        $scope.messages = [];
        $scope.$parent.worker = new Worker('/views/default/mp/app/wall/wallMessages.js?_=2');
        $scope.$parent.worker.onmessage = function(event) {
            for (var i in event.data) {
                for (var i in event.data) {
                    if (!inlist(event.data[i].id))
                        $scope.messages.splice(0, 0, event.data[i]);
                }
            }
            $scope.$apply();
        };
        $scope.$parent.worker.postMessage({
            wid: $scope.wid,
            last: 0
        });
        $scope.approve = function(msg) {
            http2.get('/rest/mp/app/wall/message/approve?wall=' + $scope.wid + '&id=' + msg.id, function(rsp) {
                var i = $scope.messages.indexOf(msg);
                $scope.messages.splice(i, 1);
            });
        };
        $scope.reject = function(msg) {
            http2.get('/rest/mp/app/wall/message/reject?wall=' + $scope.wid + '&id=' + msg.id, function(rsp) {
                var i = $scope.messages.indexOf(msg);
                $scope.messages.splice(i, 1);
            });
        };
    }]);
})();