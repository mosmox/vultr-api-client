<?php

include_once "vultrClientVM.php";
$api = new Vultr\Api('1');
if($api->isConnect() == true) {
    print_r($api->ServerList());
} else {
    echo "Can not connect to remote service.\r\n";
}


