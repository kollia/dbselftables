<?php

//require_once "test_db_account.php";
require '02_common_db.php';
require_once $_stsitecreator;

//STCheck::debug("query"); // <- to see current query from URL
STCheck::debug("test.see");

$main= new STObjectContainer("bill", $db);
$bill= $main->needTable("Bill");


$creator= new STSiteCreator($main);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
