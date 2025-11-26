<?php 

require_once($_stdbmysql);

class STDbMariaDB extends STDbMysql
{  
	/**
     *  constructor for access-definition
     *  (currently the same class as STDbMysql)
     * 
     * @param string $identifName identification name of object container
     * @param int    $defaultTyp  default fetch type (MYSQL_NUM, MYSQL_ASSOC, MYSQL_BOTH)
     */
	function __construct($identifName= "main-menue", $defaultTyp= MYSQL_NUM)
   	{
		STDatabase::__construct($identifName, $defaultTyp, "MariaDB");
  	}  
}

?>