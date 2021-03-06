<?php

require_once($_stsession);

$__static_global_STAlias_ID= 0;

class STAliasTable
{
    var $ID= 0;
	var $Name;
	var $title= "";
	var	$sPKColumn;
	var $columns;
	/**
	 * all defined aliases
	 * for columns in table
	 *  
	 * @access private;
	 * @var string array
	 */
	var $aAliases= array();
	var $titles= null;
	var	$sFirstAction= STLIST;
	var	$identification= array();
	var	$bDisplayIdentifs= true;
	var	$abNewChoice= array(); // hier wird in der Tabelle abgespeichert ob in der neuen schon etwas neu ausgefuehrt wurde
	var	$show= array();
	var	$bDisplaySelects= true;
	var	$bColumnSelects= true; // have the Table columns in select-statement
    var $FK= array();// bug: if in one table be two foreign keys to the same table
					 // 	 there be seen only one
	var	$aFks= array();	// so make an new one
	var	$aBackJoin= array();// for join in table where the other have an foreigen key to the own
							// and it should be in the select-statement
    var $error;
	var $errorText;
	var	$showTypes= array();
	var $oWhere;
	var $asOrder= array();
	var	$identifier;
	var	$bDistinct= false;
	var $bOrder= NULL;
	var $aLinkAddresses= array();
	var	$bInsert= true;
	var $bUpdate= true;
	var	$bDelete= true;
	/**
	 * 
	 * @var boolean whether should sort Table, by clicking of one of the head-names 
	 */
    var $doTableSorting= true;
	var	$bShowName= true;
	// alex 08/06/2005:	nun koennen Werte auch Statisch in der
	//					STDbTable gesetzt werden
	var $aSetAlso= array();
	var	$aCallbacks= array();
	// alex 09/06/2005:	limitieren von Rowanzahl aus der Datenbank
	var	$nFirstRowSelect= 0;
	var $nMaxRowSelect= null;// null -> es werden alle Rows aufgelistet
	var $nAktSelectedRow= 0;
	var $bAlwaysIndex= true;
	var	$bSetLinkByNull= false;
	var	$bModifyFk= true;//ob die Tabelle anhand der ForeignKeys Modifiziert werden soll
	var	$onlyRadioButtons= array(); // wenn nur zwei Enumns vorhanden sind, trotzdem radio Buttons verwenden
	var $listArrangement= STHORIZONTAL;//bestimmt das Layout der OSTTable
	var $bListCaption= true;// Beschriftung (???berschrift) der Tabelle
	var	$oSearchBox= null; // Suchen-Box bei Auflistung der Tabelle anzeigen

	var $bLimitOwn= true;	// steht im URI eine Einschr???nkung der eigenen Tabelle
							// soll diese Angewand werden und nur einen Eintrag zeigen
	var $sDeleteLimitation= "true";	// wenn in einen Link von dieser Tabelle aus gesprungen wird,
									// wird eine Einschr???nkung von dieser Tabelle gesetzt.
									// das dient dazu, dass nur die Eintr???ge vom FK auf diesen link zur???ck
									// verfolgt wird.
									// springt der User nun ???ber den BackButton auf diese Tabelle zur???ck
									// wird die Einschr???nkung bei "true" gel???scht
									// bei "false" nicht
									// und bei "older" erst wenn auch aus dieser Tabelle mit dem BackButton
									// zur???ckgesprungen wird
	var $asAccessIds= array();
	var $sAcessClusterColumn= array(); // in den angegebenen Columns wird ein Cluster f???r den Zugriff gespeichert
	var	$aUnlink= array(); 	// wenn die Upgelodete Datei nicht gel???scht werden soll
							// ist hier der Alias-Name der Spalte eingetragen
	var	$nDisplayColumns= 1; // in wieviel Hauptspalten die Aufgelistete Tabelle angezeigt werden soll
	var	$aAttributes= array(); // alle Attribute die in den diversen Tables zu den ColumnTags/RowTags hinzugef???gt werden sollen
	var	$linkParams= array(); // alle Get-Parameter die bei einem Link eingef???gt, ge???ndert oder gel???scht werden sollen
	var $bHasGroupColumns= false; // is any group-column (count, min, max, ...) be set
	var	$bDynamicAccessIn= null; // an array with access on all actions
								// if variable is null, no dynamic access is searched
	var	$oTinyMCE= array(); // array of objects from TinyMCE for CMS, for an selected Column
	var	$aInputSize= array(); // width and height for the input/textarea - tag
	var	$asForm= array(); // save there the require names for an form-table
	var	$bIsNnTable= false; // is the table declared as among-table
	var	$aPostToGet= array(); // transfer the variables from POST- to GET-params by forwarding
	var	$aCheckDef= array();
	var	$aActiveLink= array();// active Link in navigation table
	/**
	 * count of actual inner table for an update or insert box
	 */
	var $nInnerTable= 0;
	/**
	 * array of all Inner tables, which have an begin and end column
	 */
	var $aInnerTables= array();
	var $dateIndex;
	var $null= null;

	 /**
	  * Constructor of normal table object
	  * 
	  * @param {table object, string, null} $oTable exist other table, new tablename or null to create symbolic null table
	  */
	function __construct($oTable= null)
	{
	    global $__static_globl_STAlias_ID;
	    
		Tag::paramCheck($oTable, 1, "string", "STAliasTable", "null");

        $__static_globl_STAlias_ID++;
        $this->ID= $__static_globl_STAlias_ID;
    	$this->bOrder= NULL;
		if(typeof($oTable, "STAliasTable"))
		{
		    if(STCheck::isDebug("db.table.fk"))
		    {
    		    STCheck::echoDebug("db.table.fk", "create new ".get_class($this)."::<b>".$oTable->Name."</b> with ID:".$this->ID." from ".get_class($oTable)."::<b>".$oTable->Name."</b> with ID:".$oTable->ID);
    		    echo "old <b>FKs</b>:<br>";
    		    st_print_r($oTable->aBackJoin, 3);
    		    $this->copy($oTable);
    		    echo "new <b>FKs</b>:<br>";
    		    st_print_r($this->aBackJoin, 3);
    		    showErrorTrace();
		    }
		    //return;
		}
		if(Tag::isDebug())
		{
		    STCheck::echoDebug("table", "create new ID:".$this->ID." for ".get_class($this));
		    if($oTable === null)
	        {
	            STCheck::echoDebug("table", "create non correct null table.");
	        }elseif(is_string($oTable))
	        {
	            Tag::echoDebug("table", "create new table <b>".$oTable."</b>");
	        }else
	            Tag::echoDebug("table", "copy table <b>".$oTable->Name."</b>");
		}
		$this->asForm= array(	"button"=>	"save",
								"form"=>	"st_checkForm",
								"action"=>	null			);
    	$this->error= false;
		if($oTable !== null)
		{
		    if(typeof($oTable, "STAliasTable"))
	     	    $this->Name= $oTable->Name;
		    else
		        $this->Name= $oTable;
	     	$this->bCorrect= true;
		}else 
		{
			$this->Name= "NULL";
			$this->bCorrect= false;
		}
		//st_print_r($this->Name);
		STCheck::echoDebug("table", "crate table ".$this->Name." with ID:".$this->ID);
	}
	function __clone()
	{
	    global $__static_globl_STAlias_ID;
	    
	    $__static_globl_STAlias_ID++;
	    $oldID= $this->ID;
	    $this->ID= $__static_globl_STAlias_ID;
	    STCheck::echoDebug("table", "clone table ".$this->Name." from ID:$oldID to new ID:".$this->ID);
	    $this->bInsert= true;
	    $this->bUpdate= true;
	    $this->bDelete= true;
	    /**
	     *
	     * @var boolean whether should sort Table, by clicking of one of the head-names
	     */
	    $this->doTableSorting= true;
	    $this->bShowName= true;
	    // alex 08/06/2005:	nun koennen Werte auch Statisch in der
	    //					STDbTable gesetzt werden
	    $this->aSetAlso= array();
	    $this->aCallbacks= array();
	    // alex 09/06/2005:	limitieren von Rowanzahl aus der Datenbank
	    $this->nFirstRowSelect= 0;
	    $this->nMaxRowSelect= null;// null -> es werden alle Rows aufgelistet
	    $this->nAktSelectedRow= 0;
	    $this->bAlwaysIndex= true;
	    $this->bModifyFk= true;//ob die Tabelle anhand der ForeignKeys Modifiziert werden soll
	    $this->listArrangement= STHORIZONTAL;//bestimmt das Layout der OSTTable
	    $this->oSearchBox= null; // Suchen-Box bei Auflistung der Tabelle anzeigen
	}
	function title($title)
	{
		$this->title= $title;
	}
	function getTitle()
	{
		return $this->title;
	}
	function correctTable()
	{
		if(	$this->Name == "NULL" &&
			$this->bCorrect == false	)
		{
			return false;
		}
		return true;
	}
	function copy($Table)
	{
		STCheck::param($Table, 0, "STAliasTable");
		
		$this->bOrder= NULL;
     	$this->error= $Table->error;
    	$this->errorText= $Table->errorText;
     	$this->Name= $Table->Name;
    	$this->FK= $Table->FK;
		$this->aFks= $Table->aFks;
		$this->aBackJoin= $Table->aBackJoin;
		$this->bModifyFk= $Table->bModifyFk;
    	$this->identification= $Table->identification;
    	$this->showTypes= $Table->showTypes;
		$this->aActiveLink= $Table->aActiveLink;
    	$this->oWhere= &$Table->oWhere;
    	$this->asOrder= $Table->asOrder;
    	$this->sPKColumn= $Table->sPKColumn;
		$this->show= $Table->show;
    	$this->columns= $Table->columns;
		$this->bLimitOwn= $Table->bLimitOwn;
		$this->bDistinct= $Table->bDistinct;
		$this->aSetAlso= $Table->aSetAlso;
		$this->asForm=$Table->asForm;
		$this->bDisplaySelects= $Table->bDisplaySelects;
		$this->bDisplayIdentifs= $Table->bDisplayIdentifs;
		$this->bIsNnTable= $Table->bIsNnTable;
		$this->oTinyMCE= &$Table->oTinyMCE;
		$this->aInputSize= $Table->aInputSize;
	}
	function getColumnName($column)
	{
		STCheck::paramCheck($column, 1, "string", "int");

		if( isset($this->columns[$column]) &&
			is_numeric($column)	)
		{
			return $this->columns[$column]["name"];
		}

		$desc= STDbTableDescriptions::instance();
		$column= $desc->getColumnName($this->Name, $column);
		if($this->haveColumn($column))
			return $column;
		return null;
	}
	function postToGetTransfer($var /*, ...*/)
	{
		$vars= func_get_args();
		$this->aPostToGet[]= $vars;
	}
	function inputSize($column, $width, $height= null)
	{
		$this->aInputSize[$column]["width"]= $width;
		if($height)
			$this->aInputSize[$column]["height"]= $height;
	}
	function attribute($element, $attribute, $value, $tableType= null)
	{
		if($tableType==STFORM)
		{
			$this->aAttributes[STINSERT][$element][$attribute]= $value;
			$this->aAttributes[STUPDATE][$element][$attribute]= $value;
			return;
		}
		if($tableType!==null)
		{echo "tableType:$tableType<br />";
			$this->aAttributes[$tableType][$element][$attribute]= $value;
			return;
		}
		// tableType is NULL
		$this->aAttributes[STINSERT][$element][$attribute]= $value;
		$this->aAttributes[STUPDATE][$element][$attribute]= $value;
		$this->aAttributes[STLIST][$element][$attribute]= $value;

	}
	function tableAttribute($attribute, $value, $tableType= null)
	{
		$this->attribute("table", $attribute, $value, $tableType);
	}
	function trAttribute($attribute, $value, $tableType= null)
	{
		$this->attribute("tr", $attribute, $value, $tableType);
	}
	function thAttribute($attribute, $value, $tableType= null)
	{
		$this->attribute("th", $attribute, $value, $tableType);
	}
	function tdAttribute($column, $attribute, $value, $tableType= null)
	{
		$this->attribute("td", $attribute, $value, $tableType= null);
	}
	/**
	 * make allignment for specific column.<br />
	 * can be differend between list-, insert- and update-table
	 * 
	 * @param string $column name of column
	 * @param string $value whether alignment should be 'left', 'center' or 'right'
	 * @param enum $tableType for which display table - STLIST, STINSERT or STDELETE - alignment should be.
	 * 							no parameter set (default) is definition for all
	 */
	function align($column, $value, $tableType= null)
	{
		$this->tdAttribute($column, "align", $value);
	}
	function accessBy($clusters, $action= STLIST, $toAccessInfoString= "", $customID= null)
	{
		Tag::paramCheck($clusters, 1, "string");
		Tag::paramCheck($action, 2, "string");
		Tag::paramCheck($toAccessInfoString, 3, "string", "empty(string)");
		Tag::paramCheck($customID, 4, "int", "null");

		if($action==STADMIN)
		{
			$this->accessBy($clusters, STINSERT, $toAccessInfoString, $customID);
			$this->accessBy($clusters, STUPDATE, $toAccessInfoString, $customID);
			$this->accessBy($clusters, STDELETE, $toAccessInfoString, $customID);
			//return;
			// set to all extra actions also STADMIN
		}
		if(	!isset($this->abNewChoice["accessBy_".$action]) ||
			!$this->abNewChoice["accessBy_".$action]			)
		{
			$this->asAccessIds[$action]= array();
			$this->abNewChoice["accessBy_".$action]= true;
		}
		if(!is_array($this->asAccessIds[$action]))
			$this->asAccessIds[$action]= array();
		$this->asAccessIds[$action]["cluster"]= $clusters;
		$this->asAccessIds[$action]["accessString"]= $toAccessInfoString;
		$this->asAccessIds[$action]["customID"]= $customID;
	}
	function clearAccess()
	{
		$this->asAccessIds= array();
	}
	function getAccessCluster($action)
	{
		Tag::paramCheck($action, 1, "check",	$action===STALLDEF || $action===STLIST || $action===STINSERT ||
												$action===STUPDATE || $action===STDELETE,
												"STALLDEF", "STLIST", "STINSERT", "STUPDATE", "STDELETE");

		$aRv= null;
		if(isset($this->asAccessIds[$action]["cluster"]))
			$aRv= $this->asAccessIds[$action]["cluster"];
/*		if($action===STLIST)
		{// if action is STLIST set also the STISERT, STUPDATE and STDELETE clusters
			$clusters= array_merge($clusters, $this->asAccessIds[STINSERT]);
			$clusters= array_merge($clusters, $this->asAccessIds[STUPDATE]);
			$clusters= array_merge($clusters, $this->asAccessIds[STDELETE]);
		}else*/
		if(	!isset($aRv) &&
			isset($this->asAccessIds[STLIST]["cluster"])	)
		{// else if no cluster for given action set
		 // get the clusters for STLIST
			$aRv= $this->asAccessIds[STLIST]["cluster"];
		}
		if( STUserSession::sessionGenerated()
		    and
			count($this->sAcessClusterColumn) )
		{
		    $session= &STUserSession::instance();
			$created= $session->getDynamicClusters($this);

			//echo "achtion:$action<br />";
			$nAction= $action;
			if($action==STLIST)
				$nAction= STACCESS;
				if(count($created[$nAction]))
				{
				    $clusters= "";
				    foreach($created[$nAction] as $cluster)
					{
						if($action==STINSERT)
						{// if the search is for action STINSERT
						 // create from the dynamic cluster the parent.
						 // user must have by STINSERT always the admin access from before
							if(preg_match("/^(.*)_[^_]+$/", $cluster, $preg))
								$cluster= $preg[1];
						}
						$clusters.= $cluster.",";
					}
					$clusters= substr($clusters, 0, strlen($clusters)-1);
					$aRv= array_merge($aRv, $clusters);
				}
				if( (    $action==STINSERT
				         OR
						 $action==STUPDATE
						 OR
						 $action==STDELETE  )
				    and
						count($created[STADMIN])    )
				{
				    if($clusters)
						    $clusters.= ",";
				    foreach($created[STADMIN] as $cluster)
					{
						if($action==STINSERT)
						{// if the search is for action STINSERT
						 // create from the dynamic cluster the parent.
						 // user must have by STINSERT always the admin access from before
							if(preg_match("/^(.*)_[^_]+$/", $cluster, $preg))
								$cluster= $preg[1];
						}
						$clusters.= $cluster.",";
					}
					$clusters= substr($clusters, 0, strlen($clusters)-1);
					$aRv= array_merge($aRv, $clusters);
				}
		}
		return $aRv;
	}
	function getAccessInfoString($action)
	{
		$toAccessInfoString= null;
		if(isset($this->asAccessIds[$action]["accessString"]))
			$toAccessInfoString= $this->asAccessIds[$action]["accessString"];
		if(	!isset($toAccessInfoString) &&
			isset($this->asAccessIds[STLIST]["accessString"])	)
		{// else if no cluster for given action set
		 // set the STLIST clusters
			$toAccessInfoString= $this->asAccessIds[STLIST]["accessString"];
		}
		if(!isset($toAccessInfoString))
		{
			$actionString= "";
			if($action==STINSERT)
				$actionString= "INSERT";
			elseif($action==STUPDATE)
				$actionString= "UPDATE";
			elseif($action==STDELETE)
				$actionString= "DELETE";
			if($action==STLIST)
				$toAccessInfoString= "access table ".$this->getName();
			else
				$toAccessInfoString= $actionString." in table ".$this->getName();
		}
		return $toAccessInfoString;
	}
	function getAccessCustomID($action)
	{
		$customID= null;
		if(isset($this->asAccessIds[$action]["customID"]))
			$customID= $this->asAccessIds[$action]["customID"];
		if(	!isset($customID) &&
			isset($this->asAccessIds[STLIST]["customID"])	)
		{// else if no cluster for given action set
		 // set the STLIST clusters
			$customID= $this->asAccessIds[STLIST]["customID"];
		}
		return $customID;
	}
	function limitByOwn($bLimit)
	{
		$this->bLimitOwn= $bLimit;
	}
	function deleteLimitation()
	{
		$this->sDeleteLimitation= "true";
	}
	function noLimitationDelete()
	{
		$this->sDeleteLimitation= "false";
	}
	function deleteLimitationByOlder()
	{
		$this->sDeleteLimitation= "older";
	}
	function withSearchBox(&$searchBox)
	{
		Tag::paramCheck($searchBox, 1, "STSearchBox");
		$this->oSearchBox= &$searchBox;
	}
	// wenn bAlwaysIndex ist null wird der Index
	// bei keinem Eintrag in der Tabelle nicht angezeigt
	function showAlwaysIndex($bShow)
	{
		$this->bAlwaysIndex= $bShow;
	}
	function needAlwaysIndex()
	{
		return $this->bAlwaysIndex;
	}
	function setFirstRowSelect($firstRow)
	{
		$this->nFirstRowSelect= $firstRow;
	}
	function clearFirstRowSelect()
	{
		$this->nFirstRowSelect= 0;
	}
	function getFirstRowSelect()
	{
		return $this->nFirstRowSelect;
	}
	function setMaxRowSelect($count)
	{
		$this->dateIndex= array();
		$this->nMaxRowSelect= $count;
	}
	function limit($start, $limit= null)
	{
		if(!$limit)
		{
			$limit= $start;
			$start= 0;
		}
		$this->limitRows= array("start"=>$start, "limit"=>$limit);
	}
	// deprecated new is clearIndexSelect
	function clearMaxRowSelect()
	{
		Tag::deprecated("STAliasTable::clearIndexSelect()", "STAliasTable::clearMaxRowSelect()");
		$this->nMaxRowSelect= null;
	}
	function getMaxRowSelect()
	{
		return $this->nMaxRowSelect;
	}
	function setDateIndex($fromDateColumn, $toDateColumn= null, $type= null, $showEmptyDate= false)
	{
		$this->nMaxRowSelect= null;
		$this->dateIndex= array();
		$this->dateIndex["from"]= $fromDateColumn;
		if($toDateColumn)
			$this->dateIndex["to"]= $toDateColumn;
		if($type===null)
		{
			$this->dateIndex["type"]= "unknown";
			foreach($this->columns as $column)
			{
				if($column["name"]==$fromDateColumn)
				{
					if(preg_match("/date/i", $column["type"]))
						$type= STMONTH;
					else
						$type= STDAY;
					break;
				}
			}
		}
		$this->dateIndex["type"]= $type;
		$this->dateIndex["empty"]= $showEmptyDate;
	}
	function clearIndexSelect()
	{
		$this->nMaxRowSelect= null;
		$this->dateIndex= array();
	}
	function listLayout($arrangement)
	{
		$this->listArrangement= $arrangement;
	}
		function distinct($bDistinct= true)
		{
			$this->bDistinct= $bDistinct;
		}
		function isDistinct()
		{
			return $this->bDistinct;
		}
		function setIdentifier($identifier)
		{
			Tag::deprecated("STAliasTable::setDisplayName('$identifier')", "STAliasTable::setIdentifier('$identifier')");
			$this->setDisplayName($identifier);
		}
		function setDisplayName($string)
		{
			$this->identifier= $string;
		}
		function getIdentifier()
		{
			Tag::deprecated("STAliasTable::getDisplayName()", "STAliasTable::getIdentifier()");
			return $this->getDisplayName();
		}
		function getDisplayName()
		{
			$identifier= $this->identifier;
			if($identifier===null)
				$identifier= $this->Name;
			return $identifier;
		}
		function getWhereValue($columnName, $table= null, $bFirst= true)
		{
			if(!$table)
				$table= $this->Name;
			//echo $this->Name."::getWhereValue($column, $table)<br />";

			$aRv= array();
			if($this->oWhere)
				$whereResult= $this->oWhere->getSettingValue($columnName, $table);
			if(count($whereResult))
			{
				foreach($whereResult as $content)
				{
					if($content["type"]==="value")
						$aRv[]= $content;
				}
			}else
			{
				if($bFirst)
				{
					$params= new STQueryString();
					$params= $params->getColumns();
					$Rv= $params[$table][$columnName];
				}
				if(!$Rv)
				{
					//st_print_r($this->aFks,3);
					//st_print_r($this->show,5);
					foreach($this->aFks as $content)
					{
						foreach($content as $column)
						{
							// is the column selected?
							// ask the next higher table
							if($bFirst)
							{
    							foreach($this->show as $showen)
    							{
    								if($column["own"]===$showen["column"])
    								{
    									$oTable= &$this->getTable($column["table"]->Name);
    									$Rv= $oTable->getWhereValue($columnName, $table, false);
                              			if(count($Rv))
                              			{
                              				foreach($Rv as $content)
                              				{
                              					if($content["type"]==="value")
                              						$aRv[]= $content;
                              				}
                              			}
    								}
    							}
							}else
							{
								if(Tag::isDebug())
								{
									echo "to do: search where clausel in getWhereValue for identif columns<br />";
									echo __file__.__line__."<br />";
								}

								/*foreach($this->identification as $column)
								{
									if($column["column"]==$columnName)
								}*/
							}
						}
					}
				}else
					$aRv[]= array(	"value"		=> $Rv,
									"operator"	=> "=",
									"type"		=> "value"		);
			}
			return $aRv;

		}
		function noNnTable()
		{
			$this->bIsNnTable= false;
		}
		function nnTable($checkBoxColumnName, $position= 0)
		{
			Tag::paramCheck($checkBoxColumnName, 1, "string");
			Tag::paramCheck($position, 2, "int");

			Tag::alert(!$this->sPKColumn, "STAliasTable::nnTable()", "primary key for function ::nnTable() must be set in table".$this->Name);
			$this->select($this->sPKColumn, $checkBoxColumnName);
			$this->checkBox($checkBoxColumnName, $submitButton, $formName, $action);
			$this->bIsNnTable= true;
			/*foreach($this->show as $columns)
			{
				if($columns["alias"]===$checkBoxColumnName)
				{
					$pos= $columns;
					break;
				}
			}
			$count= count($this->show);
			$insert= false;
			$show= array();
			reset($this->show);
			st_print_r($this->show,2);
			for($n= 0; $n<$count; $n++)
			{
				if($n===$position)
				{
					$insert= true;
					$show[]= $pos;
				}else
				{
					$current= current($this->show);
					if($current["alias"]===$checkBoxColumnName)
						$current= next($this->show);
					$show[]= $current;
					next($this->show);
				}
			}
			if(!$insert)
				$show[]= $pos;
			$this->show= $show;
			st_print_r($this->show,2);
			if(Tag::isDebug())
			{
    			$fk= &$this->getForeignKeys();
    			$selected= array();
    			foreach($fk as $table=>$content)
    			{
    				foreach($content as $key=>$column)
    				{
    					if($this->isSelected($column["own"]))
    						$selected[$column["own"]]= "selected";
    				}
    			}
    			Tag::alert(count($selected)<1, "STAliasTable::nnTable()", "before use function nnTable, select leastwise an column with foreign key");
			}*/
		}
		function isNnTable()
		{
			return $this->bIsNnTable;
		}
		function foreignKey($ownColumn, $toTable, $otherColumn= null, $where= null)
		{
			STCheck::param($ownColumn, 0, "string");
			STCheck::param($toTable, 1, "STAliasTable", "string");
			STCheck::param($otherColumn, 2, "string", "empty(string)", "null");
			STCheck::param($where, 3, "string", "empty(String)", "STDbWhere", "null");
						
			$this->fk($ownColumn, $toTable, $otherColumn, null, $where);
		}
		function foreignKeyObj($ownColumn, &$toTable, $otherColumn= null, $where= null)
		{
			Tag::paramCheck($ownColumn, 1, "string");
			Tag::paramCheck($toTable, 2, "STAliasTable");
			Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");
			Tag::paramCheck($where, 4, "string", "empty(String)", "STDbWhere", "null");

			$this->fk($ownColumn, $toTable, $otherColumn, null, $where);
		}
		function innerJoin($ownColumn, &$toTable, $otherColumn= null)
		{
			Tag::paramCheck($ownColumn, 1, "string");
			Tag::paramCheck($toTable, 2, "STAliasTable", "string");
			Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");

			$this->fk($ownColumn, $toTable, $otherColumn, "inner", null);
		}
		function leftJoin($ownColumn, $toTable, $otherColumn= null)
		{
			Tag::paramCheck($ownColumn, 1, "string");
			Tag::paramCheck($toTable, 2, "STAliasTable", "string");
			Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");

			$this->fk($ownColumn, $toTable, $otherColumn, "left", null);
		}
		function rightJoin($ownColumn, $toTable, $otherColumn= null)
		{
			Tag::paramCheck($ownColumn, 1, "string");
			Tag::paramCheck($toTable, 2, "STAliasTable", "string");
			Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");

			$this->fk($ownColumn, $toTable, $otherColumn, "right", null);
		}
    protected function fk($ownColumn, &$toTable, $otherColumn= null, $join= null, $where= null)
    {// echo "function fk($ownColumn, &$toTable, $otherColumn, $join, $where)<br />";
		Tag::paramCheck($ownColumn, 1, "string");
		Tag::paramCheck($toTable, 2, "STAliasTable", "string");
		Tag::paramCheck($otherColumn, 3, "string", "empty(string)", "null");
		Tag::paramCheck($join, 4, "check", $join===null || $join==="inner" || $join==="left" || $join==="right",
											"null", "inner", "left", "right");
		Tag::paramCheck($where, 5, "string", "empty(String)", "STDbWhere", "null");
		
		if(typeof($toTable, "STAliasTable"))
			$toTableName= $toTable->getName();
		else
		{
			$toTableName= $toTable;
			$toTable= null;//$this->container->getTable($toTableName);
		}
		// alex 26/04/2005:	where und otherColumn tauschen wenn n???tig
		if(	typeof($otherColumn, "stdbwhere")
			or
			preg_match("/^.+[!=<>].+$/", $otherColumn)	)
		{
			$buffer= $where;
			$where= $otherColumn;
			$otherColumn= &$buffer;
		}// end of tausch
		STCheck::echoDebug("db.table.fk", "create FK from ".$this->getName().".$ownColumn to $toTableName.$otherColumn inside STAliasTable::ID'".$this->ID."'");
		
			// alex 26/04/2005: where-clausel einfuegen
			if($where)
				$toTable->where($where);
			if($otherColumn==null)
			{
				$otherColumn= $toTable->sPKColumn;
				if(!$otherColumn)
				{
					echo "###Error: in table $toTableName is no primary key set<br />";
					echo "          pleas fill in the 3 parameter (\$otherColumn) in method foreignKey()";
					showErrorTrace();
					exit;
				}
			}
			
			if($join===null)
			{
				$bInTable= false;
				$beginning_space= STCheck::echoDebug("db.table.fk", "test <b>$ownColumn:</b> for table <b>".$this->Name."</b> in object <b>".get_class($this).":</b>");
				foreach($this->columns as $field)
				{
					if(Tag::isDebug("db.table.fk"))
					{
						st_print_r($field, 1, $beginning_space);
					}
					if($field["name"]==$ownColumn)
					{//echo "fields: ";print_r($field);echo "<br />";
						$bInTable= true;
						if(preg_match("/not_null/i", $field["flags"]))
							$join= "inner";
						else
							$join= "outer";
						break;
					}
				}
				if(!$bInTable)
					echo "<b>WARNING</b> column $ownColumn is not in Table ".$this->Name."<br />";
			}

		$bSet= false;
		if(	isset($this->aFks[$toTableName]) &&
			is_array($this->aFks[$toTableName])	)
		{
			foreach($this->aFks[$toTableName] as $key=>$content)
			{
				if($content["own"]===$ownColumn)
				{
					$this->aFks[$toTableName][$key]["other"]= $otherColumn;
					$this->aFks[$toTableName][$key]["join"]= $join;
					$bSet= true;
				}
			}
		}
		if(!$bSet)
		{
	     	$this->aFks[$toTableName][]= array("own"=>$ownColumn, "other"=>$otherColumn, "join"=>$join, "table"=>&$toTable);
	     	$toTable->setBackJoin($this->Name);
		}
		if(STCheck::isDebug("db.table.fk"))
		{
		    $beginning_space= STCheck::echoDebug("db.table.fk", "new defined foreign keys on table <b>".$this->Name.":</b>");
		    st_print_r($this->aFks, 3, $beginning_space);
		}

    }
	function setBackJoin($tableName)
	{
		Tag::paramCheck($tableName, 1, "string");

		$exists= array_value_exists($tableName, $this->aBackJoin);
		if($exists !== false)
			return;
		$this->aBackJoin[]= $tableName;
	}
	function createAliases(&$aliasTables)
	{
		return $this->createAliasesA($aliasTables, $this);
	}
	function clearSqlAliases()
	{
		STCheck::echoDebug("db.statements.aliases", "clear sql aliases for table ".$this->Name);
		$this->aAliases= array();
	}
	function createAliasesA(&$aliasTables, &$oMainTable)
	{
		if($oMainTable->Name===$this->Name)
		{
			$bFromIdentifications= false;
			if(count($this->aAliases))
			{
				STCheck::echoDebug("db.statements.aliases", "take sql aliases from older createAliases search");
				$aliasTables= $this->aAliases;
				return false;
			}
			if(STCheck::isDebug())
			{
				$containerObj= $this->container;
				if(!isset($containerObj))
					$containerObj= STBaseContainer::getContainer();
				STCheck::echoDebug("db.statements.aliases", " ");
				STCheck::echoDebug("db.statements.aliases", "create new alias content for needed tables in table <b>".
												$this->getName()."</b> from container <b>".
												$containerObj->getName()."</b>");
			}
			$aliasTables= array();
			$aliasTables[$this->Name]= "t1";
		}else
		{
			$bFromIdentifications= true;
			if(!isset($aliasTables[$this->Name]))
			{
				$aliasTables[$this->Name]= "t".(count($aliasTables)+1);
			}
			STCheck::write($aliasTables);
		}


		//$container= &$this->getContainer();
		//$sMainTableName= $oTable->getName();
		$count= 2;
		if($bFromIdentifications)
			$showList= $this->getIdentifColumns();
		else
		    $showList= $this->getSelectedColumns();
		if(Tag::isDebug("db.statements.aliases"))
		{
			Tag::echoDebug("db.statements.aliases", "need columns from table ".$this->Name." (->getIdentifColumns) where container is ".$this->container->getName());
			st_print_r($showList, 2, 1);
			echo "<br />";
		}
		foreach($showList as $column)
		{//z???hle wieviel Tabellen ben???tigt werden
		    if(STCheck::isDebug("db.statements.aliases"))
		    {
    		    $dbgstr= "need column ".$column["column"];
    		    if($oMainTable->Name == $column['table'])
    		        $dbgstr.= " inside own table";
    		    else
    		        $dbgstr.= " which has an foreign key to '".$column['table']."'";
    			STCheck::echoDebug("db.statements.aliases", $dbgstr);
		    }
			//$table= $oTable->getFkTableName($column["column"]);
			//echo "foreignKey Table is $table<br />";
			//if(!$table)
			$table= $column["table"];
			if(!isset($aliasTables[$table]))
			{
				$alias= "t".(count($aliasTables)+1);
				Tag::echoDebug("db.statements.aliases", "this column is from table ".$table.": set new alias '".$alias."'");
				$aliasTables[$table]= $alias;
				$oTable= &$this->getTable($table);
				$this->db->searchAliasesInWhere($oTable, $aliasTables);
				unset($oTable);
			}
			$otherTableName= $this->getFkTableName($column["column"]);
			if($otherTableName)
			{
				$otherTable= &$oMainTable->getTable($otherTableName);
				$fktableName= $otherTable->getName();
				Tag::echoDebug("db.statements.aliases", "column ".$column["column"]." in container ".$otherTable->container->getName().", have an foreign key to table $fktableName");
				if(!isset($aliasTables[$fktableName]))
				{
  					if( !isset($aliasTables["db.".$otherTable->getName()])
  						and
  						$otherTable->db->getDatabaseName()!=$this->db->getDatabaseName()	)
  					{
  						$aliasTables["db.".$otherTable->getName()]= $otherTable->db->getDatabaseName();
  					}
  					STCheck::echoDebug("db.statements.aliases", "create new alias for table '$fktableName'");
					$otherTable->createAliasesA($aliasTables, $oMainTable);
					Tag::echoDebug("db.statements.aliases", "be back in table ".$this->Name);
				}
				unset($otherTable);
			}
		}
		foreach($this->aBackJoin as $sBackTableName)
		{
			$BackTable= &$oMainTable->getTable($sBackTableName);
			Tag::echoDebug("db.statements.aliases", "need backward-tablealias from table $tableName, table $sBackTableName from container ".$BackTable->container->getName());
			$BackTable->createAliasesA($aliasTables, $oMainTable);
			unset($BackTable);
		}
		if(isset($this->db))
			$this->db->searchAliasesInWhere($this, $aliasTables);
		if($oMainTable->Name===$this->Name)
			$this->aAliases= $aliasTables;
		return true;
	}
	function isOrdered()
	{
		$aliases= array();
		$this->createAliases($aliases, $this);
		$order= $this->getOrderStatement($aliases, null, true);
		if($order)
			return true;
		return false;
	}
	/*public*/function getOrderStatement(&$aTableAlias, $tableName= null, $bIsOrdered= false)
	{
		$statement= "";
		if(	$tableName===null
			or
			$this->Name===$tableName	)
		{ 
			
			$oTable= &$this;
			$aNeededColumns= $oTable->getSelectedColumns();
			//if tableName is null
			$tableName= $this->Name;
		}else
		{
			$oTable= &$this->getTable($tableName);
			$aNeededColumns= $oTable->getIdentifColumns();
		}
		$alias= "";
		if(	count($aTableAlias)>1)
		{
			$alias= $aTableAlias[$tableName];
			$alias.= ".";
		}elseif(!$oTable->asOrder)
		{
			return "";
		}
		foreach($oTable->asOrder as $column=>$sort)
		{
			$statement.= $alias.$column." ".$sort;
			$statement.= ",";
			if($bIsOrdered)
			{
				return $statement;
			}
		}
		foreach($aNeededColumns as $columnContent)
		{
			$fkTableName= $oTable->getFkTableName($columnContent["column"]);
			if(	isset($fkTableName) &&
				$this->Name != $fkTableName	)
			{
				//echo __FILE__.__LINE__."<br>";
				//echo "getOrderStatement($aTableAlias, $fkTableName, $bIsOrdered)<br>";
				$order= $this->getOrderStatement($aTableAlias, $fkTableName, $bIsOrdered);
				if($order)
				{
					if($bIsOrdered)
						return $order;
					$statement.= $order.",";
				}
			}
		}
		$statement= substr($statement, 0, strlen($statement)-1);
		$tableName= $this->getName();
		$oGet= new STQueryString();
		$aGet= $oGet->getArrayVars();
		if(isset($aGet["stget"]["sort"][$tableName]))
		{
			$aGet= $aGet["stget"]["sort"][$tableName];
			$statement= "";
			foreach($aGet as $column)
			{
				//preg_match("/^([^_]+)_(ASC|DESC)$/i", $column, $inherit);
			    preg_match("/^(.+)_(ASC|DESC)$/i", $column, $inherit);
				$field= $this->searchByAlias($inherit[1]);
				$statement.= $field["column"]." ".$inherit[2].",";
			}
			$statement= substr($statement, 0, strlen($statement)-1);
		}
		return $statement;
	}
	function clearCreatedAliases()
	{
		$this->aAliases= array();
	}
		function addCount($column= "*", $alias= null)
		{
			$this->count($column, $alias, true);
		}
		function count($column= "*", $alias= null, $add= false)
		{
			Tag::paramCheck($column, 1, "string", "STAliasTable");
			Tag::paramCheck($alias, 2, "string", "null");

			if(!isset($this->bOrder))
				$this->bOrder= false;
			$this->bHasGroupColumns= true;
			if(typeof($column, "STAliasTable"))
			{
				$aliasTables= array();
				$columns= $column->getSelectedColumns();
				if($column->isDistinct())
				{
					$columnString= "distinct ";
					$this->distinct(false);
					foreach($columns as $field)
						$columnString.= $field["column"].",";
					foreach($this->showTypes as $alias=>$content)
					{
						if(isset($content["get"]))
						{
							$field= $this->getAliasOrColumn($alias);
							$columnString.= $field["column"].",";
						}
					}
					$column= substr($columnString, 0, strlen($columnString)-1);
				}else
					$column= $columns[0]["column"];

				/*}else
				{
					$columnString= $column->show[0]["column"];
					if(!columnString)
						$columnString= $column->columns[0]["name"];
					$column= $columnString;
				}*/
			}
			if($add)
				STAliasTable::addSelect("count(".$column.")", $alias);
			else
				STAliasTable::select("count(".$column.")", $alias);
		}
		function column($name, $type, $len)
		{
		 	Tag::paramCheck($name, 1, "string");
			Tag::paramCheck($type, 2, "string");
			Tag::paramCheck($len, 3, "int");

			$columnKey= $this->dbColumn($name, $type, $len);
			$this->columns[$columnKey]["db"]= "alias";
		}
		function dbColumn($name, $type, $len)
		{
		 	Tag::paramCheck($name, 1, "string");
			 Tag::paramCheck($type, 2, "string");
			 Tag::paramCheck($len, 3, "int");

			$flags= "";
			if($type=="text")
			{
				$type= "blob";
				$flags= "blob";
			}
			$columnKey= $this->getColumnKey($name);
			if($columnKey!==null)
			{
				$this->columns[$columnKey]["type"]= $type;
				$this->columns[$columnKey]["len"]= $len;
			}else
			{
				$this->columns[]= array("name"=>$name, "flags"=>$flags, "type" =>$type, "len"=>$len);
				$columnKey= count($this->columns)-1;
			}
			return $columnKey;
		}
		function columnFlags($columnName, $flags)
		{
		 	$flags= strtolower($flags);
		 	$flags= pregi_replace("/not null/", "not_null", $flags);
		 	$flags= pregi_replace("/primary key/", "primary_key", $flags);
		 	$flags= pregi_replace("/multiple key/", "multiple_key", $flags);
		 	$aFlag= preg_split("/[ ,]/", $flags);
			$columnKey= $this->getColumnKey($columnName);

			$flags= "";
			foreach($aFlag as $flag)
			{
				if(!preg_match("/".$flag."/i", $this->columns[$columnKey]["flags"]))
					$flags.= " ".$flag;
			}
			$this->columns[$columnKey]["flags"]= substr($flags, 1);

		}
		function removeFlag($columnName, $flag)
		{
		 	$flag= strtolower($flag);
		 	$flag= preg_replace("/not null/", "not_null", $flag);
		 	$flag= preg_replace("/primary key/", "primary_key", $flag);
		 	$flag= preg_replace("/multiple key/", "multiple_key", $flag);
		 	$columnKey= $this->getColumnKey($columnName);
		 	$pos= strpos($this->columns[$columnKey]["flags"], $flag);
		 	//echo __file__.__line__."<br>";
		 	//echo "found flag on position $pos<br>";
		 	//echo "flags from column $columnName:<br>";
		 	//echo "before:".$this->columns[$columnKey]["flags"]."<br>";
		 	if($pos < strlen($this->columns[$columnKey]["flags"]))
		 	{
		 		$this->columns[$columnKey]["flags"]= substr($this->columns[$columnKey]["flags"], 0, $pos).
		 									substr($this->columns[$columnKey]["flags"], $pos + strlen($flag));
		 	}
		 	//echo "behind:".$this->columns[$columnKey]["flags"]."<br>";
		}
		function getColumnKey($columnName)
		{
			foreach($this->columns as $key=>$content)
			{
				if($content["name"]==$columnName)
					return $key;
			}
			return null;
		}
		function getSelectedColumnKey($columnName)
		{
			foreach($this->show as $key=>$content)
			{
				if($content["column"]==$columnName)
					return $key;
			}
			return null;
		}
		function getSelectedAliasKey($columnName)
		{
			foreach($this->show as $key=>$content)
			{
				if($content["alias"]==$columnName)
					return $key;
			}
			return null;
		}
		function getSelectedKey($column)
		{
			$key= $this->getSelectedAliasKey($column);
			if($key===null)
				$key= $this->getSelectedColumnKey($column);
			return $key;
		}
		function hasTinyMce()
		{
			if(count($this->oTinyMCE))
				return true;
			return false;
		}
		function &getTinyMCE($column= null)
		{
			if($column===null)
				return reset($this->oTinyMCE);
			return $this->oTinyMCE[$column];
		}
		function tinyMCECount()
		{
			return count($this->oTinyMCE);
		}
		function tinyMCEColumns()
		{
			$aRv= array();
			foreach($this->oTinyMCE as $column=>$mce)
			{
				$aRv[]= $column;
			}
			return $aRv;
		}
		/**
		 * beginning to put all entrys, for an update or insert box,
		 * into an table which have without this command two columns,
		 * to make a better design if the developer want to do more selects in one Row
		 * <code>select($dbcolumn, $displaycolumn, >break line< false)</code>
		 *
		 * @param fieldset if param is true an border is draw arround the inner table, if the param has an string the border is named, otherwise by default or false, no border showen
		 */
		function selectIBoxBegin($fieldset= false)
		{
			STCheck::param($fieldset, 0, "bool", "string");

			$this->aInnerTables[$this->nInnerTable]["fieldset"]= $fieldset;
			$this->aInnerTables[$this->nInnerTable]["begin"]= "NULL";
		}
		/**
		 * end of put entrys in an inner Table.<br />
		 * see selectIBoxBegin for beginning and more details
		 */
		function selectIBoxEnd()
		{
			$this->nInnerTable+= 1;
		}
		function addSelect($column, $alias= null, $fillCallback= null, $nextLine= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($alias, 2, "string", "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($fillCallback, 3, "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($nextLine, 4, "bool", "null");
			$nParams= func_num_args();
			Tag::lastParam(4, $nParams);

			STAliasTable::select($column, $alias, $fillCallback, $nextLine, true);
		}
		// alex 12/09/2005:	Alias kann jetzt auch eine Funktion
		//					zum f???llen einer nicht vorhandenen Spalte sein
		// alex 21/09/2005:	ACHTUNG alias darf keine Funktion sein (auch nicht PHP-function)
		// alex 06/08/2006: second column alias or third column fillCallback can also be an object from TinyMCE
		function select($column, $alias= null, $fillCallback= null, $nextLine= null, $add= false)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($alias, 2, "string", "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($fillCallback, 3, "function", "TinyMCE", "bool", "null");
			Tag::paramCheck($nextLine, 4, "bool", "null");
			Tag::paramCheck($add, 5, "bool");
			$nParams= func_num_args();
			Tag::lastParam(5, $nParams);
			
			if(STCheck::isDebug())
			{
				STCheck::alert(!$this->columnExist($column), "STAliasTable::selectA()",
											"column $column not exist in table ".$this->Name.
											"(".$this->getDisplayName().")");
			}
			if(is_bool($alias))
			{
				$nextLine= $alias;
				$alias= null;
				
			}elseif(is_bool($fillCallback))
			{
				$nextLine= $fillCallback;
				$fillCallback= null;
				
			}elseif($nextLine===null)
				$nextLine= true;
			if(typeof($alias, "TinyMCE"))
			{
				$alias->elements($column);
				$this->oTinyMCE[$column]= $alias;
				$alias= null;
				
			}elseif(typeof($fillCallback, "TinyMCE"))
			{
				$fillCallback->elements($column);
				$this->oTinyMCE[$alias]= $fillCallback;
				$fillCallback= null;
			}
			if(	$alias != NULL &&
			    function_exists($alias) &&
				$fillCallback===null	   )
			{// alias ist ein Funktionsname zum f???llen
			 // einer nicht vorhandenen Spalte
			 	$fillCallback= $alias;
				$alias= $column;
			}elseif(!$alias)
				$alias= $column;
				
			$this->selectA($this->Name, $column, $alias, $nextLine, $add);
			if($fillCallback)
			{
				$this->callback($alias, $fillCallback);
				$this->insertCallback($fillCallback, $alias);
				$this->updateCallback($fillCallback, $alias);
				$this->deleteCallback($fillCallback, $alias);
			}
		}
		/*protected*/function selectA($table, $column, $alias, $nextLine, $add)
		{
			if(STCheck::isDebug())
			{
				Tag::paramCheck($table, 1, "string");
				Tag::paramCheck($column, 2, "string");
				Tag::paramCheck($alias, 3, "string");
				Tag::paramCheck($nextLine, 4, "bool");
				Tag::paramCheck($add, 5, "bool");
			}
			if(!preg_match("/^count\(.*\)$/i", $column))
				$this->bOrder= true;
			$desc= STDbTableDescriptions::instance($this->db->getName());
			$column= $desc->getColumnName($table, $column);// if table is original function must not search
			$table= $desc->getTableName($table);
			if(STCheck::isDebug())
			{
				if($table===$this->Name)
					$oTable= &$this;
				else
					$oTable= &$this->db->getTable($table);
				STCheck::alert(!$oTable->columnExist($column), "STAliasTable::selectA()",
											"column $column not exist in table ".$table.
											"(".$oTable->getDisplayName().")");
			}

			if(	!$add
				and
				!isset($this->abNewChoice["select"]))
			{
				$this->show= array();
				$this->abNewChoice["select"]= true;
			}
			foreach($this->show as $content)
			{
				if(	$content["column"]==$column
					and
					$content["alias"]== $alias
					and
					$content["table"]===$table	)
				{
					Tag::warning(1, "STAliasTable::select()", "column $column with alias $alias in table $table, selected in two times", 1);
					return;
				}
			}
			if($alias===null)
				$alias= $column;
			// if is set inner Table
			// with actual number
			// note begin- and end-column
			if(isset($this->aInnerTables[$this->nInnerTable]))
			{
				if($this->aInnerTables[$this->nInnerTable]["begin"] == "NULL")
					$this->aInnerTables[$this->nInnerTable]["begin"]= $alias;
				$this->aInnerTables[$this->nInnerTable]["end"]= $alias;
					
			}

			$aColumn= array();
			$aColumn["table"]= $table;
			$aColumn["column"]= $column;
			$aColumn["alias"]= $alias;
			$aColumn["nextLine"]= $nextLine;
			$this->show[]= $aColumn;
			// if the column is set as getColumn
			// delete it, because it should not be set both
			if(	isset($this->showTypes[$column]) &&
				isset($this->showTypes[$column]["get"]) &&
				$this->showTypes[$column]["get"]==="get"	)
			{
				if(count($this->showTypes[$column])===1)
					unset($this->showTypes[$column]);
				else
					unset($this->showTypes[$column]["get"]);
				return;
			}
		}
		/**
		 * add content of string or HTML-Tags
		 * after the field
		 * 
		 * @param string or HTML-Tags $content which should be added
		 * @param predefined Action $action table action on which should be added
		 */
		function addContent($content, $action)
		{
			STCheck::param($content, 0, "string", "tag");
			
			$count= sizeof($this->show);
			if($count == 0)
				$this->show[]= array();
			else
				$count-= 1;
			if(!isset($this->show[$count]["addContent"][$action]))
				$this->show[$count]["addContent"][$action]= new SpanTag();
			$this->show[$count]["addContent"][$action]->add($content);
			
		}
		/**
		 * add content of string or HTML-Tags
		 * between the last table row and the next
		 * 
		 * @param string or HTML-Tags $content which should be added
		 * @param predefined Action $action table action on which should be added
		 */
		function addBehind($content, $action)
		{
			STCheck::param($content, 0, "string", "tag");
			
			$count= sizeof($this->show);
			if($count == 0)
				$this->show[]= array();
			else
				$count-= 1;
			if(!isset($this->show[$count]["addBehind"][$action]))
				$this->show[$count]["addBehind"][$action]= new SpanTag();
			$this->show[$count]["addBehind"][$action]->add($content);
			
		}
		function isSelected($column)
		{
			foreach($this->show as $content)
			{
				if($content["column"]==$column)
					return true;
			}
			return false;
		}
		function group($name, $fieldset, $aliasColumn /*...*/)
		{
			Tag::paramCheck($name, 1, "string", "int");
			Tag::paramCheck($fieldset, 2, "bool", "string");

			$nArgs= func_num_args();
			$aArgs= func_get_args();
			$this->aGroups[$name]["fieldset"]= $fieldset;
			for($n= 2; $n<$nArgs; $n++)
			{
				$field= $this->findAliasOrColumn($aArgs[$n]);
				// groups selected by alias-columns
				// because in more groups can be the same column
				$this->aGroups[$name]["columns"][]= $field["alias"];
			}
			st_print_r($this->aGroups,3);
		}
		function columnExist($column)
		{
			if(preg_match("/^['\"].*['\"]$/", $column))
			// column is maybe only an string content
				return true;
			if(preg_match("/(count|min|max)\((.*)\)/i", $column, $preg))
			{
				if($preg[1]=="count" && trim($preg[2])=="*")
					return true;
				if($preg[1]!="count")
				{
					$split= array();
					$split[]= $preg[2];
				}else
					$split= preg_split("/[ ,]/", $preg[2]);
				foreach($split as $col)
				{
					if($col!="distinct")
					{
						if(preg_match("/^([^.]+)\.([^.]+)$/", $col, $preg))
						{
							$table= &$this->getTable($preg[1]);
							if(!$table)
								return false;
							if(!$table->columnExist($preg[2]))
								return false;
							unset($table);
						}else
						{
							if(!$this->columnExist($col))
								return false;
						}
					}
				}
				return true;
			}
			foreach($this->columns as $tcolumn)
			{
				if($column==$tcolumn["name"])
					return true;
			}
			return false;
		}
		function searchByAlias($aliasName)
		{
			STCheck::param($aliasName, 0, "string");

			foreach($this->show as $field)
			{
				if(	isset($field["alias"]) &&
					$field["alias"] == $aliasName	)
				{
					$aRv= $field;
					$aRv["table"]= $this->Name;
					$aRv["type"]= "alias";
					$aRv["get"]= "select";
					return $aRv;
				}
			}
			foreach($this->identification as $column)
    		{
    			if( isset($column["alias"]) &&
    				$column["alias"] == $aliasName	)
    			{
					$aRv= $column;
					$aRv["table"]= $this->Name;
					$aRv["type"]= "alias";
    				$aRv["get"]= "identif";
					$aRv["alias"]= $column["alias"];
					//st_print_r($aRv);
    				return $aRv;
    			}
    		}
			return null;
		}
		function &isForeignKey($columnName, $bIsColumn= false)
		{
			if(!$bIsColumn)
			{
				$field= $this->findAliasOrColumn($columnName);
				$columnName= $field["column"];
			}
			foreach($this->aFks as $table=>$content)
			{
				foreach($content as $key=>$column)
				{
					if($column["own"]===$columnName)
						return $this->aFks[$table][$key];
				}
			}
			$Rv= null;
			return $Rv;
		}
		function searchByIdentifColumn($columnName)
		{
			foreach($this->identification as $column)
			{
				if($column["column"]==$columnName)
				{
					$aRv= array();
					$aRv["table"]= $this->Name;
					$aRv["column"]= $columnName;
					if(isset($column["alias"]))
						$aRv["alias"]= $column["alias"];
					else
						$aRv["alias"]= $columnName;
					$aRv["type"]= "column";
					$aRv["get"]= "identif";
					return $aRv;
				}
			}
			foreach($this->showTypes as $c=>$columns)
			{
				if($c==$columnName)
				{
					foreach($columns as $type=>$a)
					{
						if($type==="get")
						{
        					$aRv= array();
        					$aRv["table"]= $this->Name;
        					$aRv["column"]= $columnName;
        					$aRv["alias"]= $this->Name."@".$columnName;
        					$aRv["type"]= "column";
        					$aRv["get"]= "get";
        					return $aRv;
						}
					}
				}
			}
			return null;
		}
		function getSelectedFieldArray($bMain= true)
		{
			$fields= array();
			if($bMain)
				$columns= $this->show;
			else
				$columns= $this->identification;

			foreach($columns as $content)
			{
				$otherTable= $this->getFkTable($content["column"]);
				//echo "table:<br />";
				//st_print_r($otherTable);echo "\n<br />";
				if($otherTable->correctTable())
				{
					$otherFields= $otherTable->getSelectedFieldArray(false);
					//$fields= array_merge($otherFields, $fields);
					foreach($otherFields as $newField)
						$fields[]= $newField;
				}else
				{
					if(preg_match("/count/i", $content["column"]))
					{
						$field= array();
						$field["name"]= $content["column"];
						$field["flags"]= "";
						$field["type"]= "int";
						$field["len"]= 11;
					}else
						$field= $this->getColumnContent($content["column"]);
					$field["name"]= $content["alias"];
					$fields[]= $field;
				}
			}
			foreach($this->showTypes as $column=>$content)
			{
				if(isset($content["get"]))
				{
					$field= $this->getColumnContent($column);
					//$field["name"]= $content["alias"];
					$fields[]= $field;
				}
			}
			return $fields;
		}
		function searchByColumn($columnName)
		{
			foreach($this->show as $field)
			{
				if($field["column"]==$columnName)
				{
					$fk= &$this->isForeignKey($columnName, /*bIsColumn*/true);
					if($fk)
					{// if the column have an foreign key to an other table
					 // search for the alias name in the identif-columns from this table
						$otherTable= $this->getFkTable($columnName, /*bIsColumn*/true);

						$other= $otherTable->searchByIdentifColumn($fk["other"]);
						if($other)
						{
							$other["column"]= $columnName;
							return $other;//$aRv["fk"]= $other;
						}
					}
					$aRv= $field;
					$aRv["type"]= "column";
					$aRv["get"]= "select";
					return $aRv;
				}
			}
			foreach($this->showTypes as $c=>$columns)
			{
				if($c==$columnName)
				{
					foreach($columns as $type=>$a)
					{
						if($type==="get")
						{
        					$aRv= array();
        					$aRv["table"]= $this->Name;
        					$aRv["column"]= $columnName;
        					$aRv["alias"]= $this->Name."@".$columnName;
        					$aRv["type"]= "column";
        					$aRv["get"]= "get";
        					return $aRv;
						}
					}
				}
			}
			if(isset($this->columns))
			{
				foreach($this->columns as $field)
				{
					if($field["name"]==$columnName)
					{
						$aRv= array();
						$aRv["table"]= $this->Name;
						$aRv["column"]= $columnName;
						$aRv["alias"]= $columnName;//"unknown";
						$aRv["type"]= "column";
						$aRv["get"]= false;
						// do not ask isIdentifColumn(),
						// because it asks findAliasOrColumn()
						// and this searchByColumn()
						foreach($this->identification as $column)
	        			{
	        				if($column["column"]==$columnName)
	        				{
	        					$aRv["get"]= "identif";
	        					if(isset($column["alias"]))
									$aRv["alias"]= $column["alias"];
	        					else
	        						$aRv["alias"]= $columnName;
	        					break;
	        				}
	        			}
						return $aRv;
					}
				}
			}
			return null;
		}
		function findAliasOrColumn($alias)
		{
			return $this->findColumnAlias($alias, true);
		}
		function findColumnOrAlias($column)
		{
			return $this->findColumnAlias($column, false);
		}
		/*private*/function findColumnAlias($name, $firstAlias= false)
		{
			$field= null;
			if($firstAlias)
				$field= $this->searchByAlias($name);
			if(!$field)
				$field= $this->searchByColumn($name);
			if(!$field && !$firstAlias)
				$field= $this->searchByAlias($name);
			// alex 19/09/2005: keine Warnung! wegen aliasTable
			//Tag::warning(!$field, "findAliasOrColumn()", "column ".$name." is not declared in table ".$this->Name);
			if(!$field)
			{
				STCheck::flog("creating unknown field");
				$field= array();
				$field["table"]= "unknown";
				$field["column"]= $name;
				$field["alias"]= $name;
				$field["type"]= "not found";
			}
			return $field;
		}
		function haveColumn($column, $caseSensitive= true)
		{
			$preg_string= "/$column/";
			if(!$caseSensitive)
				$preg_string.= "i";
			foreach($this->columns as $field)
			{
				if(preg_match($preg_string, $field["name"]))
					return $field["name"];
			}
			return false;
		}
		function onChangeRefresh($column)
		{
			$field= $this->findAliasOrColumn($column);
			$this->aRefreshes[$field["column"]]= "refresh";
		}
		function isSelect($columnName, $alias= null)
		{
			$bRv= false;
			if($alias===null)
			{
				$field= $this->findAliasOrColumn($columnName);
				$columnName= $field["column"];
			}
			foreach($this->show as $column)
			{
				if(	$column["column"]==$columnName
					and
					(	$alias===null
						or
						$column["alias"]==$alias	)	)
				{
					$bRv= true;
					break;
				}
			}
			return $bRv;
		}
		function isIdentifColumn($columnName, $alias= null)
		{
			$bRv= false;
			if($alias===null)
			{
				$field= $this->findAliasOrColumn($columnName);
				$columnName= $field["column"];
			}
			foreach($this->identification as $column)
			{
				if(	$column["column"]==$columnName
					and
					(	$alias===null
						or
						$column["alias"]==$alias	)	)
				{
					$bRv= true;
					break;
				}
			}
			return $bRv;
		}
		function unSelect($columnName, $tableName= "")
		{
			$field= $this->findAliasOrColumn($columnName);
			$columnName= $field["column"];
			foreach($this->show as $key=>$column)
			{
				if(	$column["column"]==$columnName
					and
					(	$column["table"]==$tableName
						or
						!$tableName					)	)
				{
					unset($this->show[$key]);
				}
			}
		}
	function getSelectedColumns()
	{
		if(!$this->bDisplaySelects)
			return array();
		if( isset($this->show) &&
			count($this->show)		)
		{
			return $this->show;
		}
		if(!isset($this->columns))
			return array();// own object is an empty table (STAliasTable)
		foreach($this->columns as $column)
		{
  			$aColumn["table"]= $this->Name;
  			if(isset($column["name"]))
  			{
	  			$aColumn["column"]= $column["name"];
	  			$aColumn["alias"]= $column["name"];
	  			
  			}else if(STCheck::isDebug())
  			{
  				echo "undefined column inside table ".$this->Name."<br />";
  				echo __FILE__." ".__LINE__."<br />";  				
  			}  			
  			$this->show[]= $aColumn;
		}
		return $this->show;
	}
	function displaySelects($bDisplay)
	{
		$this->bDisplaySelects= $bDisplay;
	}
		function clearSelects()
		{
			$this->show= array();
		}
		function clearNoFkSelects()
		{
			foreach($this->show as $key=>$content)
			{
				$tableName= $this->getFkTableName($content["column"]);
				if(!$tableName)
					unset($this->show[$key]);
			}
		}
		function clearRekursiveNoFkSelects()
		{
			foreach($this->show as $key=>$content)
			{
			    if(STCheck::isDebug("table"))
			     STCheck::echoDebug("table", "get FK table for column <b>".$content["column"]."</b>");
				$table= &$this->getFkTable($content["column"], true);
				if(	isset($table) &&
					$table->correctTable()	)
				{
					$table->clearRekursiveNoFkIdentifColumns();
					unset($table);
				}else
					unset($this->show[$key]);
			}
		}
		function noColumnSelects()
		{
			$this->clearSelects();
			$this->clearIdentifColumns();
			$this->bColumnSelects= false;
		}
		function clearNoFkIdentifColumns()
		{
			$bExists= false;
			foreach($this->identification as $key=>$content)
			{
				$tableName= $this->getFkTableName($content["column"]);
				if(!$tableName)
					unset($this->show[$content["column"]]);
			}
			if(!$bExists)
				$this->bDisplayIdentifs= false;
		}
		function clearRekursiveNoFkIdentifColumns()
		{
			$keys= array();
			$bExists= false;
			foreach($this->identification as $key=>$content)
			{
				$table= &$this->getFkTable($content["column"], true);
				if($table->correctTable())
				{
					$table->clearRekursiveNoFkIdentifColumns();
					unset($table);
					$bExists= true;
				}else
				{
					unset($this->identification[$key]);
				}
			}
			/*foreach($keys as $key)
			{

			}*/
			if(!$bExists)
				$this->bDisplayIdentifs= false;
		}
		function clearIdentifColumns()
		{
			$this->identification= array();
		}
		function clearFKs()
		{
			$this->FK= array();
			$this->aFks= array();
			$this->aBackJoin= array();
		}
		function clearAliases()
		{
			$show= array();
			foreach($this->show as $column)
			{
				$new= array();
				$new["table"]= $column["table"];
				$new["column"]= $column["column"];
				$show[]= $new;
			}
			$this->show= $show;
		}
		function identifColumn($column, $alias= null)
		{
			Tag::alert(!$this->columnExist($column), "STAliasTable::identifColumn()", "column $column not exist in table ".$this->Name);

			if(	!isset($this->abNewChoice["identifColumn"]) ||
				!$this->abNewChoice["identifColumn"]			)
			{
				$this->identification= array();
				$this->abNewChoice["identifColumn"]= true;
			}
			$count= count($this->identification);
			$this->identification[$count]= array();
			$this->identification[$count]["column"]= $column;
			if($alias)
				$this->identification[$count]["alias"]= $alias;
			$this->identification[$count]["table"]= $this->getName();
		}
		function getIdentifColumns()
		{
			if(!$this->bDisplayIdentifs)
				return array();
			if(!count($this->identification))
			{
				$ar= array("column"=>$this->getPkColumnName(), "table"=>$this->getName());
				return array($ar);
			}
			return $this->identification;
		}
		function displayIdentifs($bDisplay= true)
		{
			$this->bDisplayIdentifs= $bDisplay;
		}
		function showNameOverList($show)
		{
			$this->bShowName= $show;
		}
		function andWhere($stwhere)
		{
		 	Tag::paramCheck($stwhere, 1, "STDbWhere", "string", "empty(string)", "null");
		 	
			if(!$stwhere)
				return;
			return $this->where($stwhere, "and");
		 	if(is_string($stwhere))
				$stwhere= new STDbWhere($stwhere);
			if(!$stwhere->isModified())
				return;
			if(	$this->oWhere
				and
				$this->oWhere->isModified()	)
			{
				$this->oWhere->andWhere($stwhere);
			}else
				$this->oWhere= $stwhere;
			$this->oWhere->forTable($this->Name);
		}
		function orWhere($stwhere)
		{
		 	Tag::paramCheck($stwhere, 1, "STDbWhere", "string", "empty(string)", "null");
		 	
			if(!$stwhere)
				return null;
			return $this->where($stwhere, "or");
		}
		function where($stwhere, $operator= "")
		{
		 	STCheck::parameter($stwhere, 1, "STDbWhere", "string", "empty(string)", "null");
		 	STCheck::parameter($operator, 2, "check", $operator === "", $operator == "and", $operator == "or");

		 	if(	!isset($stwhere) ||
				$stwhere == null ||
				$stwhere == ""	||
				(	is_object($stwhere) &&
					get_class($stwhere) == "STDbWhere" &&
					!$stwhere->isModified()					)	)
		 	{
		 		return $this->oWhere;
		 	}
		 	if($operator == "")
		 		unset($this->oWhere);
			if(!$stwhere)
				return;
	 		if(	isset($this->oWhere) &&
	 			(	is_string($stwhere) ||
	 				$this->oWhere->isModified()	)	)
	 		{
	 			// 1. parameter is an string or STDbWhere object
 				if(	is_string($stwhere) &&
 					$this->Name != $this->oWhere->forTable()	)
 				{
 					
 					$stwhere= new STDbWhere($stwhere, $this->Name);
 				}
 				if(	is_object($stwhere) &&
 					$stwhere->forTable() == ""	)
 				{
 					$stwhere->forTable($this->Name);
 				}
	 			if($operator == "or")
	 			{
	 				$this->oWhere->orWhere($stwhere);
	 			}else
	 				$this->oWhere->andWhere($stwhere);
	 		}else
	 		{// no where be set
	 			if(is_string($stwhere))
	 			{
	 				$this->oWhere= new STDbWhere($stwhere);
					$this->oWhere->forTable($this->Name);
	 			}else
	 			{
	 				if($stwhere->forTable() == "")
	 					$stwhere->forTable($this->Name);
	 				$this->oWhere= $stwhere;
	 			}
	 		}
	 		
		 	return $this->oWhere;
		}
		function clearWhere()
		{
			$this->oWhere= null;
		}
		function allowQueryLimitation($bModify= true)
		{
			if($bModify)
				$this->bModifyFk= true;
			else
				$this->bModifyFk= false;
		}
		function modify()
		{
			return $this->bModifyFk;
		}
		function getWhere()
		{
			if(!isset($this->oWhere))
				return null;
			return $this->oWhere;
		}
		/*function andWhere(&$stwhere)
		{
			if($this->oWhere)
				$this->oWhere->andWhere($stwhere);
			else
				$this->oWhere= $stwhere;
		}
		function orWhere(&$stwhere)
		{
			if($this->oWhere)
				$this->oWhere->orWhere($stwhere);
			else
				$this->oWhere= $stwhere;
		}*/
		function orderBy($column, $bASC= true)
		{
			Tag::paramCheck($bASC, 2, "bool");

			$field= $this->findAliasOrColumn($column);
			$column= $field["column"];

			if(!isset($this->abNewChoice["order"]))
			{
				$this->asOrder= array();
				$this->abNewChoice["order"]= true;
			}
			if($bASC)
				$sort= "ASC";
			else
				$sort= "DESC";
			$this->asOrder[$column]= $sort;
			//st_print_r($this->asOrder);
		}
		function getName()
		{
			return $this->Name;
		}
		function getContent()
		{
			return $this->columns;
		}
		function getColumnContent($columnName)
		{
			foreach($this->columns as $content)
			{
				if($content["name"]===$columnName)
					return $content;
			}
			return null;
		}
		function getPkColumnName()
		{
			if(	isset($this->sPKColumn) &&
				$this->sPKColumn			)
			{
				return $this->sPKColumn;
			}
			if(isset($this->columns))
			{
				foreach($this->columns as $column)
				{
					if(preg_match("/.*primary_key.*/i", $column["flags"]))
					{
						$this->sPKColumn= $column["name"];
						return $column["name"];
					}
				}
			}
			return false;
		}
		function getErrorText()
		{
			return $this->errorText;
		}
		function isError()
		{
			return $this->error;
		}
		function upload($column, $toPath, $type, $byte= 0, $width= 0, $height= 0)
		{
			$incomming= $toPath;
			if(substr($toPath, 0, 1)=="/")
			{
				$path= $_SERVER["SCRIPT_FILENAME"];
				$path= substr($path, 0, strlen($path)-strlen($_SERVER["SCRIPT_NAME"]));
				$incomming= $path.$toPath;
			}
			Tag::alert(!is_dir($incomming), "STAliasTable::upload", "path ".$toPath." not exist");
			$field= $this->findAliasOrColumn($column);
			$column= $field["alias"];
			$field= array();
			$field["size"]= $byte;
			$field["type"]= $type;
			$field["path"]= $toPath;
			if($width!=0)
				$field["width"]= $width;
			if($height!=0)
				$field["height"]= $height;
			if(!isset($this->showTypes[$column]))
				$this->showTypes[$column]= array();
			$this->showTypes[$column]["upload"]= $field;
		}
		function image($column, $toPath= null, $byte= 0, $width= 0, $height= 0)
		{
			$field= $this->findAliasOrColumn($column);
			$column= $field["alias"];
			if($toPath!==null)
			{
				$incomming= $toPath;
				if(substr($toPath, 0, 1)=="/")
				{
					$path= $_SERVER["SCRIPT_FILENAME"];
					$path= substr($path, 0, strlen($path)-strlen($_SERVER["SCRIPT_NAME"]));
					$incomming= $path.$toPath;
				}
				Tag::alert(!is_dir($incomming), "STAliasTable::image", "path ".$toPath." not exist");
				$this->upload($column, $toPath, "image/gif,image/pjpeg", $byte, $width, $height);
			}
			$this->showTypes[$column]["image"]= $field;
		}
		function noUnlinkData($column)
		{
			$field= $this->findAliasOrColumn($column);
			$column= $field["alias"];
			$this->aUnlink[$column]= false;
		}
		// alex 19/04/2005:	$address darf auch ein Tabellen-Name sein
		function imageLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STAliasTable", "STObjectContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STAliasTable", "STObjectContainer");

			$this->imageLinkA($column, null, $toPath, $byte, $width, $height, $address, false);
		}
		function imageBorderLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STAliasTable", "STBaseContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STAliasTable", "STBaseContainer");

			$this->imageLinkA($column, null, $toPath, $byte, $width, $height, $address, true);
		}
		function imageLinkA($column, $valueColumn, $toPath, $byte, $width, $height, $address, $border)
		{
			if($address==="")
				$address= null;
			if(typeof($toPath, "STAliasTable", "STBaseContainer"))
			{
				$address= $toPath;
				$toPath= null;
			}
			if($toPath)
				$this->upload($column, $toPath, "image/gif,image/pjpeg", $byte, $width, $height);
			$extraField= "imagelink";
			if($border)
				$extraField.= "1";
			else
				$extraField.= "0";
			$this->linkA($extraField, $column, $address, $valueColumn);
		}
		function imagePkLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STAliasTable", "STBaseContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STAliasTable", "STBaseContainer");

			$this->imageLinkA($column, $this->sPKColumn, $toPath, $byte, $width, $height, $address, false);
		}
		function imageBorderPkLink($column, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($toPath, 2, "string", "null", "STAliasTable", "STBaseContainer");
			Tag::paramCheck($byte, 3, "int");
			Tag::paramCheck($width, 4, "int");
			Tag::paramCheck($height, 5, "int");
			Tag::paramCheck($address, 6, "string", "null", "STAliasTable", "STBaseContainer");

			$this->imageLinkA($column, $this->sPKColumn, $toPath, $byte, $width, $height, $address, true);
		}
		function imageValueLink($column, $valueColumn, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($valueColumn, 2, "string");
			Tag::paramCheck($toPath, 3, "string", "null", "STAliasTable", "STBaseContainer");
			Tag::paramCheck($byte, 4, "int");
			Tag::paramCheck($width, 5, "int");
			Tag::paramCheck($height, 6, "int");
			Tag::paramCheck($address, 7, "string", "empty(string)", "null");

			$this->imageLinkA($column, $valueColumn, $toPath, $byte, $width, $height, $address, false);
		}
		function imageBorderValueLink($column, $valueColumn, $toPath= null, $byte= 0, $width= 0, $height= 0, $address= null)
		{
			Tag::paramCheck($column, 1, "string");
			Tag::paramCheck($valueColumn, 2, "string");
			Tag::paramCheck($toPath, 3, "string", "null", "STAliasTable", "STBaseContainer");
			Tag::paramCheck($byte, 4, "int");
			Tag::paramCheck($width, 5, "int");
			Tag::paramCheck($height, 6, "int");
			Tag::paramCheck($address, 7, "string", "empty(string)", "null");

			$this->imageLinkA($column, $valueColumn, $toPath, $byte, $width, $height, $address, true);
		}
	function download($columnName, $access= null)
	{
		Tag::paramCheck($columnName, 1, "string");
		Tag::paramCheck($access, 2, "string", "null");

		$field= $this->findAliasOrColumn($columnName);
		$column= $field["column"];
		$this->linkA("download", $column, null, $this->sPKColumn);
	}
	function disabled($columnName, $enum= null)
	{
		Tag::paramCheck($columnName, 1, "string");
		Tag::paramCheck($enum, 2, "string", "null");

		$this->linkA("disabled", $columnName, null, $enum);
	}
	function changeFormOptions($submitButton, $formName= "st_checkForm", $action= null)
	{
		$this->asForm= array(	"button"=>	$submitButton,
								"form"=>	$formName,
								"action"=>	$action			);
	}
	function checkBox($columnName, $trueValue)
	{
		Tag::paramCheck($columnName, 1, "string");

		$field= $this->findAliasOrColumn($columnName);
		$column= $field["column"];
		$this->linkA("check", $column, null, $this->sPKColumn);
		$this->aCheckDef[$field["alias"]]= $trueValue;
	}
	// alex 08/06/2005:	alle links in eine Funktion zusammengezogen
	//					und $address darf auch ein STObjectContainer,
	// 					f??r die Verlinkung auf eine neuen Container, sein
	/*protected*/function linkA($which, $aliasColumn, $address, $valueColumn)
	{
		STCheck::param($which, 0, "string");
		STCheck::param($aliasColumn, 1, "string");
		STCheck::param($address, 2, "STObjectContainer", "STAliasTable", "null");
		STCheck::param($valueColumn, 3, "string", "null");
		
		$field= $this->findAliasOrColumn($aliasColumn);
		$aliasColumn= $field["alias"];
		if(!isset($this->showTypes[$aliasColumn]))
			$this->showTypes[$aliasColumn]= array();
		$to= $which;
		if(typeof($address, "STAliasTable"))
		{// wenn ein AliasTabel hereinkommt
		 // diesen in einen Container verpacken
			$tableName= $address->getName();
			// alex 21/10/2005:	$address auf $to ge???ndert
			//					und $address null zugewiesen
			// ?????????????????????????????????????????????????????
			// nicht DEBUGGED
			$to= new STObjectContainer("dbtable ".$tableName, $this->db);
			$to->needTable($tableName, true);
			$address= null;
		}elseif(typeof($address, "STBaseContainer"))
		{// ist die Addresse schon ein Container
		 // nimm die Referenz aus der Containerliste

			$which= "container_".$which;
			$to= &STBaseContainer::getContainer($address->getName());
			$address= null;
		}else //if(!preg_match("/^container_.*/", $which))
		{// ist die Addresse keine Tabelle oder Container
		 // k???nnte es noch sein, dass sie ein Name eines Containers ist
			$containers= STBaseContainer::getAllContainerNames();
			foreach($containers as $name)
			{
				if($name==$address)
				{
					$which= "container_".$which;
					$to= &STBaseContainer::getContainer($name);
					$address= null;
					break;
				}
			}
		}
		if($which=="disabled")
		{
			$this->showTypes[$aliasColumn][$which][]= $valueColumn;
			$valueColumn= null;
		}else
			$this->showTypes[$aliasColumn][$which]= $to;
		if($valueColumn)
		{
			if(!is_array($this->showTypes["columns"]))
				$this->showTypes["valueColumns"]= array();
			$this->showTypes["valueColumns"][$aliasColumn]= $valueColumn;
			$field= $this->findAliasOrColumn($valueColumn);
			$bFound= false;
			foreach($this->show as $column)
			{
				if($column["alias"]==$field["alias"])
				{
					$bFound= true;
					break;
				}
			}
			if(!$bFound)
			{
				$this->showTypes[$valueColumn]= array();
				$this->showTypes[$valueColumn]["get"]="get";
			}

		}
		$this->aLinkAddresses[$aliasColumn]= $address;
		//echo "LinkAddresses:";st_print_r($this->aLinkAddresses,2);
		//echo "showTypes:";st_print_r($this->showTypes,2);
	}
	function activeColumnLink($alias, $representColumnValue= null)
	{
		$this->aActiveLink["column"]= $alias;
		$this->aActiveLink["represent"]= $representColumnValue;
	}
	// alex 19/04/2005:	$address darf auch ein STAliasTable
	// alex 08/06/2005: oder STObjectContainer sein
	function link($column, $address= null)
	{
		$this->linkA("link", $column, $address, null);
	}
	// alex 19/04/2005:	$address darf auch ein STAliasTable
	// alex 08/06/2005: oder STObjectContainer sein
	function namedLink($column, $address= null)
	{
		$this->linkA("namedlink", $column, $address);
	}
	function namedColumnLink($aliasColumn, $valueColumn= "", $address= null)
	{
		if(!$valueColumn)
		    $valueColumn= $aliasColumn;
		$this->linkA("namedcolumnlink", $aliasColumn, $address, $valueColumn);
	}
	function namedPkLink($aliasColumn, $address= null)
	{
		$this->namedColumnLink($aliasColumn, $this->sPKColumn, $address);
	}
	function getColumn($column, $alias= "")
	{
		Tag::paramCheck($column, 1, "string");
		Tag::paramCheck($alias, 2, "string", "empty(string)");

		$field= $this->findAliasOrColumn($column);
		// if column exists in selected list
		// make no entry for getColumn
		$wantColumn= $field["column"];
		foreach($this->show as $content)
		{
			if($content["column"]===$wantColumn)
				return;
		}
		$this->linkA("get", $column, null, null);
	}
	function clearGetColumns()
	{
		$this->showTypes= array();
		return;
		// do not know why I do need this
		$valueColumns= array();
		foreach($this->showTypes as $column=>$value)
		{
			if($column=="valueColumns")
			{
				foreach($value as $need)
					$valueColumns[$need]= "need";

			}elseif(	isset($value["get"])
						and
						!isset($valueColumns[$column])	)
			{
				unset($this->showTypes[$column]["get"]);
			}
		}
	}
	function clearRekursiveGetColumns($bFromIdentif= false)
	{
		$this->clearGetColumns();
		if($bFromIdentif)
			$from= &$this->identifications;
		else
			$from= &$this->show;
    	foreach($from as $key=>$content)
    	{
    		$table= &$this->getFkTable($content["column"], true);
    		if(	$this->Name != $table->getName() &&
				$table->correctTable()				)
    		{
    			$table->clearRekursiveGetColumns();
    			unset($table);
    		}
    	}
	}
	function clearLinkColumn($column)
	{
		$field= $this->findAliasOrColumn($column);
		$aliasColumn= $field["alias"];
		unset($this->showTypes[$aliasColumn]);
	}
	function dropDownSelect($aliasColumn, $callbackFunction)
	{
		$this->linkA("dropdown", $aliasColumn, "st_callbackFunction", null);
		$this->joinCallback($callbackFunction, $aliasColumn);
	}
	function listCallback($callbackFunction, $columnName= null)
	{
		$this->callbackA(STLIST, $columnName, $callbackFunction);
	}
	function insertCallback($callbackFunction, $columnName= null)
	{
		$this->callbackA(STINSERT, $columnName, $callbackFunction);
	}
	function updateCallback($callbackFunction, $columnName= null)
	{
		$this->callbackA(STUPDATE, $columnName, $callbackFunction);
	}
	function indexCallback($callbackFunction)
	{
		$this->callbackA(STLIST, null, $callbackFunction);
		//$this->aCallbacks["index"][]= $callbackFunction;
	}
	function deleteCallback($callbackFunction)
	{
		$this->callbackA(STDELETE, null, $callbackFunction);
	}
	function joinCallback($callbackFunction, $columnName= null)
	{
		$this->callbackA("join", $columnName, $callbackFunction);
	}
    /*private*/function callbackA($action, $columnName, $callbackFunction)
    {
		if($columnName)
		{
			$field= $this->findAliasOrColumn($columnName);
			if($action==STLIST)
				$columnName= $field["alias"];
			else
				$columnName= $field["column"];
		}else
			$columnName= $action;
    	Tag::alert(!function_exists($callbackFunction), "STAliasTable::callback()",
    			"user defined function <b>$callbackFunction</b> does not exist<br />");
    	if(!isset($this->aCallbacks[$columnName]))
    		$this->aCallbacks[$columnName]= array();
    	$this->aCallbacks[$columnName][]= array("action"=>$action, "function"=>$callbackFunction);
    }
	function clearCallbacks()
	{
		$this->aCallbacks= array();
	}
	function &getFkTable($fromColumn, $bIsColumn= false)
	{
		STCheck::param($fromColumn, 0, "string");
		STCheck::param($bIsColumn, 1, "bool");
		
		if(!$bIsColumn)
		{
			$field= $this->findAliasOrColumn($fromColumn);
			$fromColumn= $field["column"];
		}
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					if($columns["table"])
					{
						if(typeof($columns["table"], "STDbTable"))
						{
							//echo __file__.__line__."<br>";
							//echo "own database:     ".$this->container->getDatabaseName()."<br>";
							//echo "foreign database: ".$columns["table"]->container->getDatabaseName()."<br>";
							if($columns["table"]->container->getDatabaseName()!==$this->container->getDatabaseName())
								$container= &STBaseContainer::getContainer($columns["table"]->container->getName());
							else
								$container= &$this->container;
						}else
							return $columns["table"];
					}else
						$container= &$this->container;

					$table= &$container->getTable($table);
					return $table;
				}
			}
		}
		return $this->null;// /*incorrect table*/STAliasTable();;
	}
	function getFkTableName($fromColumn)
	{
		$field= $this->findAliasOrColumn($fromColumn);
		$fromColumn= $field["column"];
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
					return $table;
			}
		}
		return null;
	}
	function getFkContent($fromColumn)
	{
		$field= $this->findAliasOrColumn($fromColumn);
		$fromColumn= $field["column"];
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					$tableName= $content["table"]->Name;
					unset($content["table"]);
					$content["table"]= $tableName;
					return $content;
				}
			}
		}
		return null;
	}
	function getFkContainerName($fromColumn)
	{
		$field= $this->findAliasOrColumn($fromColumn);
		$fromColumn= $field["column"];
		foreach($this->aFks as $table=>$content)
		{
			foreach($content as $columns)
			{
				if($fromColumn==$columns["own"])
				{
					if(	$columns["table"]
						and
						$columns["table"]->container->getDatabaseName()!==$this->container->getDatabaseName()	)
					{
						return $columns["table"]->container->getName();
					}
					return $this->container->getName();
				}
			}
		}
		return null;
	}
	function &getForeignKeys()
	{
		return $this->aFks;
	}
	function &getFKs()
	{
		Tag::deprecated("STAliasTable::ForeignKeys()", "STAliasTable::FKs()");
		return $this->FK;
	}
	// alex 08/06/2005:	nun k???nnen Werte auch Statisch in der
	//					STAliasTable gesetzt werden
	function preSelect($columnName, $value, $action= STINSERT)
 	{
		$field= $this->findAliasOrColumn($columnName);
		$columnName= $field["column"];
		if(	!isset($this->aSetAlso[$columnName]) ||
			!is_array($this->aSetAlso[$columnName])	)
		{
			$this->aSetAlso[$columnName]= array();
		}
		if($action==STALLDEF)
		{
			$this->aSetAlso[$columnName][STINSERT]= $value;
			$this->aSetAlso[$columnName][STUPDATE]= $value;
		}else
 			$this->aSetAlso[$columnName][$action]= $value;
	}
	function setAlso($columnName, $value, $action= "All")
	{
		Tag::deprecated("STAliasTable::preSelect(columnName, value, action)", "STAliasTable::setAlso(columnName, value, action)");
		$this->preSelect($columnName, $value, $action);
	}
	// alex 08/06/2005:	und ebenso auch entfernt werden
	function unsetAlso($columnName, $action= "All")
	{
		$field= $this->findAliasOrColumn($columnName);
		$columnName= $field["column"];
		if($action=="ALL")
			unset($this->aSetAlso[$columnName]);
		else
 			unset($this->aSetAlso[$columnName][$action]);
	}
	function setLinkByNull($bSet= true)
	{
		$this->bSetLinkByNull= $bSet;
	}
	function radioButtonsByEnum($aliasName)
	{
		$field= $this->findAliasOrColumn($aliasName);
		$this->enumField[$field["column"]]= "radio";
		$this->onlyRadioButtons[$field["column"]]= true;
	}
	function pullDownMenuByEnum($aliasName)
	{
		$field= $this->findAliasOrColumn($aliasName);
		$this->enumField[$field["column"]]= "pull_down";
	}
	// deprecated
    function noInsert()
    {
    	$this->bInsert= false;
    }
	// deprecated
    function noUpdate()
    {
    	$this->bUpdate= false;
    }
	// deprecated
    function noDelete()
    {
    	$this->bDelete= false;
    }
    function doInsert($do= true)
    {
    	$this->bInsert= $do;
    }
    function doUpdate($do= true)
    {
    	$this->bUpdate= $do;
    }
    function doDelete($do= true)
    {
    	$this->bDelete= $do;
    }
    /**
     * whether sort an Table
     * by clicking of one of the head-names
     * 
     * @param boolean $do whether should sort
     */
    function doHeadLineSort($do)
    {
    	$this->doTableSorting= $do;
    }
    /**
     * do not sort the Table
     * by clicking of one of the head-names
     */
    function noHeadLineSort()
    {
    	$this->doTableSorting= false;
    }
    function canInsert()
    {
    	return $this->bInsert;
    }
    function canUpdate()
    {
    	return $this->bUpdate;
    }
    function canDelete()
    {
    	return $this->bDelete;
    }
	function setListCaption($bSet)
	{
		$this->bListCaption= $bSet;
	}
	function displayListInColumns($nColumns)
	{
		$this->nDisplayColumns= $nColumns;
	}
	function insertByLink($param, $linkedColumn)
	{
		Tag::paramCheck($param, 1, "string");
		Tag::paramCheck($linkedColumn, 2, "string");

		$this->linkParams[$linkedColumn][STINSERT][]= $param;
	}
	function updateByLink($param, $linkedColumn)
	{
		$this->linkParams[$linkedColumn][STUPDATE][]= $param;
	}
	function deleteByLink($param, $linkedColumn)
	{
		$this->linkParams[$linkedColumn][STDELETE][]= $param;
	}
	function forwardByOneEntry($toColumn= true)
	{
		$bForward= true;
		if(is_bool($toColumn))
		{
			$bForward= $toColumn;
			$toColumn= null;
		}else
		{
			$field= $this->findAliasOrColumn($toColumn);
			$toColumn= $field["column"];
		}

		$this->aForward["do"]= $bForward;
		$this->aForward["column"]= $toColumn;
	}
		/*public static*/function createDynamicAccess()
		{
			if($this->bDynamicAccessIn!==null)
				return $this->bDynamicAccessIn;

			$checked= array();
			if( count($this->sAcessClusterColumn)
				and
				STUserSession::sessionGenerated()   )
			{
			    //st_print_r($table->sAcessClusterColumn,2);
			    $session= &STUserSession::instance();
				$aAccess= &$session->getDynamicClusters($this);
				if(count($aAccess))
				{
    				//st_print_r($aAccess, 10);
    				$in= "";
    				$where= new STDbWhere();
					// read all columns for dynamic clustering
					// which are set in the table object
					$bFounded= false;
					$aktAccess= "";
					//$accessTo= array();
    				foreach($this->sAcessClusterColumn as $info)
    				{
    				    if($info["cluster"]!=$aktAccess)
    					{
    					    if($in)
    						{
    					        $in= substr($in, 0, strlen($in)-1).")";
    						    $where->orWhere($in);
    						}
    						$checked= array();
    						$aktAccess= $info["cluster"];
    						$in= $aktAccess." in(";
    					}
    					$checked[$info["action"]]= 0;

						//echo "info access: ";st_print_r($info["action"]);echo "<br />";
    				    foreach($aAccess[$info["action"]] as $key=>$cluster)
    					{
    					    if($session->hasAccess($cluster, null, null, false, $info["action"]))
    						{
    						    $in.= $key.",";
    							++$checked[$info["action"]];
								$bFounded= true;
								//$accessTo[$info["action"]]= true;
    						}
						}
    				}
					if($bFounded)
					{
    					$in= substr($in, 0, strlen($in)-1).")";
    					$where->orWhere($in);
    					$this->andWhere($where);
					}
					$this->bDynamicAccessIn= $checked;
				}
			}
			return $checked;
		}
	function setFirstAction($action)
	{
		STCheck::paramCheck($action, 1, "string");

		$this->sFirstAction= $action;
	}
	function getFirstAction()
	{
		return $this->sFirstAction;
	}
	function hasAccess($action= STALLDEF, $makeError= false)
	{
		STCheck::paramCheck($action, 1, "check",	$action===STALLDEF || $action===STLIST || $action===STINSERT ||
													$action===STUPDATE || $action===STDELETE || $action===STCHOOSE,
													"STALLDEF", "STCHOOSE", "STLIST", "STINSERT", "STUPDATE", "STDELETE");
		STCheck::paramCheck($makeError, 2, "bool");

		if(!STSession::sessionGenerated())
			return true;

		$instance= &STSession::instance();
		if($action===STCHOOSE)
			$action= STLIST;
		$clusters= $this->getAccessCluster($action);
		$infoString= $this->getAccessInfoString($action);
		$customID= $this->getAccessCustomID($action);
		$access= true;
		if($clusters)
			$access= $instance->hasAccess($clusters, $infoString, $customID, $makeError, $action);

		if(STCheck::isDebug())
		{
			if($action==STALLDEF)
				$staction= "STALLDEF";
			elseif($action==STCHOOSE)
				$staction= "STCHOOSE";
			elseif($action==STLIST)
				$staction= "STLIST";
			elseif($action==STUPDATE)
				$staction= "STUPDATE";
			elseif($action==STINSERT)
				$staction= "STINSERT";
			elseif($action==STDELETE)
				$staction= "STDELETE";
			$clusterString= "";
			if(is_array($clusters))
			{
				foreach($clusters as $cluster)
					$clusterString.= $cluster.", ";
				$clusterString= substr($clusterString, 0, strlen($clusterString)-2);
			}else
				$clusterString= $clusters;
		}
		if($access)
		{
			if(STCheck::isDebug())
			{
				if($clusterString)
				{
					STCheck::echoDebug("access", "user in action $staction has <b>access</b> to table "
									.$this->Name."(".$this->getDisplayName()
									.") with Clusters '<i>$clusterString</i>'");
				}else
				{
					STCheck::echoDebug("access", "in table ".$this->Name."(".$this->getDisplayName().") no cluster be set, so return true");
				}
			}
			return true;
		}

		STCheck::echoDebug("access", "user in action $staction has <b>no access</b> to table "
								.$this->Name."(".$this->getDisplayName()
								.") with Clusters '<i>$clusterString</i>'");
		/*if($gotoLoginMask)
		{
			Tag::echoDebug("access", "so goto login-mask");
			$this->userManagement->gotoLoginMask(5);
		}*/
		STCheck::echoDebug("access", "gotoLoginMask not be set, so return false");
		return false;
	}
}

?>
