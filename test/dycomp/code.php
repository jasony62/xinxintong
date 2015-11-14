<?php
$component = array(
	//'html' => '<div ng-bind="test2()"></div>',
	'html' => '',
	'css' => 'body{background:#ccc;}',
	'js' => 'app.register.controller("dynaCtrl",["$scope",function($scope){$scope.data2="abc";}]);',
);

header('Content-type: application/json');
header('Cache-Control: no-cache');
echo json_encode($component);