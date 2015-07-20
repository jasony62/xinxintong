<?php
$data = file_get_contents("php://input");

$obj = json_decode($data);

echo $obj->css;