<?php

require_once '02_common_db.php';
require_once $_stsitecreator;

//STCheck::debug("query"); // <- to see current query from URL

$addressee= new STObjectContainer("addressee", $db);
$addressee->setDisplayName("Addressee");
$addressee->needTable("Country");
$addressee->needTable("State");
$addressee->needTable("County");
$addressee->needTable("Person");
$addressee->needTable("Address");
$addressee->setFirstTable("Person");

$order= new STObjectContainer("Order", $db);
$order->needTable("article");
$orderTable= $order->needTable("Order");
$orderTable->select("amount", "Amount");
$orderTable->select("article", "Article");
$orderTable->align("amount", "center");
$order->setFirstTable("Order");
/*if( $order->currentContainer() &&
    $order->getTableName() == "Order" &&
    $order->getAction() == STINSERT       )
{
    $query= new STQueryString();
    $bill_id= $query->getParam("stget[from][bill]");
    $order->preSelect("bill", 1);
}*/

$main= new STObjectContainer("bill", $db);
$main->setDisplayName("main Container");
$main->needContainer($addressee);
$main->needTable("Article");
$main->setFirstTable("Bill");
$bill= $main->needTable("Bill");
$bill->namedLink("bill_id", $order);


$creator= new STSiteCreator($main);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
