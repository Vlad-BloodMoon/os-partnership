<?php
header("Content-type:text/plain");
include_once("../lib/sendMessage.php");
include_once("../lib/params.php");

//file_put_contents("data.txt", "pw: ".$_GET["pw"]."\n");



$p = new parameters();
$data = "pw=".urlencode($p->pw)."&action=$p->action&user1=$p->user1&user2=$p->user2";
$r = sendMessage("http://your_url/partnership.php", $data);
echo $r;

?>
