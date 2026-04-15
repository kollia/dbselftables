<?php

$dbselftables= '../..'; // <- maybe also > dbselftables-x.x-RC
require "$dbselftables/st_pathdef.inc.php";
require_once $_stdbmariadb;
require_once $_stsitecreator;

$db= new STDbMariaDb();
$db->connect($_test_db_host, $_test_db_user, $_test_db_password);
$db->database($_test_db_name);

$creator= new STSiteCreator($db);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
