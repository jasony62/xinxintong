app = angular.module('app', ['ngRoute', 'ui.tms', 'tinymce.ui.xxt', 'matters.xxt', 'member.xxt', 'channel.fe.pl']);
app.config(['$routeProvider', '$locationProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/rest/pl/fe/matter/article', {
		templateUrl: '/views/default/pl/fe/matter/article/setting.html?_=3',
		controller: 'ctrlSetting',
	}).otherwise({
		templateUrl: '/views/default/pl/fe/matter/article/setting.html?_=3',
		controller: 'ctrlSetting'
	});
	$locationProvider.html5Mode(true);
}]);
app.controller('ctrlArticle', ['$scope', '$location', 'http2', function($scope, $location, http2) {
	var ls = $location.search();
	$scope.id = ls.id;
	$scope.siteId = ls.site;
	http2.get('/rest/pl/fe/matter/article/get?site=' + $scope.siteId + '&id=' + $scope.id, function(rsp) {
		var url;
		$scope.editing = rsp.data;
		!$scope.editing.attachments && ($scope.editing.attachments = []);
		url = 'http://' + location.host + '/rest/site/fe/matter?site=' + ls.site + '&id=' + ls.id + '&type=article';
		$scope.entry = {
			url: url,
			qrcode: '/rest/pl/fe/matter/article/qrcode?url=' + encodeURIComponent(url),
		};
	});
}]);
app.controller('ctrlSetting', ['$scope', 'http2', 'mattersgallery', 'mediagallery', function($scope, http2, mattersgallery, mediagallery) {
	var modifiedData = {};
	var r = new Resumable({
		target: '/rest/pl/fe/matter/article/attachment/upload?site=' + $scope.siteId + '&articleid=' + $scope.id,
		testChunks: false,
	});
	r.assignBrowse(document.getElementById('addAttachment'));
	r.on('fileAdded', function(file, event) {
		$scope.$root.progmsg = '开始上传文件';
		$scope.$root.$apply('progmsg');
		r.upload();
	});
	r.on('progress', function(file, event) {
		$scope.$root.progmsg = '正在上传文件：' + Math.floor(r.progress() * 100) + '%';
		$scope.$root.$apply('progmsg');
	});
	r.on('complete', function() {
		var f, lastModified, posted;
		f = r.files.pop().file;
		lastModified = f.lastModified ? f.lastModified : (f.lastModifiedDate ? f.lastModifiedDate.getTime() : 0);
		posted = {
			name: f.name,
			size: f.size,
			type: f.type,
			lastModified: lastModified,
			uniqueIdentifier: f.uniqueIdentifier,
		};
		http2.post('/rest/pl/fe/matter/article/attachment/add?site=' + $scope.siteId + '&id=' + $scope.id, posted, function success(rsp) {
			$scope.editing.attachments.push(rsp.data);
			$scope.$root.progmsg = null;
		});
	});
	$scope.modified = false;
	$scope.innerlinkTypes = [{
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
	}];
	$scope.back = function() {
		history.back();
	};
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
	$scope.tinymceSave = function() {
		$scope.update('body');
		$scope.submit();
	};
	$scope.submit = function() {
		http2.post('/rest/pl/fe/matter/article/update?site=' + $scope.siteId + '&id=' + $scope.id, modifiedData, function() {
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
		mediagallery.open($scope.siteId, options);
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
		mediagallery.open($scope.siteId, options);
	});
	$scope.embedMatter = function() {
		mattersgallery.open($scope.siteId, function(matters, type) {
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
		$uibModal.open({
			templateUrl: 'insertMedia.html',
			controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
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
			$uibModal.open({
				templateUrl: 'insertMedia.html',
				controller: ['$uibModalInstance', '$scope', function($mi, $scope) {
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
		http2.post('/rest/pl/fe/matter/article/tag/add?site=' + $scope.siteId + '&id=' + $scope.id, aNewTags, function(rsp) {
			$scope.editing.tags = $scope.editing.tags.concat(aNewTags);
		});
	});
	$scope.$on('tag.xxt.combox.add', function(event, newTag) {
		var oNewTag = {
			title: newTag
		};
		http2.post('/rest/pl/fe/matter/article/tag/add?site=' + $scope.siteId + '&id=' + $scope.id, [oNewTag], function(rsp) {
			$scope.editing.tags.push(oNewTag);
		});
	});
	$scope.$on('tag.xxt.combox.del', function(event, removed) {
		http2.post('/rest/pl/fe/matter/article/tag/remove?site=' + $scope.siteId + '&id=' + $scope.id, [removed], function(rsp) {
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
		http2.post('/rest/pl/fe/matter/article/tag/add2?site=' + $scope.siteId + '&id=' + $scope.id, aNewTags, function(rsp) {
			$scope.editing.tags2 = $scope.editing.tags2.concat(aNewTags);
		});
	});
	$scope.$on('tag2.xxt.combox.add', function(event, newTag) {
		var oNewTag = {
			title: newTag
		};
		http2.post('/rest/pl/fe/matter/article/tag/add2?site=' + $scope.siteId + '&id=' + $scope.id, [oNewTag], function(rsp) {
			$scope.editing.tags2.push(oNewTag);
		});
	});
	$scope.$on('tag2.xxt.combox.del', function(event, removed) {
		http2.post('/rest/pl/fe/matter/article/tag/remove2?site=' + $scope.siteId + '&id=' + $scope.id, [removed], function(rsp) {
			$scope.editing.tags2.splice($scope.editing.tags2.indexOf(removed), 1);
		});
	});
	$scope.delAttachment = function(index, att) {
		$scope.$root.progmsg = '删除文件';
		http2.get('/rest/pl/fe/matter/article/attachment/del?site=' + $scope.siteId + '&id=' + att.id, function success(rsp) {
			$scope.editing.attachments.splice(index, 1);
			$scope.$root.progmsg = null;
		});
	};
	$scope.downloadUrl = function(att) {
		return '/rest/site/fe/matter/article/attachmentGet?site=' + $scope.siteId + '&articleid=' + $scope.editing.id + '&attachmentid=' + att.id;
	};
	http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.siteId + '&resType=article&subType=0', function(rsp) {
		$scope.tags = rsp.data;
	});
	http2.get('/rest/pl/fe/matter/tag/list?site=' + $scope.siteId + '&resType=article&subType=1', function(rsp) {
		$scope.tags2 = rsp.data;
	});
}]);