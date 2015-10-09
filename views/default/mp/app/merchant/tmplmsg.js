(function() {
    xxtApp.register.controller('tmplmsgCtrl', ['$scope', 'http2', '$modal', function($scope, http2, $modal) {
        $scope.$parent.subView = 'tmplmsg';
        $scope.orderEvts = [{
            id: 'submit_order',
            label: '用户提交订单'
        }, {
            id: 'pay_order',
            label: '用户完成支付'
        }, {
            id: 'feedback_order',
            label: '客服反馈订单'
        }];
        http2.get('/rest/mp/app/merchant/catelog/get?shopId=' + $scope.shopId, function(rsp) {
            $scope.catelogs = rsp.data;
            if (rsp.data.length) {
                $scope.selectedCatelog = rsp.data[0];
            }
        });
        http2.get('/rest/mp/matter/tmplmsg/list', function(rsp) {
            $scope.tmplmsgs = rsp.data;
        });
        $scope.selectCatelog = function() {};
        $scope.choose = function(orderEvt) {
            $scope.selectedOrderEvt = orderEvt;
        };
        $scope.selectTmplmsg = function() {
            $modal.open({
                templateUrl: 'tmplmsgSelector.html',
                backdrop: 'static',
                resolve: {
                    tmplmsgs: function() {
                        return $scope.tmplmsgs;
                    }
                },
                controller: ['$modalInstance', '$scope', 'tmplmsgs', function($mi, $scope2, tmplmsgs) {
                    $scope2.tmplmsgs = tmplmsgs;
                    $scope2.data = {};
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                $scope.selectedOrderEvt.tmplmsg = data.selected;
            });
        };
        $scope.selectProperty = function(tmplmsgProp) {
            $modal.open({
                templateUrl: 'propertySelector.html',
                backdrop: 'static',
                resolve: {
                    catelog: function() {
                        return $scope.selectedCatelog;
                    }
                },
                controller: ['$modalInstance', '$scope', 'catelog', function($mi, $scope2, catelog) {
                    $scope2.catelog = catelog;
                    $scope2.data = {};
                    $scope2.close = function() {
                        $mi.dismiss();
                    };
                    $scope2.ok = function() {
                        $mi.close($scope2.data);
                    };
                }]
            }).result.then(function(data) {
                //$scope.selectedOrderEvt.mapping[tmplmsgProp.id] = data.selected;
            });
        };
        $scope.save = function() {

        };
    }]);
})();