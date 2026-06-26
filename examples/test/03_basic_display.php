<?php

require_once '02_common_db.php';
require_once $_stsitecreator;

//STCheck::debug("db.statement");

$creator= new STSiteCreator($db);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
