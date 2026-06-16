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

$orderContainer= new STObjectContainer("Order", $db);
$orderContainer->needTable("article");
$orderTable= $orderContainer->needTable("Order");
$orderTable->select("amount", "Amount");
$orderTable->select("article", "Article");
$orderTable->align("amount", "center");
$orderContainer->setFirstTable("Order");

if( $orderContainer->currentContainer() &&
    $orderContainer->getTableName() == "Order" &&
    $orderContainer->getAction() == STINSERT       )
{
    $query= new STQueryString();
    $bill_id= $query->getParam("stget[from][bill]");
    $orderContainer->preSelect("bill", $bill_id);
}

$main= new STObjectContainer("bill", $db);
$main->needContainer($addressee);
$main->needTable("Article");
$main->setFirstTable("Bill");
$bill= $main->needTable("Bill");
$bill->namedLink("bill_id", $order);


$creator= new STSiteCreator($main);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
