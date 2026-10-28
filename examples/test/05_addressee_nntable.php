<?php

require_once '02_common_db.php';
require_once $_stsitecreator;

STCheck::debug("query"); // <- to see current query from URL

echo "Breakpoint in next row<br>";
$children= new STObjectContainer("children", $db);
$children->setDisplayName("Children");
$nnTable= $children->needNnTable("Person", "Children", "Person");
$nnTable->nnTableCheckboxColumn("Child");
$nnTable->select("Children", "child", "Child");

$addressee= new STObjectContainer("addressee", $db);
$addressee->setDisplayName("Addressee");
$addressee->needTable("Country");
$addressee->needTable("State");
$addressee->needTable("County");
$person= $addressee->needTable("Person");
$person->select("first_name", "first Name");
$person->select("last_name", "last Name");
//$person->select("person_id", "Children");
//$person->select("address", "Address");
//$person->namedLink("person_id", $children);
$addressee->needTable("Address");
$addressee->setFirstTable("Person");


$creator= new STSiteCreator($addressee);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$creator->execute();
$creator->display();
