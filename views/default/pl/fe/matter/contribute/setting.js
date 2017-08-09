(function() {
	ngApp.provider.controller('ctrlSetting', ['$scope', '$location', 'http2', 'mediagallery', '$uibModal', 'srvTag', function($scope, $location, http2, mediagallery, $uibModal, srvTag) {
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
		$scope.tagMatter = function(subType){
            var oTags;
            oTags = $scope.oTag;
            srvTag._tagMatter($scope.app, oTags, subType);
        }
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