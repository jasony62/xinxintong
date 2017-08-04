ngApp = angular.module('app', ['ngRoute', 'ui.bootstrap', 'ui.tms', 'ui.xxt']);
ngApp.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/news', {
		templateUrl: '/views/default/pl/fe/matter/text/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/text/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.controller('ctrlText', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', '$uibModal', function($scope, http2, $uibModal) {
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
	$scope.tagMatter = function(subType) {
        var oApp, oTags, tagsOfData;
        oApp = $scope.editing;
        oTags = $scope.oTag;
        $uibModal.open({
            templateUrl: 'tagMatterData.html',
            controller: ['$scope', '$uibModalInstance', function($scope2, $mi) {
                var model;
                $scope2.apptags = oTags;

                if(subType === 'C'){
                    tagsOfData = oApp.matter_cont_tag;
                    $scope2.tagTitle = '内容标签';
                }else{
                    tagsOfData = oApp.matter_mg_tag;
                    $scope2.tagTitle = '管理标签';
                }
                $scope2.model = model = {
                    selected: []
                };
                if (tagsOfData) {
                    tagsOfData.forEach(function(oTag) {
                        var index;
                        if (-1 !== (index = $scope2.apptags.indexOf(oTag))) {
                            model.selected[$scope2.apptags.indexOf(oTag)] = true;
                        }
                    });
                }
                $scope2.createTag = function() {
                    var newTags;
                    if ($scope2.model.newtag) {
                        newTags = $scope2.model.newtag.replace(/\s/, ',');
                        newTags = newTags.split(',');
                        http2.post('/rest/pl/fe/matter/tag/create?site=' + oApp.siteid, newTags, function(rsp) {
                            rsp.data.forEach(function(oNewTag) {
                                $scope2.apptags.push(oNewTag);
                            });
                        });
                        $scope2.model.newtag = '';
                    }
                };
                $scope2.cancel = function() { $mi.dismiss(); };
                $scope2.ok = function() {
                    var addMatterTag = [];
                    model.selected.forEach(function(selected, index) {
                        if (selected) {
                            addMatterTag.push($scope2.apptags[index]);
                        }
                    });
                    var url = '/rest/pl/fe/matter/tag/add?site=' + oApp.siteid + '&resId=' + oApp.id + '&resType=' + oApp.type + '&subType=' + subType;
                    http2.post(url, addMatterTag, function(rsp) {
                        $scope.editing.matter_mg_tag = addMatterTag;
                    });
                    $mi.close();
                };
            }],
            backdrop: 'static',
        });
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