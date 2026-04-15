<?php

require '02_common_db.php';
require_once $_stsitecreator;

//STCheck::debug("query"); // <- to see current query from URL

$order= new STObjectContainer("Order", $db);
$order->needTable("article");
$order->needTable("Order");
$order->setFirstTable("Order");
$container= $order->getContainerName();
$table= $order->getTableName();
$action= $order->getAction();
if( $container == "Order" &&
    $table == "Order" &&
    $action == STINSERT       )
{
    $query= new STQueryString();
    $bill_id= $query->getParam("stget[from][bill]");
    $order->preSelect("bill", $bill_id);
}

$main= new STObjectContainer("bill", $db);
$main->setFirstTable("Bill");
$bill= $main->needTable("Bill");
$bill->namedLink("bill_id", $order);


$creator= new STSiteCreator($main);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
