ngApp = angular.module('app', ['ngRoute', 'ui.tms', 'ui.xxt','ui.bootstrap']);
ngApp.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/news', {
		templateUrl: '/views/default/pl/fe/matter/news/setting.html?_=2',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/news/setting.html?_=2',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
ngApp.directive('sortable', function() {
    return {
        link: function(scope, el, attrs) {
            el.sortable({
                revert: 50
            });
            el.disableSelection();
            el.on("sortdeactivate", function(event, ui) {
                var from = angular.element(ui.item).scope().$index;
                var to = el.children('li').index(ui.item);
                if (to >= 0) {
                    scope.$apply(function() {
                        if (from >= 0) {
                            scope.$emit('my-sorted', {
                                from: from,
                                to: to
                            });
                        }
                    });
                }
            });
        }
    };
});
ngApp.controller('ctrlNews', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	http2.get('/rest/pl/fe/matter/tag/listTags?site=' + $scope.siteId, function(rsp) {
        $scope.oTag = rsp.data;
    });
	http2.get('/rest/pl/fe/matter/news/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		if(rsp.data.matter_mg_tag !== ''){
            rsp.data.matter_mg_tag.forEach(function(cTag,index){
                $scope.oTag.forEach(function(oTag){
                    if(oTag.id === cTag){
                        rsp.data.matter_mg_tag[index] = oTag;
                    }
                });
            });
        }
		$scope.editing = rsp.data;
		$scope.entryUrl = 'http://' + location.host + '/rest/site/fe/matter?site=' + $scope.siteId + '&id=' + $scope.id + '&type=news';
	});
}]);
ngApp.controller('ctrlSetting', ['$scope', 'http2', 'mattersgallery', '$uibModal', function($scope, http2, mattersgallery, $uibModal) {

	var modifiedData = {};
	$scope.modified = false;
	$scope.matterTypes = [{
		value: 'article',
		title: '单图文',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'news',
		title: '多图文',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'channel',
		title: '频道',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'link',
		title: '链接',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'enroll',
		title: '登记活动',
		url: '/rest/pl/fe/matter'
	}, {
		value: 'lottery',
		title: '抽奖活动',
		url: '/rest/pl/fe/matter'
	}];
	var updateMatters = function() {
		http2.post('/rest/pl/fe/matter/news/updateMatter?site=' + $scope.siteId + '&id=' + $scope.editing.id, $scope.editing.matters);
	};
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/news/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
			modifiedData = {};
			$scope.modified = false;
		});
	};
	$scope.update = function(name) {
		$scope.modified = true;
		modifiedData[name] = $scope.editing[name];
	};
	$scope.assign = function() {
		mattersgallery.open($scope.siteId, function(matters, type) {
			for (var i in matters) {
				matters[i].type = type;
			}
			$scope.editing.matters = $scope.editing.matters.concat(matters);
			updateMatters();
		}, {
			matterTypes: $scope.matterTypes,
			hasParent: false,
			singleMatter: false
		});
	};
	$scope.removeMatter = function(index) {
		$scope.editing.matters.splice(index, 1);
		updateMatters();
	};
	$scope.setEmptyReply = function() {
		mattersgallery.open($scope.siteId, function(matters, type) {
			if (matters.length === 1) {
				var p = {
					mt: type,
					mid: matters[0].id
				};
				http2.post('/rest/pl/fe/matter/news/setEmptyReply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
					$scope.editing.emptyReply = matters[0];
				});
			}
		}, {
			matterTypes: $scope.matterTypes,
			hasParent: false,
			singleMatter: true
		});
	};
	$scope.removeEmptyReply = function() {
		var p = {
			mt: '',
			mid: ''
		};
		http2.post('/rest/pl/fe/matter/news/setEmptyReply?site=' + $scope.siteId + '&id=' + $scope.editing.id, p, function(rsp) {
			$scope.editing.emptyReply = null;
		});
	};
	$scope.$on('my-sorted', function(ev, val) {
		// rearrange $scope.items
		$scope.editing.matters.splice(val.to, 0, $scope.editing.matters.splice(val.from, 1)[0]);
		for (var i = 0; i < $scope.editing.matters.length; i++) {
			$scope.editing.matters.seq = i;
		}
		updateMatters();
	});
	(function() {
		new ZeroClipboard(document.querySelectorAll('.text2Clipboard'));
	})();
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
                        if(subType === 'C'){
                            $scope.editing.matter_cont_tag = addMatterTag;
                        }else{
                            $scope.editing.matter_mg_tag = addMatterTag;
                        }
                    });
                    $mi.close();
                };
            }],
            backdrop: 'static',
        });
    };
}]);