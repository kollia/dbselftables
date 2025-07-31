<?php

require_once '02_common_db.php';

$creator= new STSiteCreator($db);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
