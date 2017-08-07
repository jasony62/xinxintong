(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', 'mediagallery', '$uibModal', function($scope, $location, http2, mediagallery, $uibModal) {
		var tinymceEditor;
		$scope.run = function() {
			$scope.app.state = 2;
			$scope.update('state');
			$scope.submit().then(function() {
				location.href = '/rest/pl/fe/matter/contribute/running?site=' + $scope.siteId + '&id=' + $scope.id;
			});
		};
		$scope.setPic = function() {
			var options = {
				callback: function(url) {
					$scope.app.pic = url + '?_=' + (new Date()) * 1;
					$scope.update('pic');
				}
			};
			mediagallery.open($scope.siteId, options);
		};
		$scope.removePic = function() {
			var nv = {
				pic: ''
			};
			http2.post('/rest/pl/fe/matter/group/update?aid=' + $scope.id, nv, function() {
				$scope.app.pic = '';
			});
		};
		$scope.tagMatter = function(subType) {
	        var oApp, oTags, tagsOfData;
	        oApp = $scope.app;
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
	                            $scope.app.matter_cont_tag = addMatterTag;
	                        }else{
	                            $scope.app.matter_mg_tag = addMatterTag;
	                        }
	                    });
	                    $mi.close();
	                };
	            }],
	            backdrop: 'static',
	        });
	    };
		$scope.$on('sub-channel.xxt.combox.done', function(event, data) {
			var app = $scope.app;
			app.params.subChannels === undefined && (app.params.subChannels = []);
			angular.forEach(data, function(c) {
				app.subChannels.push({
					id: c.id,
					title: c.title
				});
				app.params.subChannels.push(c.id);
			});
			$scope.update('params');
		});
		$scope.$on('sub-channel.xxt.combox.del', function(event, ch) {
			var i, app = $scope.app;
			i = app.subChannels.indexOf(ch);
			app.subChannels.splice(i, 1);
			i = app.params.subChannels.indexOf(ch.id);
			app.params.subChannels.splice(i, 1);
			$scope.update('params');
		});
		$scope.$on('tinymce.multipleimage.open', function(event, callback) {
			var options = {
				callback: callback,
				multiple: true,
				setshowname: true
			};
			mediagallery.open($scope.siteId, options);
		});
		$scope.$watch('app', function(app) {
			if (app && tinymceEditor) {
				tinymceEditor.setContent(app.template_body ? app.template_body : '');
			}
		});
		$scope.$on('tinymce.instance.init', function(event, editor) {
			tinymceEditor = editor;
			if ($scope.app) {
				editor.setContent($scope.app.template_body ? $scope.app.template_body : '');
			}
		});
		$scope.$on('tinymce.content.change', function(event, changed) {
			var content;
			content = tinymceEditor.getContent();
			if (content !== $scope.app.template_body) {
				$scope.app.template_body = content;
				$scope.update('template_body');
			}
		});
	}]);
})();