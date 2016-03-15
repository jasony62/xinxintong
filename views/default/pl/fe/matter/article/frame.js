app = angular.module('app', ['ngRoute', 'ui.tms', 'matters.xxt', 'channel.fe.pl']);
app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/mp/matter/article/setting', {
		templateUrl: '/views/default/pl/fe/matter/article/setting.html?_=1',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/article/setting.html?_=1',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
app.controller('ctrlArticle', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteid = ls.site;
	http2.get('/rest/mp/matter/article/get?id=' + $scope.id, function(rsp) {
		$scope.editing = rsp.data;
		$scope.entryUrl = 'http://' + location.host + '/rest/mi/matter?mpid=' + ls.site + '&id=' + ls.id + '&type=article';
		$scope.entryUrl += '&tpl=' + ($scope.editing.custom_body === 'N' ? 'std' : 'cus');
	});
}]);
app.controller('ctrlSetting', ['$scope', 'http2', 'mattersgallery', 'mediagallery', function($scope, http2, mattersgallery, mediagallery) {
	var modifiedData = {};
	$scope.modified = false;
	$scope.innerlinkTypes = [{
		value: 'article',
		title: '单图文',
		url: '/rest/mp/matter'
	}, {
		value: 'news',
		title: '多图文',
		url: '/rest/mp/matter'
	}, {
		value: 'channel',
		title: '频道',
		url: '/rest/mp/matter'
	}];
	window.onbeforeunload = function(e) {
		var message;
		if ($scope.modified) {
			message = '修改还没有保存，是否要离开当前页面？',
				e = e || window.event;
			if (e) {
				e.returnValue = message;
			}
			return message;
		}
	};
	$scope.onBodyChange = function() {
		$scope.modified = true;
		modifiedData['body'] = encodeURIComponent($scope.editing['body']);
	};
	$scope.submit = function() {
		http2.post('/rest/mp/matter/article/update?id=' + $scope.id, modifiedData, function() {
			modifiedData = {};
			$scope.modified = false;
		});
	};
	$scope.update = function(name) {
		$scope.modified = true;
		modifiedData[name] = name === 'body' ? encodeURIComponent($scope.editing[name]) : $scope.editing[name];
	};
	$scope.setPic = function() {
		var options = {
			callback: function(url) {
				$scope.editing.pic = url + '?_=' + (new Date()) * 1;
				$scope.update('pic');
			}
		};
		mediagallery.open($scope.siteid, options);
	};
	$scope.removePic = function() {
		$scope.editing.pic = '';
		$scope.update('pic');
	};
	$scope.$on('tinymce.multipleimage.open', function(event, callback) {
		var options = {
			callback: callback,
			multiple: true,
			setshowname: true
		};
		mediagallery.open($scope.siteid, options);
	});
	$scope.embedMatter = function() {
		mattersgallery.open('mattersgallery.open', function(matters, type) {
			var editor, dom, i, matter, mtype, fn;
			editor = tinymce.get('body1');
			dom = editor.dom;
			for (i = 0; i < matters.length; i++) {
				matter = matters[i];
				mtype = matter.type ? matter.type : type;
				fn = "openMatter($event," + matter.id + ",'" + mtype + "')";
				editor.insertContent(dom.createHTML('p', {
					'class': 'matter-link'
				}, dom.createHTML('a', {
					"ng-click": fn,
				}, dom.encode(matter.title))));
			}
		}, {
			matterTypes: $scope.innerlinkTypes,
			hasParent: false,
			singleMatter: true
		});
	};
	var insertVideo = function(url) {
		var editor, dom, html;
		if (url.length > 0) {
			editor = tinymce.get('body1');
			dom = editor.dom;
			html = dom.createHTML('p', {},
				dom.createHTML(
					'video', {
						style: 'width:100%',
						controls: "controls",
					},
					dom.createHTML(
						'source', {
							src: url,
							type: "video/mp4",
						})
				)
			);
			editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
		}
	};
	$scope.embedVideo = function() {
		$modal.open({
			templateUrl: 'insertMedia.html',
			controller: ['$modalInstance', '$scope', function($mi, $scope) {
				$scope.data = {
					url: ''
				};
				$scope.cancel = function() {
					$mi.dismiss()
				};
				$scope.ok = function() {
					$mi.close($scope.data)
				};
			}],
			backdrop: 'static',
		}).result.then(function(data) {
			insertVideo(data.url);
		});
	};
	var insertAudio = function(url) {
		var editor, dom, html;
		if (url.length > 0) {
			editor = tinymce.get('body1');
			dom = editor.dom;
			html = dom.createHTML('p', {}, dom.createHTML('audio', {
				src: url,
				controls: "controls",
			}));
			editor.insertContent('<p>&nbsp;</p>' + html + '<p>&nbsp;</p>');
		}
	};
	$scope.embedAudio = function() {
		if ($scope.mpaccount._env.SAE) {
			$modal.open({
				templateUrl: 'insertMedia.html',
				controller: ['$modalInstance', '$scope', function($mi, $scope) {
					$scope.data = {
						url: ''
					};
					$scope.cancel = function() {
						$mi.dismiss()
					};
					$scope.ok = function() {
						$mi.close($scope.data)
					};
				}],
				backdrop: 'static',
			}).result.then(function(data) {
				insertAudio(data.url);
			});
		} else {
			$scope.$broadcast('mediagallery.open', {
				mediaType: '音频',
				callback: insertAudio
			});
		}
	};
	$scope.$on('tag.xxt.combox.done', function(event, aSelected) {
		var aNewTags = [];
		angular.forEach(aSelected, function(selected) {
			var existing = false;
			angular.forEach($scope.editing.tags, function(tag) {
				if (selected.title === tag.title) {
					existing = true;
				}
			});
			!existing && aNewTags.push(selected);
		});
		http2.post('/rest/mp/matter/article/addTag?id=' + $scope.id, aNewTags, function(rsp) {
			$scope.editing.tags = $scope.editing.tags.concat(aNewTags);
		});
	});
	$scope.$on('tag.xxt.combox.add', function(event, newTag) {
		var oNewTag = {
			title: newTag
		};
		http2.post('/rest/mp/matter/article/addTag?id=' + $scope.id, [oNewTag], function(rsp) {
			$scope.editing.tags.push(oNewTag);
		});
	});
	$scope.$on('tag.xxt.combox.del', function(event, removed) {
		http2.post('/rest/mp/matter/article/removeTag?id=' + $scope.editing.id, [removed], function(rsp) {
			$scope.editing.tags.splice($scope.editing.tags.indexOf(removed), 1);
		});
	});
	$scope.$on('tag2.xxt.combox.done', function(event, aSelected) {
		var aNewTags = [];
		angular.forEach(aSelected, function(selected) {
			var existing = false;
			angular.forEach($scope.editing.tags2, function(tag) {
				if (selected.title === tag.title) {
					existing = true;
				}
			});
			!existing && aNewTags.push(selected);
		});
		http2.post('/rest/mp/matter/article/addTag2?id=' + $scope.id, aNewTags, function(rsp) {
			$scope.editing.tags2 = $scope.editing.tags2.concat(aNewTags);
		});
	});
	$scope.$on('tag2.xxt.combox.add', function(event, newTag) {
		var oNewTag = {
			title: newTag
		};
		http2.post('/rest/mp/matter/article/addTag2?id=' + $scope.id, [oNewTag], function(rsp) {
			$scope.editing.tags2.push(oNewTag);
		});
	});
	$scope.$on('tag2.xxt.combox.del', function(event, removed) {
		http2.post('/rest/mp/matter/article/removeTag2?id=' + $scope.editing.id, [removed], function(rsp) {
			$scope.editing.tags2.splice($scope.editing.tags.indexOf(removed), 1);
		});
	});
	http2.get('/rest/mp/matter/tag?resType=article&subType=0', function(rsp) {
		$scope.tags = rsp.data;
	});
	http2.get('/rest/mp/matter/tag?resType=article&subType=1', function(rsp) {
		$scope.tags2 = rsp.data;
	});
}]);