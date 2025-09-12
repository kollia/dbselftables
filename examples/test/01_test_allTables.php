<?php

$dbselftables= 'dbselftables';
require_once "$dbselftables/st_pathdef.inc.php";
require_once $_stdbmariadb;
require_once $_stsitecreator;

 /**
  * This is a test script for all regular example tables only in one container.
  * It allows to test insert, update and delete operations.
  * - For the State table there is not Insert allowed and so it will be tested
  *   update and delete over an self new implemented entry.
  * - For the County table there is only an update allowed and it should test
  *   over two run which update by second to the original data.
  */
 STCheck::debug("test");

$db= new STDbMariaDb();
$db->connect('localhost', '<user>', '<password>');
$db->database('test');


$country= $db->getTable("Country");
$country->setDisplayName("existing Countries");
$country->identifColumn("name", "Country");
$country->select("name", "Name");
$country->setMaxRowSelect(20);

// STCheck::no_test_error for 'C0970D098fTt7R0S*' will be made before siteCreator execute
$state= $db->getTable("State");
$state->setDisplayName("States");
$state->identifColumn("name", "State");
$state->select("state_id", "State ID");
$state->select("name", "Name");
$state->select("country", "from Country");
$state->orderBy("name");
$state->setMaxRowSelect(20);
$state->noInsert();

STCheck::no_test_error('C0970D098fTt4R0S03', 'update');
$county= $db->getTable("County");
$county->identifColumn("name", "County");
$county->select("state", "State");
$county->select("name", "County");
$county->setMaxRowSelect(50);
$county->noInsert();
$county->noDelete();

$person= $db->getTable("Person");
$person->identifColumn("first_name", "first Name");
$person->identifColumn("last_name", "last Name");
$person->select("first_name", "first Name");
$person->select("last_name", "last Name");
$person->select("address", "Address");
$person->setMaxRowSelect(50);

$address= $db->getTable("Address");
$address->identifColumn("city", "City");
$address->identifColumn("street", "Street");
//$address->identifColumn("county", "County");
$address->select("city", "City");
$address->select("street", "Street");
$address->select("county", "from County");
$address->setMaxRowSelect(50);

$bill= $db->getTable("Bill");
$bill->identifColumn("bill_id", "Bill");
$bill->select("bill_id", "Bill");
$bill->select("person", "for Person");
$bill->select("address", "bill Address");
$bill->select("date", "date of invoice");
$bill->setMaxRowSelect(50);

$order= $db->getTable("Order");
$order->select("bill", "Bill");
$order->select("order_id", "Order ID");
$order->select("amount", "Count");
$order->align("amount", "right");
$order->select("article", "Article");
$order->setMaxRowSelect(50);

$article= $db->getTable("Article");
$article->identifColumn("title", "Article");
$article->select("title", "Article");
$article->select("content", "Description");
$article->select("price", "Price");
$article->setMaxRowSelect(50);


$creator= new STSiteCreator($db);
$creator->addCssLink("$dbselftables/design/websitecolors.css");
$siteNr= $creator->getSiteNumber();
if($siteNr == 'C0970D098fTt7R0S03')
{
    $inserter= new STDbInserter($state);
    $inserter->fillColumn("name", "colum to update and delete");
    $inserter->fillColumn("country", 1);
    $inserter->execute();
    $pk= $inserter->getLastInsertID();
    STCheck::no_test_error('C0970D098fTt7R0S03', STUPDATE, array("state_id"=>$pk));
    
}elseif($siteNr == 'C0970D098fTt7R0S09')
{
    $selector= new STDbSelector($state);
    $selector->select("State","state_id");
    $selector->where("name='update text (removable)'");
    $selector->execute();
    $pk= $selector->getSingleResult();
    STCheck::no_test_error('C0970D098fTt7R0S09', STDELETE, array("state_id"=>$pk));
}
$creator->execute();
$creator->display();
