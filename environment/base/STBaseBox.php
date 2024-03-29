<?php

require_once($php_html_description);
require_once($_stmessagehandling);
require_once($php_javascript);
require_once($_stcallbackclass);

abstract class STBaseBox extends TableTag
{
    /**
     * language and format for text messages
     * and nation for date
     * @var array
     */
    var $locale= array( "language"  => 'en',
        "nation"    => 'XXX', // <- undefined
        "format"    => 'UTF-8'      );
    var	$db;
    /**
     * array of selected result
     * from database
     * @var array
     */
    protected $sqlResult= null;
		var $tableContainer;
		var $msg; // class for STMessageHandling
		var	$onError= onErrorMessage;
		var $error= null;
		var	$nextOnError;
		var $aHidden;
		/**
		 * filled array with all columns
		 * in which state (STList/STInsert/STUpdate)
		 * should be disabled by displaying on Browser
		 * @var array
		 */
		protected $aDisabled= array();
		var	$newFields= array();
		var	$aErrorString= null;
		var	$OKUrl= null;
		var	$ahidden= array();
		var $aktualScript= "";
		var	$asSelect;
		var	$asTable;
		var $tableName;
		var	$asDBTable;
		var	$oWhere;
		var	$asOrder;
		var $fieldArray;
		var $uniqueKey;
		var	$aCallbacks= array();
		var $where= "";
		/**
		 * defined names for update and delete columns
		 * @var array
		 */
		protected $aAction= array();
		/**
		 * css stylshet link
		 */
		protected string $css_link= "";
		/**
		 * whether should shifted and restore the container parameters
		 * from stget/older parameter inside url string
		 * @var boolean
		 */
		protected	$bContainerManagement= true;

		function __construct(&$container, $class= "STBaseBox")
		{
			global $HTTP_SERVER_VARS;

			TableTag::__construct($class);
			// alex 17/05/2005:	parameter kann jetzt vom Container STDbTableContainer sein
			// die Datenbank wird nun �ber dieses Objekt geholt
			$this->db= &$container->getDatabase();
			$this->tableContainer= &$container;
			$table= $container->getTable();
			$this->fieldArray[$table->getName()]= $table->columns;
			$this->msg= new STMessageHandling($class, onErrorMessage);
			STBaseBox::init();
			$this->aktualScript= $HTTP_SERVER_VARS["SCRIPT_NAME"];
			$this->error= "NOERROR";
			if($container==null)
				$this->msg->setMessageId("NO_DATABASE");
		}
		public function setLanguage(string $lang, string $nation= "XXX")
		{
		    if(preg_match("/_/", $lang))
		    {
		        $split= preg_split("_/", $lang);
		        $lang= $split[0];
		        $nation= $split[1];
		    }
		    if(STCheck::warning(   $lang != "en" &&
		        $lang != "de"   , "STBaseContainer::setLanguage()", "only en or de currently allowed"))
		    {
		        $lang= "en";
		    }
		    $this->locale['language']= $lang;
		    $this->locale['nation']= $nation;
		}
		function createMessages()
		{
		    if($this->locale['language'] == "de")
			{
				$this->msg->setMessageContent("EMPTY_RESULT", "");
				$this->msg->setMessageContent("NO_DATABASE", "dem Konstruktor von der Klasse $class, muss eine Datenbank mitgegeben werden");
				$this->msg->setMessageContent("NOERROR", ""); // alle aenderungen wurden mit der Datenbank abgeglichen
				$this->msg->setMessageContent("SQLERROR", "SQL-Error werden immer zuzueglich mit Nummer ausgegeben, getrennt mit underline '_'");
				$this->msg->setMessageContent("CALLBACKERROR@", "@"); // hier wird die Fehlermeldung der Callbackfunktion gesetzt
				
			}else // language have to be english ('en')
			{
				$this->msg->setMessageContent("EMPTY_RESULT", "");
				$this->msg->setMessageContent("NO_DATABASE", "the constructor from class need to get an database");
				$this->msg->setMessageContent("NOERROR", ""); // all changes be made in database
				$this->msg->setMessageContent("SQLERROR", "SQL-Error always have to be set with number paresed with an underline '_'");
				$this->msg->setMessageContent("CALLBACKERROR@", "@"); // here have to be set the error messages from callback function
			}
		}
		function onOKGotoUrl($url)
		{
			$this->msg->onOKGotoUrl($url);
		}
		function getMessageId()
		{
			return $this->msg->getMessageId();
		}
		function setMessageContent($messageId, $messageString)
		{
			$this->msg->setMessageContent($messageId, $messageString);
		}
		function init()
		{
			$this->nextOnError= null;
			$this->error= null;
			$this->aErrorString= null;
			$this->OKUrl= null;
			$this->ahidden= array();
	  		$this->asSelect= array();
			$this->asTable= array();
			$this->asDBTable= null;
			$this->oWhere= null;
			$this->asOrder= array();
			$this->uniqueKey= array();
			$this->aCallbacks= array();
		}
		function clear()
		{
			TableTag::clear();
			STBaseBox::init();
			$this->init();
		}
		function callback($columnName, $callbackFunction, $action)
		{
			if(!function_exists($callbackFunction))
			{
				echo "\n<b>ERROR:</b> user defined function <b>$callbackFunction</b> does not exist<br />\n";
				if(phpVersionNeed("4.3.0", "debug_backtrace()"))
					showBackTrace(debug_backtrace());
				exit;
			}
			if(!isset($this->aCallbacks[$columnName]))
				$this->aCallbacks[$columnName]= array();
			$this->aCallbacks[$columnName][]= array(	"action"=>	$action,
														"function"=>$callbackFunction	);
		}
		protected function makeCallback($action, &$oCallbackClass, $columnName, $rownum)
		{//STCheck::is_warning(1,"","makeCallback");
		    if(STCheck::isDebug())
		    {
		        STCheck::echoDebug("callback", "makeCallback(ACTION:'$action', CALLBACKCLASS("
    									.get_class($oCallbackClass)."), COLUMN:'$columnName', ROWNUM:$rownum)");
		        STCheck::param($action, 0, "string");
		        STCheck::param($oCallbackClass, 1, "STCallbackClass");
		        STCheck::param($columnName, 2, "string");
		        STCheck::param($rownum, 3, "int", "string");
		    }

			if(!count($this->aCallbacks))
				return false;
			$oCallbackClass->clear();
			$oCallbackClass->aAcessClusterColumns= &$this->asDBTable->sAcessClusterColumn;
			if(!$oCallbackClass->aTables)
			{
				$aliases= $this->asDBTable->getAliasOrder();
				$aliases= array_flip($aliases);
				$oCallbackClass->aTables= $aliases;
			}
			$errorString= "";
			$oCallbackClass->setWhere($this->where);
			$oCallbackClass->column= $columnName;
			$oCallbackClass->action= $action;
			$oCallbackClass->aUnlink= $this->asDBTable->aUnlink;
			if( (    isset($this->aDisabled[$columnName][0]) &&
			         $this->aDisabled[$columnName][0] == true    ) ||
			    (    isset($this->aDisabled[$columnName][$action]) &&
			         $this->aDisabled[$columnName][$action] == true                 )   )
			{
			    $oCallbackClass->disabled($columnName);
			}else
			    $oCallbackClass->enabled($columnName);
			
			// look first wether callbacks exist
			// for the current column
			if(	isset($this->aCallbacks[$columnName]) )
			{
			    Tag::echoDebug("callback", "make callback for existing column $columnName");
			    $oCallbackClass->rownum= $rownum;
				$callbacks= $this->aCallbacks[$columnName];
				foreach($callbacks as $functionArray)
				{
					if($action==$functionArray["action"])
					{
      					$errorString= $functionArray["function"]($oCallbackClass, $columnName, $rownum, $action);
    					if(	is_string($errorString)
    						and
    						$errorString!==""	)
    					{
							$this->msg->setMessageId("CALLBACKERROR@", $errorString);
    						return $errorString;
    					}
					}
				}
				$this->aDisabled[$columnName][$rownum]= $oCallbackClass->argument("disabled", $columnName, 0);
				return true;
			}else
			{// elsewhere search in loop all callbacks for STLIST, STINSERT, ...
			 // wether columnName the same as action
			 	Tag::echoDebug("callback", "loop callbacks for STLIST, STINSERT, ... -> wether columnName has the same action");
				$incomming= $columnName;
				$bOk= false;
				//echo "action:$action<br />";
				//echo "columnName:$columnName<br />";
				foreach($this->aCallbacks as $column=>$content)
				{
					//echo "column:$column<br />";
					$columnName= $incomming;
					if($action==$columnName)
						$columnName= $column;
				    if(!isset($oCallbackClass->aDisabled[$columnName]))
				    {
				        if( (   isset($this->aDisabled[$columnName][0]) &&
				                $this->aDisabled[$columnName][0] == true    ) ||
				            (   isset($this->aDisabled[$columnName][$action]) &&
				                $this->aDisabled[$columnName][$action] == true                 )   )
				        {
					        $oCallbackClass->disabled($columnName);
					    }else
					        $oCallbackClass->enabled($columnName);
					}
					foreach($content as $functionArray)
    				{
    					if(	$action==$functionArray["action"]
							and
							(	$columnName==$column
								or
								$column==STLIST
								or
								$column==STINSERT
								or
								$column==STUPDATE
								or
								$column==STDELETE
								or
								$column==STALLDEF		)		)
    					{
          					$errorString= $functionArray["function"]($oCallbackClass, $columnName, $rownum, $action);
        					if(	is_string($errorString)
        						and
        						$errorString!==""	)
        					{
								$this->msg->setMessageId("CALLBACKERROR@", $errorString);
        						return $errorString;
        					}
							$bOk= true;
							break;
    					}
    				}
    				$this->aDisabled[$columnName][$rownum]= $oCallbackClass->argument("disabled", $columnName, 0);
				}
			}
			return $bOk;
		}
		public function updateLine(string $column, string $alias= null)
		{
		    if(!isset($alias))
		        $alias= $column;
		    $this->aAction['update']= $alias;
		    $this->select($column, $alias);
		}
		public function deleteLine(string $column, string $alias= null)
		{
		    if(!isset($alias))
		        $alias= $column;
	        $this->aAction['delete']= $alias;
	        $this->select($column, $alias);
		}
		function select($column, $alias= null)
		{
			$column= trim($column);
			$alias= trim($alias);
			$split= preg_split("/[.]/", $column);
			if(	count($split)>1
				and
				!preg_match("/^concat(_ws)?()/i", $column)	)
			{
				$column= substr($column, strlen($split[0])+1, strlen($column));
				$table= $split[0];
			}
			//if(!$name)
			//	$name= $column;
			$n= count($this->asSelect);
			$this->asSelect[$n]= array();
			$this->asSelect[$n]["column"]= $column;
			$this->asSelect[$n]["alias"]= $alias;
			if(isset($table))
			    $this->asSelect[$n]["table"]= $table;
		}
		function unSelect($column)
		{
			if($this->asDBTable)
				$this->asDBTable->unSelect($column);
			else// toDo: asUnSelect wird noch nirgends ausgelesen
				$this->asUnSelect[]= $column;
		}
		/* toDo: for selfTables
		function select($column, $alias= null)
		{
			echo "<b>Error:</b> the function ->select() can not use in OSTSearchBox,<br />\n";
			echo " please apply select only in the tables!";
			exit;
		}*/
		function table($table, $name= null)
		{// wenn eine fertige Tabelle herein kommt,
		 // wird nicht die Reference übernommen
		 // da die Tabelle in späterer folge vielleicht noch geändert wird

			if(typeof($table, "STBaseTable"))
			{
				$this->tableName= $table->getName();
				$this->asDBTable= $table;
				// callback �bergabe
    			if(	isset($this->asDBTable->aCallbacks) &&
    				is_array($this->asDBTable->aCallbacks)	)
    			{
	        		foreach($this->asDBTable->aCallbacks as $column=>$functions)
	        		{
	        			if(	!isset($this->aCallbacks[$column]) ||
	        				!is_array($this->aCallbacks[$column])	)
	        			{
	        				$this->aCallbacks[$column]= array();
	        			}
	        			foreach($functions as $fCallback)
	        			{
	        				$this->aCallbacks[$column][]= $fCallback;
	        			}
	        		}
    			}
				if(count($table->aInputSize))
					$this->aInputSize= array_merge($this->aInputSize, $table->aInputSize);
				return;
			}
			$table= trim($table);
			$name= trim($name);
			if(!$name)
				$name= $table;
			$this->tableName= $name;
			$this->asTable[$name]= $table;
			$container= &STBaseContainer::getContainer();
			if(!$container)
				$container= &$this->tableContainer;
			$this->asDBTable= $container->getTable($table);
		}
		function &getTable()
		{
			$this->createTables();
			return $this->asDBTable;
		}
		function createTables()
		{
			if(count($this->asSelect))
			{
  				$tableName= reset($this->asTable);
  				if(!$tableName)
  					$tableName= $this->asDBTable->getName();
  				if($this->asDBTable==null)
  					$this->asDBTable= new STDbTable($tableName, $this->tableContainer, $this->getOnError());
				foreach($this->asSelect as $column)
				{
					$this->asDBTable->show[]= array(	"table"=>$tableName,
														"column"=>$column["column"],
														"alias"=>$column["alias"]		);
				}
				$this->asSelect= array();
			}
		}
		function createStatArray($array, $determine)
		{
			$buffer= "";
			if($array)
			{
				foreach($array as $column)
					$buffer.= $column.$determine;
				if($buffer)
					$buffer= substr($buffer, 0, strlen($buffer)-strlen($determine));
			}
			return $buffer;
		}
		function defaultOnError($onError)
		{
			$this->onError= $onError;
			$this->msg->setOnErrorStatus($onError);
		}
		function setOnError($onError)
		{
			$this->nextOnError= $onError;
			$this->msg->setOnErrorStatus($onError);
		}
		function getOnError($type= "ALL")
		{
			if($this->nextOnError===null)
				$onError= $this->onError;
			else
				$onError= $this->nextOnError;

			if($type!="ALL")
			{
				if($onError==onErrorMessage)
					$onError= noErrorShow;
			}
			return $onError;
		}
		/*protected*/function setSqlError($sqlResult, $db= null)
		{
  			if($sqlResult===null)
  			{
				if($db===null)
					$db= $this->db;
  				$sqlErrorMessage= $db->getError();
				if($this->db->errno()!=0)
				{//
					$messageId= "SQLERROR";//_".$this->db->errno();
  					$this->msg->setMessageId($messageId, $sqlErrorMessage);
				}
  			}elseif(!isset($sqlResult) || !is_array($sqlResult) || count($sqlResult)===0)
				$this->msg->setMessageId("EMPTY_RESULT");
		}
		public abstract function getResult() : array;
		public function getCssLink()
		{
			showBackTrace();
			if(!isset($this->css_link["css"]))
				return null;
			$css= $this->css_link["css"];
			$description= "Stylesheet Link";
			if(isset($this->css_link["description"]))
				$description= $this->css_link["description"];
			$link= getCssLink($link, $description);
			return $link;
		}
		public function setCssLink($link, $description= null)
		{
			$this->css_link["css"]= $link;
			if($description !== null)
				$this->css_link["description"]= $description;
		}
		function hidden($name, $value)
		{
			$this->aHidden[$name]= $value;
		}
		function setDateFormat($sFormat)
		{
			if(!$this->db->setDateFormat($sFormat))
				return false;
			$this->dateFormat= strtoupper(trim($sFormat));
			return true;
		}
		function uniqueKey($keys)
		{
			$this->uniqueKey[]= $keys;
		}
		protected function getFieldArray($statement= null)
		{
		    STCheck::alert(!isset($this->fieldArray), "STBaseBox::getFieldArray()", "no local fieldArray be set");
			if($statement==null)
			{
				$statement= reset($this->asTable);
				if(!$statement)
					$statement= $this->asDBTable->getName();
			}
			$fields= $this->fieldArray[$statement];
			if($fields==null)
			{
				Tag::echoDebug("fieldArray", "file:".__file__." line:".__line__);
    			$fieldArray= $this->db->describeTable($statement);
    			foreach($this->uniqueKey as $uniqueKey)
    			{
    				$keys= preg_split("/ /", $uniqueKey);
    				foreach($fieldArray as $key=>$field)
    				{
    					foreach($keys as $unique)
    					{
    						if($field["name"]==trim($unique))
    						{
    							if(count($keys)==1)
    							{
    								if(!preg_match("/unique_key/", $field["flags"]))
    									$fieldArray[$key]["flags"].= " unique_key";
    							}else
    							{
    								if(!preg_match("/multiple_key/", $field["flags"]))
    									$fieldArray[$key]["flags"].= " multiple_key($uniqueKey)";
    								else
    									$fieldArray[$key]["flags"]= preg_replace("/multiple_key/", $field["flags"], "multiple_key($uniqueKey)");
    							}
    						}
    					}

    				}
    			}
				if(count($this->newFields))
					$fieldArray= array_merge($fieldArray, $this->newFields);
				$this->fieldArray[$statement]= $fieldArray;
			}
			if(	isset($fields) &&
				isset($this->asDBTable->show)	)
			{
				foreach($fields as &$field)
				{
					$content= $this->getAfterContent($field["name"]);
					if(isset($content["addContent"]))
						$field["addContent"]= $content["addContent"];
					if(isset($content["addBehind"]))
						$field["addBehind"]= $content["addBehind"];
				}
				
			}
			return $fields;//$this->fieldArray[$statement];
		}
		function getAfterContent($fieldName)
		{
			$content= null;
			foreach($this->asDBTable->show as $field)
			{
				if($field["column"] == $fieldName)
				{
					if(isset($field["addContent"]))
						$content["addContent"]= $field["addContent"];
					if(isset($field["addBehind"]))
						$content["addBehind"]= $field["addBehind"];
					break;
				}
			}
			return $content;
		}
		function isEnum($columnName, $enum1)
		{
			$arg_num= func_num_args();
			$enumMaxLen= 0;
			$string= "enum(";
			for($c= 1; $c<$arg_num; $c++)
			{
				$enum= func_get_arg($c);
				if(is_string($enum))
				{
					$enumLen= strlen($enum);
					if($enumLen>$enumMaxLen)
						$enumMaxLen= $enumLen;
					$enum= "'".$enum."'";
				}
				$string.= $enum.",";
			}
			$string= substr($string, 0, strlen($string)-1).")";
			$type= "string";
			if(is_int($enum1))
			{
				$type= "int";
				$enumMaxLen= 11;
			}
			$this->addNewField($columnName, $string, $type, $enumMaxLen);
		}
		function isNotNull($columnName)
		{
			$this->addNewField($columnName, "not_null", null, null);
		}
		function addNewField($name, $flags, $type, $len)
		{
			$bFound= false;
			foreach($this->newFields as $nr=>$field)
			{
				if($field["name"]==$name)
				{
					$bFound= true;
					break;
				}
			}
			if(!$bFound)
			{
				if(!$flags)
					$flags= "";
				$new= array(	"name"=>$name,
								"flags"=>$flags,
								"type"=>$type,
								"len"=>$len	);
				$this->newFields[]= $new;
				return count($this->newFields)-1;
			}else
			{
				if($flags)
				{
					if($this->newFields[$nr]["flags"])
						$this->newFields[$nr]["flags"].= " ";
					$this->newFields[$nr]["flags"].= $flags;
				}
				if($type)
					$this->newFields[$nr]["type"]= $type;
				if($len)
					$this->newFields[$nr]["len"]= $len;
				return $nr;
			}
		}
		function where($where)
		{
			$this->where= $where;
		}
		function searchByAlias($aliasName)
		{
		    return $this->asDBTable->searchByColumn($aliasName);
		}
		function searchByColumn($columnName)
		{
		    return $this->asDBTable->searchByColumn($columnName);
		}
		function findAliasOrColumn($name, $firstAlias= false)
		{
		    return $this->asDBTable->findAliasOrColumn($name);
		}
		public function doContainerManagement($bManagement)
		{
		    $this->bContainerManagement= $bManagement;
		}
}

?>