var app = angular.module('app', []);
app.controller('merchantCtrl', ['$scope', '$http', function($scope, $http) {
	var search, mpid, productid, orderid;
	search = location.search;
	mpid = search.match(/[\?&]mpid=(.+?)(&|$)/)[1];
	productid = search.match(/[\?&]product=(.+?)(&|$)/) ? search.match(/[\?&]product=(.+?)(&|$)/)[1] : '';
	orderid = search.match(/[\?&]order=(.+?)(&|$)/) ? search.match(/[\?&]order=(.+?)(&|$)/)[1] : '';
	$scope.orderInfo = {
		product_count: 1,
	};
	$scope.buy = function() {
		if (!$scope.orderInfo.receiver_name) {
			alert('请填写联系人姓名');
			return;
		}
		if (!$scope.orderInfo.receiver_mobile) {
			alert('请填写联系人电话');
			return;
		}
		$http.post('/rest/app/merchant/order/buy?mpid=' + mpid + '&sku=' + $scope.sku.id, $scope.orderInfo).success(function(rsp) {
			if (rsp.err_code !== 0) {
				alert(rsp.err_msg);
				return;
			}
			location.href = '/rest/app/merchant/pay?mpid=' + mpid + '&order=' + rsp.data;
		});
	};
	var productGet = function(id) {
		$http.get('/rest/app/merchant/product/get?mpid=' + mpid + '&id=' + id).success(function(rsp) {
			if (rsp.err_code !== 0) {
				alert(rsp.err_msg);
				return;
			}
			var propValue;
			$scope.sku = rsp.data.skus[0];
			$scope.product = rsp.data;
			$scope.catelog = rsp.data.catelog;
			$scope.propValues = rsp.data.propValue2;
		});
	};
	if (productid) {
		productGet(productid);
	} else if (orderid) {
		$http.get('/rest/app/merchant/order/get?mpid=' + mpid + '&order=' + orderid).success(function(rsp) {
			if (rsp.err_code !== 0) {
				alert(rsp.err_msg);
				return;
			}
			$scope.orderInfo.extPropValues = JSON.parse(rsp.data.ext_prop_value);
			$scope.orderInfo.receiver_name = rsp.data.receiver_name;
			$scope.orderInfo.receiver_mobile = rsp.data.receiver_mobile;
			productGet(rsp.data.product_id);
		});
	}
}]);