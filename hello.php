<?php
foreach ($_SERVER as $key => $value) {
    echo "$key=$value<br>";
}
echo substr(dirname($_SERVER['SCRIPT_NAME']), 1);
