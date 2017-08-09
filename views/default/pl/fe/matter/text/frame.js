ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt', 'service.matter']);
ngApp.config(['$routeProvider', '$locationProvider', 'srvTagProvider', function($routeProvider, $locationProvider, srvTagProvider) {
	$routeProvider.when('/rest/pl/fe/matter/news', {
		templateUrl: '/views/default/pl/fe/matter/text/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/text/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
	 //设置服务参数
    (function() {
        var ls, siteId;
        ls = location.search;
        siteId = ls.match(/[\?&]site=([^&]*)/)[1];
        //
        srvTagProvider.config(siteId);
    })();
}]);
ngApp.controller('ctrlText', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', '$uibModal', 'srvTag', function($scope, http2, $uibModal, srvTag) {
	$scope.create = function() {
		var obj = {
			title: '新文本素材',
		};
		http2.post('/rest/pl/fe/matter/text/create?site=' + $scope.siteId, obj, function(rsp) {
			$scope.texts.splice(0, 0, rsp.data);
			$scope.selectOne(0);
		});
	};
	$scope.deleteOne = function(event) {
		event.preventDefault();
		event.stopPropagation();
		http2.get('/rest/pl/fe/matter/text/delete?site=' + $scope.siteId + '&id=' + $scope.editing.id, function(rsp) {
			$scope.texts.splice($scope.selectedIndex, 1);
			if ($scope.texts.length == 0) {
				alert('empty');
			} else if ($scope.selectedIndex == $scope.texts.length) {
				$scope.selectOne($scope.selectedIndex - 1);
			} else {
				$scope.selectOne($scope.selectedIndex);
			}
		});
	};
	$scope.selectOne = function(index) {
		$scope.selectedIndex = index;
		$scope.editing = $scope.texts[index];
	};
	$scope.update = function(prop) {
		var p = {};
		p[prop] = $scope.editing[prop];
		http2.post('/rest/pl/fe/matter/text/update?site=' + $scope.siteId + '&id=' + $scope.editing.id, p);
	};
    $scope.tagMatter = function(subType){
        var oTags;
        oTags = $scope.oTag;
        srvTag._tagMatter($scope.editing, oTags, subType);
    };
	$scope.doSearch = function() {
		var url = '/rest/pl/fe/matter/text/list?site=' + $scope.siteId,
			params = {};
		http2.get(url, function(rsp) {
			$scope.texts = rsp.data;
			if ($scope.texts.length > 0)
				$scope.texts.forEach(function(text){
					if(text.matter_mg_tag !== ''){
		                text.matter_mg_tag.forEach(function(cTag,index){
		                    $scope.oTag.forEach(function(oTag){
		                        if(oTag.id === cTag){
		                            text.matter_mg_tag[index] = oTag;
		                        }
		                    });
		                });
		            }
				});
				$scope.selectOne(0);
		});
	};
	http2.get('/rest/pl/fe/matter/tag/listTags?site=' + $scope.siteId, function(rsp) {
        $scope.oTag = rsp.data;
    });
	$scope.doSearch();
}]);
ngApp.filter("truncate", function() {
	return function(text, length) {
		if (text) {
			var ellipsis = text.length > length ? "..." : "";
			return text.slice(0, length) + ellipsis;
		};
		return text;
	}
});