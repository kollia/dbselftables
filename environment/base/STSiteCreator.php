<?php

require_once($_stmessagehandling);
require_once($php_html_description);
require_once($_stdownload);


class STSiteCreator extends HtmlTag
{
    /**
     * language and format for text messages
     * and nation for date
     * @var array
     */
    var $locale= array( "language"  => 'en',
                        "nation"    => 'XXX', // <- undefined
                        "format"    => 'UTF-8'      );

	/**
	 * generated number unique for every site
	 * @var string
	 */
	private $generatedSiteNumber= null;
		var	$db;
		var	$defaultTitles= array();
		var	$project;
		var	$sFirstTableContainerName;
		var	$tableContainer;
		var	$defaultTitle= "Db SelfTables";
		var	$startPage= null;
		var	$sBackButton= "";
		var	$oMainTable;
		//deprecated
		var	$aNoChoise= array();
		/**
		 * shows whether install method
		 * be called
		 * @var boolean
		 */
		var $bDoInstall= false;
		var	$uRequireSites= array();
		var $aNeededVars= array();// Variablen welche auf selbst deffinierter Seite ben�tigt werden
		var	$aBehindTableIdentif= array();
		var	$aBehindProjectIdentif= array();
		var	$aBehindHeadLineButtons= array();
		var	$backButtonAddress;
		var	$bBackButton= true;
		var	$oCurrentListTable;
		var $asError= array();
		var	$aCallbacks= array();
		var $bChooseInTable= true; 	// ob auch, wenn die Tabelle angezeigt wird,
									// eine Auswahl existieren soll
		var	$bContainerManagement= true; // ob die Container in das older verschoben werden soll
		var	$aContainerAdress= array(); // alle link-Adressen der ContainerButtons
		var $logoutButton= null;
		var $aDefaultCssLink= array();
		var $aJavaScriptLinks= array();
		protected $bUseOnlyDefaultCssLinks= false;

		function __construct($container= null)
		{
			global	$_selftable_first_main_database_name;

			STCheck::paramCheck($container, 1, "STBaseContainer", "STFrameContainer", "null");

			if(STCheck::isDebug())
			{
				global	$_st_page_starttime_;

				STCheck::setPageStartTime();
				STCheck::echoDebug("performance", "creating object of class STSiteCreator ".date("H:i:s")." ".(time()-$_st_page_starttime_));
			}
			HtmlTag::__construct();
			if($container)
			{
				// alex 11/09/2005:	maybe the container is no refference,
				//					so took the container from globaly list
				$containerName= $container->getName();
				$listContainer= &STBaseContainer::getContainer($containerName);
				// alex 11/10/2022: if the container from list not the same as from parameter
				//                  took the parameter container
				if( !isset($listContainer) ||
				    $listContainer->getName() != $container->getName()  )
				{
				    $this->setMainContainer($container);
				}else
				    $this->setMainContainer($listContainer);
				if(!$_selftable_first_main_database_name)
				{
					$db= $container->getDatabase();
					$_selftable_first_main_database_name= $db->getName();
					$this->db= &$db;
				}
			}
		}
		function setDefaultLanguage(string $lang, string $nation= "XXX")
		{
			$this->locale['language']= $lang;
			$this->locale['nation']= $nation;
		}
		function createMessages($onError)
		{	
			$action= $this->getAction();
			$oTable= &$this->tableContainer->getTable();
			$msgHandling= new STMessageHandling(get_class($this), $onError);
			
			if($this->locale['language'] == "en")
			{
				if($this->sBackButton == "")
					$this->sBackButton= " back ";
				$msgHandling->setMessageContent("LISTACCESSERROR@", "user has no permission to see table @");
				$msgHandling->setMessageContent("INSERTACCESSERROR@", "user has no permission to fill content into table @");
				$msgHandling->setMessageContent("UPDATEACCESSERROR@", "user has no permission to change content inside table @");
				$msgHandling->setMessageContent("DELETEACCESSERROR@", "user has no permission to delete content from table @");
				
			}elseif($this->locale['language'] = "de")
			{
				if($this->sBackButton == "")
					$this->sBackButton= " zurueck ";
				$msgHandling->setMessageContent("LISTACCESSERROR@", "Benutzer hat keine Berechtigung die Tabelle @ anzusehen");
				$msgHandling->setMessageContent("INSERTACCESSERROR@", "Benutzer hat keine Berechtigung in der Tabelle @ neue Eintraege zu erstellen");
				$msgHandling->setMessageContent("UPDATEACCESSERROR@", "Benutzer hat keine Berechtigung Eintraege in der Tabelle @ zu aendern");
				$msgHandling->setMessageContent("DELETEACCESSERROR@", "Benutzer hat keine Berechtigung Eintraege in der Tabelle @ zu loeschen");
			}
			
			if(isset($oTable))
			{
    			if(	$action == STLIST &&
    				!$oTable->hasAccess(STLIST, true)	)
    			{
    				$msgHandling->setMessageId("LISTACCESSERROR@", $oTable->getDisplayName());
    				
    			}elseif(	$action == STDELETE &&
        					(	!$oTable->canDelete() ||
        						!$oTable->hasAccess(STDELETE, true)	)	)
    			{
    				$msgHandling->setMessageId("DELETEACCESSERROR@", $oTable->getDisplayName());
    				
      			}elseif(	$action == STINSERT &&
    						(	!$oTable->canInsert() ||
    							!$oTable->hasAccess(STINSERT, true)	)	)
    			{
    				$msgHandling->setMessageId("INSERTACCESSERROR@", $oTable->getDisplayName());
    				
    			}elseif(	$action == STUPDATE &&
    						(	!$oTable->canUpdate() ||
    							!$oTable->hasAccess(STUPDATE, true)	)	)
    			{
    				$msgHandling->setMessageId("UPDATEACCESSERROR@", $oTable->getDisplayName());
    			}		
			}
			return $msgHandling;
		}
		protected function closeUserDbConnection()
		{
			// Dummy function for STSessionSiteCreator
			// there is eventually defined an extra user database
			// which should also be closed
		}
		/**
		 * Display the tag and all sub tags
		 * on browser screen
		 * with the possibility to testing the page
		 * 
		 * @param boolean $bCloseConnection close the connection to database
		 */
		function display($bCloseConnection= true)
		{
		    //-------------------------------------------------------------------------------------------------------
		    // alex:  19/10/2022
		    //        since use database session
		    //        do not close connection
		    //        because after finished site,
		    //        STDbSessionHandler try to write session data
		    //        into database
			/*$aClosed= array();
			if($bCloseConnection)
			{
				$containers= &STBaseContainer::getAllContainer();
				foreach($containers as $container)
				{
					if(typeof($container, "STDatabase"))
					{
						$container->closeConnection();
						$aClosed[$container->getName()]= true;
					}
				}
				$this->closeUserDbConnection();
			}*/
			//-------------------------------------------------------------------------------------------------------
			
			if(STCheck::isDebug("test"))
			{
				$this->getDisplayString(0);
				$this->testing();
				// generate page with getDisplayString() method in two times
				// because if some errors by the first time it should seen in the output log
				// testing generate new link for automatic reload the page
				// and by display the page on screen we need a new generated page
				echo "<!DOCTYPE html>\n";
				echo $this->getDisplayString(0);
			}else
			{
				echo "<!DOCTYPE html>";
				if(STCheck::isDebug())
					echo "\n";
				HtmlTag::display($bCloseConnection);
			}
			if(Tag::isDebug())
			{
				global	$_st_page_starttime_;

				Tag::echoDebug("performance", "END of hole page-display in class STSiteCreator ".date("H:i:s")." needed ".(time()-$_st_page_starttime_)." sec.");
			}
		}
		function doContainerManagement($bManagement)
		{
			$this->bContainerManagement= $bManagement;
		}
		function setMainContainer($container)
		{
			STCheck::paramCheck($container, 1, "STBaseContainer", "string");

			global	$global_first_objectContainerName;

			if(is_string($container))
			{
				$containerName= $container;
				$container= &STBaseContainer::getContainer($containerName);
			}else
				$containerName= $container->getName();

			$container->bFirstContainer= true;

			if($this->sFirstTableContainerName)
			{
				$before= &STBaseContainer::getContainer($this->sFirstTableContainerName);
				$before->bFirstContainer= false;
			}
			$this->sFirstTableContainerName= $containerName;
			$global_first_objectContainerName= $containerName;
			// alex 17/05/2005:	parameter kann jetzt vom Container STObjectContainer sein
			// 					die Datenbank wird nun �ber dieses Objekt geholt
			$this->tableContainer= &$container;
			if(!$this->db)
				$this->db= $container->getDatabase();
		}
		function needVar($name, &$value)
		{
			$this->aNeededVars[$name]= &$value;
		}
		function &getVar($name)
		{
			return $this->aNeededVars[$name];
		}
		function &getDatabase()
		{
			return $this->db;
		}
		function setProjectID($projectID)
		{
			$this->nProjectID= $projectID;
		}
		function getProjectID()
		{
			if($this->nProjectID===null)
				$this->nProjectID= 1;
			return $this->nProjectID;
		}
		function setProjectDisplayName($name)
		{
			$this->project= $name;
		}
		function setProjectIdentifier($project)
		{
			Tag::deprecated("STSiteCreator::setProjectDisplayName()", "STSiteCreator::setProjectIdentifier()");
			$this->setProjectDisplayName($project);
		}
		function getProjectIdentifier()
		{
			Tag::deprecated("STSiteCreator::getProjectDisplayName()", "STSiteCreator::getProjectIdentifier()");
			$this->getProjectDisplayName();
		}
		function getProjectDisplayName()
		{
			$project= $this->tableContainer->getProjectDisplayName();
			if($project===null)
				$project= $this->project;
			return $project;
		}
		public function chooseTitle($title)
		{
			Tag::deprecated("STSiteCreator::title()", "STSiteCreator::chooseTitle()");
			$this->chooseTitle= $title;
		}
		public function title(string $title)
		{
			$this->defaultTitle= $title;
		}
		public function getTitle()
		{
			return $this->defaultTitle;
		}
		function setStartPage($file)
		{
			$this->startPage= $file;
		}
		function getStartPage()
		{
			if(!isset($this->startPage))
			{
				global $HTTP_SERVER_VARS;
			
				$this->startPage= $HTTP_SERVER_VARS["SCRIPT_NAME"];
			}
			return $this->startPage;
		}
		function getResult()
		{
			if(!typeof($this->tableContainer, "STObjectContainer"))
			{
				STCheck::is_warning(1, "STSiteCreator::getResult()",
								"this function is only for an STObjectContainer");
				return null;
			}
			return $this->tableContainer->getResult();

		}
		function getInsertID()
		{
			if(!typeof($this->tableContainer, "STObjectContainer"))
			{
				STCheck::is_warning(1, "STSiteCreator::getResult()",
								"this function is only for an STObjectContainer and action STINSERT and STUPDATE");
				return null;
			}
			$action= $this->getAction();
			if(	$action!==STINSERT
				or
				$action !==STUPDATE	)
			{
				STCheck::is_warning(1, "STSiteCreator::getResult()",
									"this function is only for action STINSERT and STUPDATE");
				return null;
			}
			return $this->tableContainer->getInsertID();
		}
	function showLogoutButton($buttonName= "log out", $align= "right", $buttonId= "logoutMainButton")
	{
		$this->logoutButton= array(	"buttonName"=>$buttonName,
									"columnAlign"=>$align,
									"buttonId"=>$buttonId		);
	}
		public function execute($onError= onErrorMessage)
		{
			global	$HTTP_GET_VARS;
			global  $__global_finished_SiteCreator_result;

			Tag::alert($this->tableContainer==null, "STDbSiteCreator::execute()",
								"befor execute set container in constructor or with ::setMainContainer()", 1);
			if(	STCheck::isDebug("install") &&
				!$this->bDoInstall				)
			{
				$className= get_class($this);
				STCheck::echoDebug("install", "<b>WARNING</b> no {$className}->install() be called before {$className}->execute()");
			}
			// create first the container where will be set the maintable / other tables
			$this->tableContainer->setDefaultLanguage($this->locale['language'], $this->locale['nation']);
			if(isset($HTTP_GET_VARS["stget"]))
				$get_vars= $HTTP_GET_VARS["stget"];
			if(isset($get_vars["table"]))
				$queryTable= $get_vars["table"];
			if(!isset($get_vars))
				$get_vars= array();
			// alex 01/07/2005:	gibt es in stget einen Container
			//					wechsle vom Haupt-Container zu diesem
			if( isset($get_vars["container"]) &&
			    trim($get_vars["container"]) != ""   )
			{
				$container= &STBaseContainer::getContainer($get_vars["container"]);
				Tag::alert(!isset($container), "STDbSiteCreator::execute()",
												"do not found given container from GET-VARS \""
												.$get_vars["container"]."\" in container-list");
				$this->tableContainer= &$container;
				// bug: 28/12/2009
				//	by STFrameContainer no database exists and so comming thru getDatabase() NULL
				//	if save this without chek on this-db
				//	the container from set table STQueryString in global variable $global_selftables_query_table["table"]
				//	lost the member variable container
				$db= $container->getDatabase();
				if($db !== NULL)
				    $this->db= &$db;//&$container->getDatabase();			
			}
			if(is_array($this->logoutButton))
				$this->tableContainer->showLogoutButton(	$this->logoutButton["buttonName"],
															$this->logoutButton["columnAlign"],
															$this->logoutButton["buttonId"]);

			// alex 18/05/2005:	wenn die Aktion für choose (Auswahl) steht
			//					kontrolliere ob eine Tabelle schon als erstes
			//					aufgelistet werden soll
			if(is_string($get_vars))
				$get_vars= array();
			$get_vars["action"]= $this->getAction();
			if(	(	!isset($get_vars["table"]) ||
					$get_vars["table"] == ""		) &&
				$get_vars["action"] != STCHOOSE			)
			{
				$get_vars["table"]= $this->getTableName();
			}
			
			if(	isset($get_vars["table"]) &&
				is_string($get_vars["table"]) &&
				isset($this->uRequireSites[$get_vars["table"]]))
			{// wenn gewünscht inkludiere eine weitere Seite
				$siteCreator= &$this;// zugriff auf gegenwärtiges Objekt in der neuen Seite
				require($this->uRequireSites[$get_vars["table"]]["site"]);
			}
			
			if(STCheck::isDebug("container"))
			{
			    echo "<br />";
			    $msg= "execute container ".get_class($this)."(<b>".$this->tableContainer->getName()."</b>)";			    
			    if( isset($this->db) &&
			        $this->db != NULL          )
			    {
			        $msg.= " with database ".get_class($this->db)."(<b>".$this->db->getName()."</b>)";
			    }else
			        $msg.= " with no database";
		        STCheck::echoDebug("container", $msg);
		        //st_print_r()
		        if(	isset($get_vars["table"]) &&
		            $get_vars["table"] != ""		)
		        {
		            $msg= "     on table     <b>".$get_vars["table"]."</b>";
		        }elseif(typeof($this->tableContainer, "STObjectContainer"))
		            $msg= "with no explicit <b>table</b>";
		        else
		            $msg= "with no table";
		        STCheck::echoDebug("container", $msg);		        
		        if(	isset($get_vars["action"]) &&
		            $get_vars["action"] != ""		)
		        {
		            $msg= "    and by action <b>".$get_vars["action"]."</b>";
		        }else
		            $msg= "with unknown action";
	            STCheck::echoDebug("container", $msg);
	            echo "<br />";
			}
			//$this->tableContainer->createContainer();
			if(isset($get_vars["download"]))
			{
				$oTable= &$this->tableContainer->getTable();
				$download= new STDownload($this->db, $oTable);
				$download->execute();
			}
			
			$msgHandling= $this->createMessages($onError);
			$result= $msgHandling->getMessageId();
			if($result=="NOERROR")
			{
			    $result= $this->tableContainer->execute($this, $onError);
				$msgHandling->setMessageContent($result);
				$msgHandling->setDummyMessageId($result);
				$endScript= $msgHandling->getMessageEndScript();
				$this->tableContainer->appendObj($endScript);

				if($result!="FORWARDTtoADDRESS")
					$this->addObj($this->tableContainer->getHead($this->getTitle()));
				$this->addObj($this->tableContainer);
			}else
			{
				$msgHandling->setErrorScript("window.location.back()");
				$body= new BodyTag();
					$body->addObj($msgHandling->getMessageEndScript());
				$this->addObj($body);
			}
			if(STCheck::isDebug("test"))
				$__global_finished_SiteCreator_result= $result;
			return $result;
		}
		/**
		 * returning head tag with content
		 *
		 * @return Tag head-tag
		 */
		function &getHead()
		{
			$head= &$this->getElementByTagName("head");
			return $head;
		}
		/**
		 * returning body tag with content
		 *
		 * @return Tag body-tag
		 */
		 function &getBody()
		 {
		 	$body= &$this->getElementByTagName("body");
		 	return $body;
		 }
		function addObjBehindProjectIdentif(&$tag)
		{
			$this->aBehindProjectIdentif[]= &$tag;
		}
		function addBehindProjectIdentif($tag)
		{
			$this->aBehindProjectIdentif[]= &$tag;
		}
		function addObjBehindTableIdentif(&$tag)
		{
			$this->aBehindTableIdentif[]= &$tag;
		}
		function addBehindTableIdentif($tag)
		{
			$this->aBehindTableIdentif[]= &$tag;
		}
		function addObjBehindHeadLineButtons(&$tag)
		{
			$this->aBehindHeadLineButtons[]= &$tag;
		}
		function addBehindHeadLineButtons($tag)
		{
			$this->aBehindHeadLineButtons[]= &$tag;
		}
		function setBackButtonValue($name)
		{
			$this->sBackButton= $name;
		}
		/*function setMainTable(&$table, $bAdmin= true)
		{
			if(!typeof($table, "STDbTable", "stdbtablecontainer"))
			{echo get_class($table)."<br />";
				echo "<b>ERROR:</b> first parameter in STUser::setMainTable() ";
				echo "must be an object from class STDbTable or STDbTableContainer";
				exit;
			}if(!is_bool($bAdmin))
			{
				echo "<b>ERROR:</b> second parameter in STUser::setMainTable() must be an boolean";
				exit;
			}
			$this->aMainTable["table"]= &$table;
			$this->aMainTable["admin"]= $bAdmin;
		}*/
		function setMainMenueButtonValue($name)
		{
			$this->sBackButton= $name;
		}
		function setBackButtonAddress($address)
		{
			if($address)
				$this->bBackButton= true;
			else
				$this->bBackButton= false;
			$this->backButtonAddress= $address;
		}
		function getBackButton($get_vars)
		{
			if(!$this->bBackButton)
				return null;


			$get= new STQueryString();
			if($get_vars["action"]!=STLIST)
			{
				$get->update("stget[action]=".STLIST);
				$get->delete("stget[link][VALUE]");
				$backButtonAddress= $get->getStringVars();
			}
			if($this->backButtonAddress)
				$backButtonAddress= $this->backButtonAddress;
			if($backButtonAddress)
			{
  				$table= new TableTag();
  					$table->width("100%");
  					$tr= new RowTag();
  						$td= new ColumnTag(TD);
  							$td->align("right");
  							$button= new ButtonTag("backButton");
  								$button->add($this->sBackButton);
  								$button->onClick("javascript:location='".$backButtonAddress."'");
  							$td->add($button);
  						$tr->add($td);
  					$table->add($tr);
				return $table;
			}
			return null;
		}
		function getContainerAddress($containerName)
		{
			return $this->tableContainer->aContainerAdress[$containerName];
		}
		public function setDefaultCssLink($href, $media= "all", $title= "protokoll default Stylesheet")
		{
		    $this->bUseOnlyDefaultCssLinks= true;
		    $this->setCssLink($href, $media, $title);
		}
		public function setCssLink($href, $media= "all", $title= "protokoll default Stylesheet")
		{
		    if($media == "all")
		        $this->aDefaultCssLink= array();
		    else
		        $this->aDefaultCssLink[$media]= array();
			$this->aDefaultCssLink[$media][]= array( "href"=>	$href,
											         "title"=>	$title	);
		}
		public function addCssLink($href, $media= "all", $title= "protokoll default Stylesheet")
		{
		    $this->aDefaultCssLink[$media][]= array( "href"=>	$href,
		                                             "title"=>	$title	);
		}
		function getCssLinks()
		{
		    $aLinks= array();			
			if($this->tableContainer->needDefaultCssLinks())
			{
				foreach($this->aDefaultCssLink as $media => $mediaLinks)
				{
				    foreach($mediaLinks as $link)
				        $aLinks[]= STQueryString::getCssLink($link["href"], $media, $link["title"]);
				}
			}
			if(!$this->bUseOnlyDefaultCssLinks)
			{
    			$aContainerLinks= $this->tableContainer->getCssLinks();
    			foreach($aContainerLinks as $link)
    			    $aLinks[]= $link;
			}
			return $aLinks;
		}
		public function addJavaScriptLink($src, $type= null, $title= "protokoll default JavaScript")
		{
			if(!isset($type))
				$type= "text/javascript";
			$sLink= array( "src"  =>	$src,
						   "type" =>	$type,
						   "title"=>	$title	);
			$this->aJavaScriptLinks[]= $sLink;
		}
		public function getJavaScriptLinks()
		{
			$aLinks= $this->tableContainer->getJavaScriptLinks();
			foreach($this->aJavaScriptLinks as $link)
				$aLinks[]= STQueryString::getJavaScriptTag($link["src"], $link["type"]);
			return $aLinks;
		}
		// deprecatet wird in STObjectContainer verschoben
		function chooseInTable($bChoose)
		{
			$this->bChooseInTable= $bChoose;
		}
		function setAccessForColumnsInTable(&$oTable, &$oList)
		{
			STCheck::is_warning(1, "", "");
			echo "wrong function access in STSiteCreator<br />";
			echo "function now in STObjectContainer<br />";
			exit;
		}
		function &getMainTable()
		{
			return $this->tableContainer->oMainTable;
		}
		function &getNavigationTable($tableDisplayName= null)
		{
			Tag::alert(!$tableDisplayName&&count($this->tableContainer->aNavigationTables)>1,
							"STDbSiteCreator::getNavigationTable()", "more then one navigation-table in container found");

			if(!$tableDisplayName)
				$tableDisplayName= $this->tableContainer->aNavigationTables[0]["tableName"];
			foreach($this->tableContainer->aNavigationTables as $key=>$content)
			{
				if($content["tableName"]==$tableDisplayName)
					return $this->tableContainer->aNavigationTables[$key]["list"];
			}
			if(Tag::isDebug())
			{
			    $set= "no navigation-table in container set";
					if($tableDisplayName)
			        $set= "table-display-name ".$tableDisplayName." not set in aktual container";
					STCheck::is_warning(1, "STDbSiteCreator::getNavigationTable()", $set);
					//st_print_r($this->tableContainer->aNavigationTables,2);
			}
			return null;
		}
		function noChoise($table)
		{
			if(typeof($table, "MUDbTable"))
				$table= $table->getName();
			$this->aNoChoise[]= $table;
		}
		function onTableRequireSite($tableName, $site)
		{
			$require= array();
			$require["site"]= $site;
			$require["action"]= "require";
			$this->uRequireSites[$tableName]= $require;
		}
		function setMessageContent($action, $table, $error= null, $errorMessage= null)
		{
			if($error===null)
			{echo $table."<br />";
				$error= $action;
				$errorMassage= $table;echo $errorMassage."<br />";
				if(!isset($this->asError["all"]))
					$this->asError["all"]= array();
				$this->asError["all"][$error]= $errorMassage;
				return;
			}
			if($errorMessage===null)
			{
				$errorMessage= $error;
				$error= $table;
				$table= $action;
				if(!isset($this->asError[$table]))
					$this->asError[$table]= array();
				$this->asError[$table][$error]= $errorMessage;
				return;
			}
			$action= strtolower(trim($action));
			if(!isset($this->asError[$action]))
				$this->asError[$action]= array();
			if(!isset($this->asError[$action][$table]))
				$this->asError[$action][$table]= array();
			$this->asError[$action][$table][$error]= $errorMessage;
		}
		// action darf nicht delete sein
		function callback($action, $tableName, $columnName, $callbackFunction= null)
		{
			if(!$callbackFunction)
			{
				$callbackFunction= $columnName;
				$columnName= "mysql_statement";
			}
			if(!isset($this->aCallbacks[$action]))
				$this->aCallbacks[$action]= array();
			if(!isset($this->aCallbacks[$action][$tableName]))
				$this->aCallbacks[$action][$tableName]= array();
			$this->aCallbacks[$action][$tableName][$columnName]= $callbackFunction;
		}
	function &getContainer(string|null $containerName= null, string|null $className= null, string|null $fromContainer= null)
	{
		global	$_selftable_first_main_database_name;

		if( $containerName &&
			$this->tableContainer->getName() == $containerName	)
		{
			return $this->tableContainer;
		}
		if(	$containerName
			and
			!$fromContainer
			and
			!STDatabase::existDatabaseClassName($className)	)
		{
			STCheck::alert(	!$_selftable_first_main_database_name, "STSiteCreator::getContainer()",
							"first pulled container must be for an database-object"						);
			if(!$className)
				$className= "STObjectContainer";
			$fromContainer= $_selftable_first_main_database_name;
		}
		if(STCheck::isDebug())
		{
			STCheck::alert($containerName && !STBaseContainer::existContainer($containerName) && !$className, "STSiteCreator::getContainer()",
								"on first call of getContainer() for '$containerName' second parameter must be an defined class-name");
		}
		$newContainer= &STBaseContainer::getContainer($containerName, $className, $fromContainer);
		if(!$this->sFirstTableContainerName)
		{
			$this->tableContainer= $newContainer;
			$this->setMainContainer($newContainer);
		}
		if( isset($newContainer) &&
		    !$_selftable_first_main_database_name )
		{
			$db= $newContainer->getDatabase();
			if(isset($db))
			   $_selftable_first_main_database_name= $db->getName();
		}
		return $newContainer;
	}
	function getContainerName()
	{
		global $HTTP_GET_VARS;

		if(isset($HTTP_GET_VARS["stget"]["container"]))
			$containerName= $HTTP_GET_VARS["stget"]["container"];
		else
			$containerName= $this->tableContainer->getName();
		return $containerName;
	}
	/*function &getOlderContainer()
	{
		global $HTTP_GET_VARS;

		$oldContainerName= $HTTP_GET_VARS["stget"]["older"]["stget"]["container"];
		if(	!$oldContainerName
			and
			$HTTP_GET_VARS["stget"]["container"]
			and
			$this->sFirstTableContainerName!=$HTTP_GET_VARS["stget"]["container"]	)
		{
			$oldContainerName= $this->sFirstTableContainerName;
		}
		$container= &STDbTableContainer::getContainer($oldContainerName);
		return $container;
	}*/
	function getAction()
	{
		$container= &$this->getContainer();
		return $container->getAction();
	}
	function &getTable(string $tableName= null, string|bool $sContainer= null, bool $bEmpty= false)
	{
		return $this->getContainer()->getTable($tableName, $sContainer, $bEmpty); 
	}
	function getTableName()
	{
		$container= &$this->getContainer();
		$sRv= $container->getTableName();
		return $sRv;
	}
	function getContainerIdentification()
	{
		$this->getContainer();//setzt den $this->tableContainer
		return $this->tableContainer->getIdentification();
	}
	public function install()
	{
		$this->bDoInstall= true;
		$this->installDbTables();
		$this->installContainer();
	}
	protected function installDbTables()
	{
		$this->bDoInstall= true;
		$containers= STBaseContainer::getAllContainerNames();
		foreach($containers as $containerName)
		{
			$obj= &STBaseContainer::getContainer($containerName);
			if(typeof($obj, "STObjectContainer"))
			{
			    STCheck::echoDebug("install", "<b>install</b> database tables from container ".get_class($obj)."($containerName)");
				$obj->installDbTables();
				STCheck::echoDebug("install", "tables from ".get_class($obj)."($containerName) is installed");
			}
		}
	}
	protected function installContainer()
	{
		$this->bDoInstall= true;
		global	$global_boolean_installed_objectContainer;

		$bInstalled= false;
		$containers= STBaseContainer::getAllContainerNames();
		foreach($containers as $containerName)
		{
			$obj= &STBaseContainer::getContainer($containerName);
			if(typeof($obj, "STObjectContainer"))
			{
			    STCheck::echoDebug("install", "<b>install</b> container ".get_class($obj)."($containerName)");
				$obj->setExternSiteCreator($this);
				$obj->doContainerInstallation();
				$bInstalled= true;
				STCheck::echoDebug("install", "container ".get_class($obj)."($containerName) is installed");
			}
		}
		$global_boolean_installed_objectContainer= $bInstalled;
		if(!$bInstalled)
			STCheck::echoDebug("install", "no container to install be set");
	}
	/**
	 * sorting order of links
	 * inside array $global_selftable_test_links.
	 * entry STINSERT not implemented, because thats only for information
	 * which last entry was inserted
	 * @var array $aTestTypes
	 */
	private array $aTestTypes= array("back_tables", "edit", "table", "action", "container_back", "container");
	private string $reportFilename= "selftable_test_report.txt";
	/**
	 * testing all containers and tables
	 * with <code>STCheck::debug("test")</code>
	 */
	public function testing()
	{
		global $global_selftable_test_links;
		global $__global_finished_SiteCreator_result;
		global $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION;
		global $global_selftable_testing_file_warnings;

		$report= "";
		$query= new STQueryString();
		//$query->update("set=5");
		$testdebug= $query->getParameterValue("testdebug");
		$status= $query->getParameterValue("testdebug", "status");
		//STCheck::end_outputBuffer(false);// flush first normal output buffer
		if(	isset($__global_finished_SiteCreator_result) &&
			(	$__global_finished_SiteCreator_result === "NOERROR" ||
				$__global_finished_SiteCreator_result === "BOXDISPLAY" ||
				$__global_finished_SiteCreator_result === "EMPTY_RESULT"	) &&
			(	!isset($status) ||
				$status !== "finished"	)											)
		{
			$sorted_selftable_test_links= array();
			// Sort keys according to the order in $this->aTestTypes
			foreach ($this->aTestTypes as $orderKey)
			{
				if (isset($global_selftable_test_links[$orderKey]))
					$sorted_selftable_test_links[$orderKey] = $global_selftable_test_links[$orderKey];
			}
			$bNew= false;
			//$nMaxEditLinks= 1;
			$script = pathinfo($_SERVER["SCRIPT_FILENAME"]);
			if(isset($testdebug))
			{
				$type= $testdebug['link-type'];
				if($testdebug['status'] == "finished")
					$bNew= true;
			}else
				$bNew= true;

			$bFinished= false;
			if($bNew)
			{
				reset($sorted_selftable_test_links);
				$type= key($sorted_selftable_test_links);						
				reset($sorted_selftable_test_links[$type]);

				$testdebug= array();
				$testdebug['start']= time();
				$testdebug['status']= "running";
				$testdebug['link-type']= $type;
				$testdebug['link-class']= "STChoose-menue-button"; //should be first link class
				$testdebug['last-insert']= null;
				/**
				 * if 'test' entry is true, the double update test will be activated
				 * to update table row back to original values in next update.
				 * if 'secondRun' entry is true, the second run of the update will be performed.
				 */
				$testdebug['DoubleUpdate']= array(	'test' => "false",
													'secondRun' => "false"	);

				$testdebug['faults']= false;
				$this->resetDebugValues($testdebug);

				$report= "\n\n";
				$report.= " ****************************************\n";
				$report.= " ***  new DBSelfTables test started\n";
				$report.= " ***  on ".date("d.m.Y H:i:s")."\n";
				$report.= " ***  file {$script['basename']}\n";
				$report.= " ***\n";
				$report.= " ***\n";
				$report.= "\n";

			}elseif(trim($testdebug['containers']) == "")
				$this->resetDebugValues($testdebug);

			// report testing steps forcast
			// if debugging step was (4) - insert new entry
			//                   or  (7) - update entry
			// link made over javascript function
			// no increasing was made, do now
			if(	(	$testdebug['step'] == 4 ||
					$testdebug['step'] == 7		) &&
					!isset($sorted_selftable_test_links['action']['function'])	)
			{// action was done
				$testdebug['link-type']= "action";
				$testdebug['step']++;
			}	
			if($testdebug['step'] == 0)
			{
				$table= $this->getTableName();
				if(trim($table) != "")
				{
					$testdebug['step']= 1;
					++$testdebug['tab_count'];
				}
			}
			/**
			 * existing step cases:
			 *	case 0 -> go to first table-button listing
			 *  case 1 -> go to insert/update box to test backbutton
			 *  case 2 -> display item box to test back-button
			 *  case 3 -> show table listing again
			 *  case 4 -> go to item box for new entry
			 * 	case 5 -> insert new entry done, go back to table listing
			 *  case 6 -> go to update box
			 * 	case 7 -> display item box to update entry
			 *  case 8 -> update entry done, go back to table listing
			 * 	case 9 -> show table listing to delete entry inserted before
			 *  case 10 -> delete entry done, go back to table listing
			 * 	case 11 -> go to table listing for next table
			 */
			$this->createContainerReport($testdebug['step']);
			/**
			 * AFTER method createContainerReport() see in WATCH window
			 * 		$this->report
			 * 			 		['container']	- name of container
			 * 					['table']		- name of table
			 * 					['action']		- current action of table
			 * 					['step']		- current step in test
			 * 			 		['description']	- description of current step
			 * 
			 * 		$testdebug['progress']
			 * 					['onTableTagCount']		- count of seen table in current container begin by 0
			 * 					['backbutton-test']		- first step is back-button tested
			 * 					['onEditLinkCount']+1	- current step in edit ##link (STINSERT, STUPDATE) (res:2 = done)
			 * 					['onEditDeleteCount']+1	- current step in edit ##delete (STDELETE) (res:1 = done)
			 * 
			 * 		$sorted_selftable_test_links['edit']
			 * 					['###link']		- array with links to edit (STINSERT, STUPDATE)
			 * 					['###delete']	- array with links to delete (STDELETE)
			 */

			if(	$__global_finished_SiteCreator_result === "NOERROR" ||
				$__global_finished_SiteCreator_result === "BOXDISPLAY"	) // ||
			//	$__global_finished_SiteCreator_result === "EMPTY_RESULT"	)
			{
				// Check if there are no back_tables or action links
				if(	!isset($sorted_selftable_test_links['back_tables']['###action']) &&
					!isset($sorted_selftable_test_links['action'])			)
				{
					if( $testdebug['step'] == 0 ||		//  0	- go to first table listing (only table-buttons are displayed)
						$testdebug['step'] == 11	)	// 11	- go to table listing for next table
					{ // [0][11] Pos. beginning of tables 
						//       0 - go to first table listing (only table-buttons are displayed)
						//      11 - go to table listing for next table
						$link= $this->makeNextTableContainer_Test($testdebug, $sorted_selftable_test_links, $query);
					}else
					{ // [1][3][6] Pos. show table listing STListBox
						//       1 - go to insert/update box to test backbutton
						//       3 - go to insert box again
						//       6 - go to update box
						//       9 - delete inserted before
						$link= $this->makeTableListing_Test($testdebug, $sorted_selftable_test_links, $query);
					}
				}else
				{ // [2][4][5][7][8] Pos. show STItemBox
					//       2 - go Back-Button from insert box
					//       4 - insert new entry
					//       5 - insert done go back to table listing
					//       7 - update inserted before
					//       8 - update done go back to table listing
					//      10 - delete done go back to table listing
					$link= $this->makeTableAction_Test($testdebug, $sorted_selftable_test_links, $query);
				}
				if( $testdebug['step'] == 8 &&
					$testdebug['DoubleUpdate']['test'] == "true" &&
					$testdebug['DoubleUpdate']['secondRun'] == "true"	)
				{ // steps are now normal again
					$testdebug['DoubleUpdate']['test']= "false";
					$testdebug['DoubleUpdate']['secondRun']= "false";
				}
				if	($testdebug['table'] != $this->report['table'] ||
					(	$testdebug['step'] >= 11 &&
						$testdebug['tab_count'] >= $testdebug['tables']	)	)
				{ // new next table
					$testdebug['table']= $this->report['table'];
					if($testdebug['tab_count'] >= $testdebug['tables'])
					{
						if(	$testdebug['containers'] > 0 &&
							$testdebug['cont_count'] < $testdebug['containers']	)
						{ // more containers to test
							$testdebug['cont_count']++;
							$this->pushDebugToOlder($testdebug);
							$testdebug['containers']= null;
							$testdebug['step']= 0; // go to first table listing of next container
							$testdebug['link-type']= "container_link";
							$link= $sorted_selftable_test_links['container']['###link'][$testdebug['cont_count']];
							
						}else
						{
							if(isset($sorted_selftable_test_links['back_tables']['###container']))
							{ // go back to older container
								$this->restoreDebugOlder($testdebug);
								$testdebug['step']= 10; // go to table listing for next table
								$testdebug['link-type']= "link";
								$link= $sorted_selftable_test_links['back_tables']['###container'];
							}else
								$bFinished= true;
						}
					}else
						++$testdebug['tab_count'];
				}
				// to get last inserted PK, write containr report after localize new values
				$sErrorOutput= STCheck::end_outputBuffer("test");
				if(count($global_selftable_testing_file_warnings) > 0)
				{
					foreach($global_selftable_testing_file_warnings as $warning)
						$sErrorOutput.= $warning."\n";
					$sErrorOutput.= "\n\n";
					$global_selftable_testing_file_warnings= array();
				}
				$report.= $this->writeContainerReport($testdebug, $sErrorOutput);

				$type= $testdebug['link-type'];
				if(	$type == "link" || $type == "edit"	)
				{ // otherwise the increasing was made in make_XXX_Test() functions
					if(	$testdebug['DoubleUpdate']['test'] == "true" &&
						$testdebug['DoubleUpdate']['secondRun'] == "false" &&
						$testdebug['step'] == 9 					)
					{// update was done and should updated back in a previous run
						$testdebug['step']= 6;
						$testdebug['DoubleUpdate']['secondRun']= "true";
					}else
						$testdebug['step']++;
				}
				$output= false;
				if( isset($HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION) &&
					$HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION != ""		)
				{
					$exp= explode("/", $HTML_CLASS_DEBUG_CONTENT_CLASS_FUNCTION);
					if(count($exp) > 1) // it exists more than debug('test')
						$output= true;
				}
				if($output)
				{
					echo "<pre>";
					showLine();
					echo "Current working directory: " . getcwd();
					echo "nextLink: $link<br />";
					if(!is_array($sorted_selftable_test_links))
						echo "<br /><br />";
					st_print_r($sorted_selftable_test_links,2);
					echo "new testdbug array:";
					st_print_r($testdebug, 2);
					if(!is_array($testdebug))
						echo "<br /><br />";
					echo "next stget query string:";
					$stget= $query->getArrayVars("stget");
					st_print_r($stget, 8);
					if(!is_array($stget))
						echo "<br /><br />";
					echo "</pre>";
				}
			}else
			{ 
				$bFinished= true; 
				$testdebug['status']= "finished";
			}

			if($bFinished)
				$report.= $this->writeEndTimeReport($testdebug);					

			if(	$__global_finished_SiteCreator_result === "NOERROR" ||
				$__global_finished_SiteCreator_result === "BOXDISPLAY"	)
			{
				if($bFinished)
				{
					$query->update("testdebug[status]=finished");
					$link= "alert('Test finished'); ";
					$link.= "location.href='".$query->getUrlParamString()."'";
				}else
				{
					$params= array( 'testdebug' => $testdebug );
					$query->update($params);
					if(	$type == "link" ||
						$type == "container_link"	)
					{
						$link= "window.location='$link".$query->getUrlParamString()."'";
					}elseif($type == "edit")
					{
						$link= $query->update($link);
						$link= "window.location='$link".$query->getUrlParamString()."'";
					}// by type action no update of parameters can be made, because link is made over javascript function
				}
				$script= new JavaScriptTag();
					$script->add("setTimeout(function(){ $link; }, 1);");
				$body= $this->getBody();
				$body->add($script);
			}
		}else
		{
			if( $status != "finished" )
			{
				$this->createContainerReport($testdebug['step']);
				$sErrorOutput= STCheck::end_outputBuffer("test");
				if(trim($sErrorOutput) != "")
				{
					$report.= "\n\n";
					$report.= " ****************************************\n";
					$report.= " ***  ERROR: on Ending of test\n";
					$report.= "\n";
					$report.= $sErrorOutput;
					$report.= "\n\n\n\n";
				}
			}else
			{ // on finished do not output any crutial string with maybe open pre-tag
				$sErrorOutput= STCheck::end_outputBuffer("test");
				//echo $sErrorOutput;
			}

		}

		if(file_put_contents($this->reportFilename, $report, FILE_APPEND) === false)
		{
			echo "<br /> ERROR: cannot write file {$this->reportFilename}<br />";
			exit();
		}
	}
	private array $aDebugShiftVars= array(	"step", "container", "table", "oupval", "tables",
											"containers", "cont_count", "tab_count", "progress", "older"	);
	/**
	 * reset all debug related values from parameter array
	 */
	private function resetDebugValues(array &$testdebug)
	{
		global $global_selftable_test_links;

		$query= new STQueryString();
		$step= 0;
		$action= $query->getParameterValue("stget", "action");
		if( isset($action) &&
			$action != ""	)
		{
			$step= 1;
		}
		$testdebug['step']= $step;
		$testdebug['container']= $this->getContainerName();
		$testdebug['table']= $this->getTableName();
		$testdebug['oupval']= array(); // old update values
		$testdebug['tables']= $global_selftable_test_links['table']['count'];
		$containers= 0;
		if(isset($global_selftable_test_links['container']))
			$containers= $global_selftable_test_links['container']['count'];
		$testdebug['containers']= $containers;
		$testdebug['cont_count']= 0; // on beginning step define also whether the first shows an container or container listing
		$testdebug['tab_count']= $step; // on beginning step define also whether the first shows an table or table listing
		$testdebug['progress']= array();
		$testdebug['progress']['backbutton-test']= "false";
		$testdebug['progress']['onTableTagCount']= -1;
		$testdebug['progress']['onEditLinkCount']= -1;
		$testdebug['progress']['onEditDeleteCount']= -1;
		$testdebug['progress']['onContainerLinkCount']= -1;
	}
	/**
	 * push debug content to new parameter layer
	 * 
	 * @param bool $bOnlyLimitation push only limitation link up to older container.<br />
	 *                                (default is to push all beside limit and link values)
	 */
	private function pushDebugToOlder(array &$testdebug)
	{
		$older= array();
		foreach($testdebug as $param => $value)
		{
			if(in_array($param, $this->aDebugShiftVars))
				$older[$param]= $value;
		}
		$testdebug['older']= $older;
		$this->resetDebugValues($testdebug);			
	}
	private function restoreDebugOlder(array &$testdebug)
	{
		$older= $testdebug['older'];
		foreach($this->aDebugShiftVars as $param)
		{
			if(isset($testdebug[$param]))
				unset($testdebug[$param]);
			if(isset($older[$param]))
				$testdebug[$param]= $older[$param];
		}
	}
	private function makeNextTableContainer_Test(array &$testdebug, array $sorted_selftable_test_links, STQueryString &$query) : string
	{
		// ( 0) - go to first table listing (only table-buttons are displayed)
		// (11) - go to table listing for next table
		if( $testdebug['step'] == 11 &&
			isset($sorted_selftable_test_links['edit']['###link_container'][$testdebug['progress']['onContainerLinkCount']+1])	)
		{
			$testdebug['last-insert']= null;
			$testdebug['progress']['onContainerLinkCount']++;
			$link= $sorted_selftable_test_links['edit']['###link_container'][$testdebug['progress']['onContainerLinkCount']];
			$link= $this->updateQueryLink($query, $link);
			$this->pushDebugToOlder($testdebug);
			$testdebug['containers']= null;
			$testdebug['step']= -1; // go to first table listing of next container
			$testdebug['link-type']= "container_link";
			return $link;
		}
		$type= "table";
		$testdebug['last-insert']= null;
		$testdebug['progress']['backbutton-test']= "false";
		$testdebug['progress']['onEditLinkCount']= -1;
		$testdebug['progress']['onEditDeleteCount']= -1;
		$buttonClass= $testdebug['link-class'];
		if(isset($sorted_selftable_test_links[$type][$buttonClass]))
		{
			$onAttribute= $sorted_selftable_test_links[$type][$buttonClass];
			$tags= $this->getElementsByClass($buttonClass);
			$tagCount= $testdebug['progress']['onTableTagCount'] + 1;
			if(isset($tags[$tagCount]))
			{
				$elements= $tags[$tagCount]->getElements();
				if(count($elements) > 0)
				{ // if next table is same as first table, increase tagCount
					$container= $this->getContainer();
					$firstTable= $container->getFirstTableName();
					$t= $container->getTable($firstTable);
					$firstTable= $t->getDisplayName();
					$newTableName= trim($elements[0]);
					if($newTableName == trim($firstTable))
						$tagCount++;
				}
			}
			if(isset($tags[$tagCount]))
			{
				$link= $tags[$tagCount]->getAttribut($onAttribute);
				$link= $query->update($link);
				if($testdebug['step'] > 0)
				{// if step is 0, no table was selected
				 // so do not increase the count of tag (onTableTagCount)
				 // because on next beginning when count is 10
				 // button of first table not be displayed
				 // and should begin also on first table tags entry (0)
					$testdebug['progress']['onTableTagCount']= $tagCount;
					$testdebug['step']= 0; // increasing outside by type link to 1 for first step
				}
				$type= "link";
			}else
			{ // no more table-button found, so test is finished
				$link= "";
				if($testdebug['containers'] == 0 ||
				   $testdebug['cont_count'] >= $testdebug['containers']	)
				{
					$bFinished= true;
					$testdebug['status']= "finished";
				}
			}
		}else
		{
			$link= "";
			if(isset($sorted_selftable_test_links["action"]['link']))
			{
				$link= $sorted_selftable_test_links["action"]['link'];
				$link= $this->updateQueryLink($query, $link);
				$testdebug['step']= 1;
				$type= "link";
			}else
			{ // no other table found, so test is finished
				$bFinished= true;
				$link= "";
				$testdebug['status']= "finished";
			}
		}
		$testdebug['link-type']= $type;
		return $link;
	}
	private function makeTableListing_Test(array &$testdebug, array $sorted_selftable_test_links, STQueryString &$query) : string
	{
		global $global_selftable_testing_allowSiteFaults,
			   $global_selftable_testing_file_warnings;

		if(	$testdebug['step'] > 1)
		{// test whether insert/update/delete be allowed from table
			$bInsertLink= true;
			$bUpdateLink= true;
			$bDeleteLink= true;
			$nLinks= count($sorted_selftable_test_links['edit']['###link']);
			if($nLinks < 2)
			{
				if($nLinks == 1)
				{
					$query2= new STQueryString();
					$query2->update($sorted_selftable_test_links['edit']['###link'][0]);
					$action= $query2->getParameterValue("stget", "action");
					if($action == STUPDATE)
						$bInsertLink= false; // no insert link found
					else
						$bUpdateLink= false; // no update link found
				}else
				{
					$bInsertLink= false; // no insert link found
					$bUpdateLink= false; // and no update link found
				}
			}
			if(!isset($sorted_selftable_test_links['edit']['###delete']))
				$bDeleteLink= false; // no delete link found

			$currentSiteNr= $this->getSiteNumberI($testdebug['step']);// from outside only this number reachable
			if(	$testdebug['step'] == 3 &&
				!$bInsertLink				)
			{
				$testdebug['step']= 6; // show listing before
				$this->report['step']= 6; // update entry
			}
			if($testdebug['step'] == 6)
			{
				if(	$bUpdateLink &&
					!$bInsertLink	)
				{ 		
					if(!isset($global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['update']))
					{
						$testdebug['step']= 9; // show listing before
						$this->report['step']= 9; // delete entry
						$update_error_msg= array();
						$update_error_msg[]= "<b>ERROR</b>: no insert link found for table.";
						$update_error_msg[]=         "         In this case, the update will not be tested.";
						$update_error_msg[]=         "         If you want test this, type after STCheck::debug('test'):";
						$update_error_msg[]=         "         STCheck::no_test_error('$currentSiteNr', 'update' [, &lt;PK&gt;]);";
						$update_error_msg[]=         "         -> update PK if defined, otherwise";
						$update_error_msg[]=         "            for a random entry in table.";
						$update_error_msg[]=		 "         : If update set to random test, update will be done in two steps:";
						$update_error_msg[]=		 "         : 1. string to 'update text (removeable)'";
						$update_error_msg[]=		 "         :    int/float to random value";
						$update_error_msg[]=		 "         : 2. all values back to original values";
						$update_error_msg[]=		 "         :    -> ATTENTION: this can cause problems if first update break by error";
						$global_selftable_testing_file_warnings= array_merge(
											$global_selftable_testing_file_warnings, $update_error_msg);
											
					}else
					{
						$updateArr= $global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['update'];
						$update_warning_msg= array();
						$update_warning_msg[]= "<b>WARNING</b>: no insert link found for table.";
						$pk_msg= 			        "           Update now ";
						if(is_array($updateArr['pk']))
						{
							$column = array_key_first($updateArr['pk']);
							$value= $updateArr['pk'][$column];
							$pk_msg.= "PK '$column' with value '$value'";
						}else
							$pk_msg.= "random PK in table";
						$update_warning_msg[]= $pk_msg.".";
						$global_selftable_testing_file_warnings= array_merge(
											$global_selftable_testing_file_warnings, $update_warning_msg);
					}
				}elseif(!$bUpdateLink)
				{
					$testdebug['step']= 9; // show listing before
					$this->report['step']= 9; // delete entry
				}
			}
			if($testdebug['step'] == 9)
			{
				if(	$testdebug['DoubleUpdate']['test'] == "false" ||
					$testdebug['DoubleUpdate']['secondRun'] == "true"	)
				{
					// all updates be done, so delete old values
					$testdebug['oldUpdateVals']= array();
					$testdebug['DoubleUpdate']['test']= "false";
					$testdebug['DoubleUpdate']['firstRun']= "false";
					//$remove= array(	'testdebug' => array(	'oldUpdateVals' => array()	)	);
					$query->delete("testdebug[oldUpdateVals]");

					if(	$bDeleteLink &&
						!$bInsertLink	)
					{ 
						if(!isset($global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['delete']))
						{
							$testdebug['step']= 11; // go to table listing for next table
							$this->report['step']= 11; // go to table listing for next table
							$delete_error_msg= array();
							$delete_error_msg[]= "<b>ERROR</b>: no insert link found for table.";
							$delete_error_msg[]=         "         In this case, deletion is not tested.";
							$delete_error_msg[]=         "         If you want test this, type after STCheck::debug('test'):";
							$delete_error_msg[]=         "         STCheck::no_test_error('$currentSiteNr', 'delete', &lt;PK&gt;);";
							$global_selftable_testing_file_warnings= array_merge(
											$global_selftable_testing_file_warnings, $delete_error_msg);
							$this->makeNextTableContainer_Test($testdebug, $sorted_selftable_test_links, $query);
							return ""; // no link to return, stay on site
						}else
						{
							$deleteArr= $global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['delete'];
							$also_delete_msg= array();
							$also_delete_msg[]= "<b>WARNING</b>: no insert link found for table.";
							$pk_msg=                 "           Delete now ";
							$column = array_key_first($deleteArr['pk']);
							$value= $deleteArr['pk'][$column];
							$pk_msg.= "PK '$column' with value '$value'";
							$also_delete_msg[]= $pk_msg.".";
							$global_selftable_testing_file_warnings= array_merge(
												$global_selftable_testing_file_warnings, $also_delete_msg);
						}
					}elseif(!$bDeleteLink &&
							$bInsertLink)
					{ // no delete link found, so go to next table
						$lastInsert= array();
						if(is_array($testdebug['last-insert']))
						{
							$lastInsert['column'] = array_key_first($testdebug['last-insert']);
							if($lastInsert['column'] !== null)
								$lastInsert['value']= $testdebug['last-insert'][$lastInsert['column']];
						}
						$delete_warning_msg= array();
						$delete_warning_msg[]= "<b>WARNING</b>: no delete action can be done for last insert.";
						$delete_warning_msg[]=         "           please remove self the last inserted column";
						if(isset($lastInsert['value']))
							$delete_warning_msg[]= "           where PK column '".$lastInsert['column']."' = '".$lastInsert['value']."'.";
						$global_selftable_testing_file_warnings= array_merge(
												$global_selftable_testing_file_warnings, $delete_warning_msg);
						$testdebug['step']= 11; // go to table listing for next table
						$this->report['step']= 11; // go to table listing for next table
						$this->makeNextTableContainer_Test($testdebug, $sorted_selftable_test_links, $query);
						return ""; // no link to return, stay on site
					}
				}else
				{ // double update test, so make update again
					$testdebug['step']= 6; // go to update box
					$this->report['step']= 6; // go to update box
					$testdebug['DoubleUpdate']['secondRun']= "true";
					// get new site number with step 3 which shoud be defined from update
					// (DoubleUpdate secondRun is not active because set by next side access)
					$currentSiteNr= $this->getSiteNumberI(/*step*/3);
					$this->createContainerReport($testdebug['step']); // define new beginning of report
				}
			}
		}

		if(	$testdebug['step'] == 1 || // go to insert/update box to test backbutton
			$testdebug['step'] == 3 || // go to insert box
			$testdebug['step'] == 6	 ) // go to update box
		{// trigger now insert/update/delete link

			if($testdebug['progress']['backbutton-test'] === "true") // if backbutton-test, go back to
			{                                             // first link from where comming
				if(is_array($testdebug['last-insert']))
				{
					// (6) step go to update box
					if($testdebug['DoubleUpdate']['secondRun'] == "false")
						$testdebug['progress']['onEditLinkCount']++;
					$link= $sorted_selftable_test_links['edit']['###link'][$testdebug['progress']['onEditLinkCount']];
					$link= $query->update($link);
					$this->updateQueryLimitation($query, $testdebug);

				}elseif(	$testdebug['step'] == 6 &&
							isset($global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['update'])	)
				{	// (6) step go to update box, no last-insert be defined
					//     do not increase onEditLinkCount, because DoubleUpdate test
					$link= $sorted_selftable_test_links['edit']['###link'][$testdebug['progress']['onEditLinkCount']];
					$link= $query->update($link);
					if(!isset($global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['update']['pk']))
					{ // no PK set, make as next update where set values back
						$testdebug['DoubleUpdate']['test']= "true";
					}else
					{ // PK set, so update only this entry for limitation
						$pkColumns= $global_selftable_testing_allowSiteFaults['ERRORS'][$currentSiteNr]['update']['pk'];
						$testdebug['last-insert']= $pkColumns;
						$this->updateQueryLimitation($query, $testdebug);
					}

				}else
				{ // STINSERT (3) step go to insert box
					$link= $sorted_selftable_test_links['edit']['###link'][$testdebug['progress']['onEditLinkCount']];
					$link= $query->update($link);
				}
			}else
			{
				// (1) step go to insert/update box to test backbutton
				$testdebug['progress']['onEditLinkCount']++;
				$link= $sorted_selftable_test_links['edit']['###link'][$testdebug['progress']['onEditLinkCount']];
				$link= $this->updateQueryLink($query, $link);
			}
		}else
		{// (9) step delete entry
			if(isset($sorted_selftable_test_links['edit']['###delete']))
			{
				$testdebug['progress']['onEditDeleteCount']++;
				$link= $sorted_selftable_test_links['edit']['###delete'][$testdebug['progress']['onEditDeleteCount']];
				$link= $this->updateQueryLink($query, $link);
				$this->updateQueryLimitation($query, $testdebug);
			}else
			{
				$testdebug['step']= 11;
				$this->report['step']= 11;
				$this->report['description']= "go to table listing for next table";
				return $this->makeNextTableContainer_Test($testdebug, $sorted_selftable_test_links, $query);
			}
		}
		$testdebug['link-type']= "link";
		return $link;
	}
	/**
	 * update query with link
	 * but remove before the stget parameter
	 * because otherwise the old settings will be used
	 * 
	 * @param STQueryString $query  query object to update
	 * @param string $link  link to update
	 */
	private function updateQueryLink(STQueryString &$query, string $link) : string
	{
		$query->update("stget=");
		return $query->update($link);
	}
	/**
	 * update query with primary key limitation from last insert
	 * 
	 * @param STQueryString $query  query object to update
	 * @param array $testdebug  testdebug array with last-insert key
	 * @return void
	 */
	private function updateQueryLimitation(STQueryString &$query, array $testdebug) : void
	{
		if( !isset($testdebug['last-insert']) ||
			!is_array($testdebug['last-insert']) || 
			empty($testdebug['last-insert']) 		)
		{
			// if no last-insert defined, cannot set limitation
			// mostly in this case backbutton on step 2 was tested
			// and it need no limitation
			return;
		}
		$table= $query->getParameterValue("stget", "table");
		$column= array_key_first($testdebug['last-insert']);
		$stget= array( "stget" => array( "limit" => array( $table => array())));
		$stget['stget']['limit'][$table][$column]= $testdebug['last-insert'][$column];
		$query->update($stget);
	}
	private function makeTableAction_Test(array &$testdebug, array $sorted_selftable_test_links, STQueryString &$query) : string
	{
		global $global_selftable_test_links;

		if($testdebug['progress']['backbutton-test'] === "false")
		{// STItemBox should test first back-button
			$type= "back_tables";
			$testdebug['progress']['backbutton-test']= "true";
			$link= $this->updateQueryLink($query, $sorted_selftable_test_links[$type]['###action']);
			STCheck::warning(is_bool($link), "no correct back link found", 1);
			$testdebug['step']++;// only for type link or edit steps will be increase later
			$query->update(array( 'testdebug' => $testdebug ));
			$link= "window.location='$link".$query->getUrlParamString()."'";
		}else
		{// test come back from tables where from STItemBox
			// back-button was tested

			if(isset($sorted_selftable_test_links['action']['function']))
			{// test now insert function
				// and create new entry in database
				$link= $sorted_selftable_test_links['action']['function'];
				$type= "action";
				if(	$testdebug['step'] == 7 &&
					$testdebug['DoubleUpdate']['test'] == "true" &&
					!is_array($testdebug['last-insert'])	)
				{
					// item box for update was shown
					// while DoubleUpdate test should done in next step
					// because no insert was done before
					// so set now last-insert to current update PK
					
					// Try to get PK from query parameters first
					$currentQuery = new STQueryString();
					$stgetVars = $currentQuery->getArrayVars();
					
					// Look for limit parameters in stget that contain the PK
					if(isset($stgetVars['stget']['limit'][$this->report['table']]))
					{
						$limitation = $stgetVars['stget']['limit'][$this->report['table']];
						if(is_array($limitation) && count($limitation) > 0)
							$testdebug['last-insert'] = $limitation;
						else
							STCheck::warning(true, "no correct limitation for table '".$this->report['table']."' found"
								." to set last-insert for DoubleUpdate test", 1);
					}else
						STCheck::warning(true, "no correct limitation for table '".$this->report['table']."' found"
							." to set last-insert for DoubleUpdate test", 1);
					$query->update(array( 'testdebug' => $testdebug ));
					$newParams= $query->getUrlParamString();
					$newParams= substr($newParams, 1);
					$newParams= "', '$newParams')";
					$link= str_replace("')", $newParams, $link);
				}

			}else
			{// now entry was inserted correctly
				// and there be defined only an forward link
				// backbutton-test wasn't correct set by insert to false, but do it now
				if(isset($global_selftable_test_links[STINSERT]))
				{
					$pkColumn = array_key_first($global_selftable_test_links[STINSERT]);
					$value= $global_selftable_test_links[STINSERT][$pkColumn];
					$testdebug['last-insert']= array();
					$testdebug['last-insert'][$pkColumn]= $value;
				}else
				{
					if(isset($global_selftable_test_links[STUPDATE]))
					{
						if($testdebug['DoubleUpdate']['test'] == "true" &&
						   $testdebug['DoubleUpdate']['secondRun'] == "false"	)
						{ // DoubleUpdate test should be done in next procedure,
						  // set last-insert from update values
							$testdebug['last-insert']= $global_selftable_test_links[STUPDATE];
						}
						$pkColumn = array_key_first($global_selftable_test_links[STUPDATE]);
					}else
						$pkColumn= array_key_first($testdebug['last-insert']);
					
					//$value= $global_selftable_test_links[STUPDATE][$pkColumn];
					if(!isset($global_selftable_test_links[STUPDATE]))
						$testdebug['progress']['backbutton-test']= "false";
					$query->delete("stget[limit][{$this->report['table']}][$pkColumn]");
				}
				$link= $this->updateQueryLink($query, $sorted_selftable_test_links['action']['link']);
				$query->update(array( 'testdebug' => $testdebug ));
				$type= "link";
			}
		}
		$testdebug['link-type']= $type;
		return $link;
	}	
	
	private function createContainerReport(int $step) : void
	{
		global $__global_finished_SiteCreator_result;

		switch ($step) {
			case 0; // [0] Pos. beginning of Container (only table-buttons are displayed)
				$description= "list only table buttons";
				// 0 -> go to first table listing
				break;
			case 1; // [1] Pos. show table listing STListBox
				$description= "show first table listing";
				// 1 -> go to insert box
				break;
			case 2; // [2] Pos. show STItemBox
				$description= "display item box to test back-button";
				// 2 -> go Back-Button from insert box
				break;
			case 3; // [3] Pos. show table listing STListBox
				$description= "show table listing again";
				// 3 -> go to insert box again
				break;
			case 4; // [4] Pos. show STItemBox
				$description= "display item box to insert new entry";
				// 4 -> insert new entry
				break;
			case 5; // [5] Pos. show link if correct
				if($__global_finished_SiteCreator_result == "NOERROR")
					$description= "insert new entry done, go back to table listing";
				else
					$description= "insert new entry failed, produce ERROR";
				// 5 -> insert done go back to table listing
				break;
			case 6; // [6] Pos. show table listing STListBox
				$description= "show table listing again";
				// 6 -> go to update box
				break;
			case 7; // [7] Pos. show STItemBox
				$description= "display item box to update entry";
				// 7 -> update inserted before
				break;
			case 8; // [8] Pos. show link if correct
				if($__global_finished_SiteCreator_result == "NOERROR")
					$description= "update entry done, go back to table listing";
				else
					$description= "update entry failed, produce ERROR";
				// 8 -> update done go back to table listing
				break;
			case 9; // [9] Pos. delete entry and show link by fault
				$description= "show table listing to delete entry inserted before";
				// 9 -> delete inserted before
				break;
			case 10; // [10] Pos. show link if correct
				if($__global_finished_SiteCreator_result == "NOERROR")
					$description= "delete entry done, go back to table listing";
				else
					$description= "delete entry failed, produce ERROR";
				// 10 -> delete done go back to table listing
				break;
			case 11; // [11] Pos. show table listing STListBox
				$description= "show table listing for next table";
				// 11 -> go to table listing for next table
				break;
			default:
				$description= "UNKNOWN step ($step) found";
				break;
		}
		$this->report['container']= $this->getContainerName();
		$this->report['table']= $this->getTableName();
		$this->report['action']= $this->getAction();
		$this->report['step']= $step;
		$this->report['description']= $description;
	}
	public function getSiteNumber() : string
	{
		$query= new STQueryString();
		$step= $query->getParameterValue("testdebug", "step");
		if($step === null)
			$step= 0; // default step
		return $this->getSiteNumberI($step);
	}
	/**
	 * create site number container/table/steps for report
	 * 
	 * @param int $step  step number of current action
	 * @return string site number
	 */
	private function getSiteNumberI(int $step) : string
	{
		$query= new STQueryString();
		$addDoubleUpdateSign= $query->getParameterValue("testdebug", "DoubleUpdate", "secondRun");
		if($addDoubleUpdateSign === "true")
			$addDoubleUpdateSign= "b";
		else
			$addDoubleUpdateSign= "";
		if(isset($this->generatedSiteNumber))
			return $this->generatedSiteNumber.$this->addStepNr($step).$addDoubleUpdateSign;
		$currentRow= $query->getParameterValue("stget", "firstrow");
		$container= $this->getContainerName();
		$sContHash = "C" . substr(md5($container), 0, 4);
		$table= $this->getTable();
		if(!isset($table))
		{
			$tableName= $this->getTableName();
			if($tableName == "")
				$tableName= "#no-table";
			$sTabNr = "B" . substr(md5($tableName), 0, 4);
		}else
		{
			$sTabNr = $table->getTableNumber();
			$tableName= "no-table";
		}
		$sRv = $sContHash . $sTabNr;
		if(isset($currentRow[$tableName]))
			$sRv .= "R" . $currentRow[$tableName];
		else
			$sRv .= "R0";
		$this->generatedSiteNumber= $sRv;
		$sRv.= $this->addStepNr($step).$addDoubleUpdateSign;
		return $sRv;
	}
	private function addStepNr(int $step) : string
	{
		$sStepNr= "S";
		if($step < 10)
			$sStepNr .= "0";
		$sStepNr .= $step;
		return $sStepNr;
	}
	/**
	 * member variable to report
	 * values for later use
	 * @var array $report
	 */
	private $report= array();
	private function writeContainerReport(array &$testdebug, string $sErrorOutput) : string
	{
		global $__global_finished_SiteCreator_result;

		$container= $this->report['container'];
		$table= $this->report['table'];
		$step= $this->report['step'];
		$siteNr= $this->getSiteNumberI($step);
		$action= $this->report['action'];
		$description= $this->report['description'];
		$this->report= array();
		// correct $sErrorOutput to ASCII-only
		// remove all html-tags
		// --------------------------------------------------------------------------------------------------
		if(trim($sErrorOutput) != "")
		{
			$sErrorOutput= str_replace("&#160;", " ", $sErrorOutput);
			$sErrorOutput= preg_replace('/<b>|<\/b>/i', '*', $sErrorOutput);
			$sErrorOutput= preg_replace('/<\/td>/i', ' ', $sErrorOutput);
			$sErrorOutput= preg_replace('/&nbsp;|&amp;nbsp;|&nbsp;/i', ' ', $sErrorOutput);
			$sErrorOutput= preg_replace('/<br\s*\/?>|<\/tr>|<\/li>|<\/div>|<\/p>|<\/h[1-9]>/i', "\n", $sErrorOutput);
			$sErrorOutput= strip_tags($sErrorOutput);
			$sErrorOutput= preg_replace('/&lt;/i', '<', $sErrorOutput);
			$sErrorOutput= preg_replace('/&gt;/i', '>', $sErrorOutput);
		}
		// --------------------------------------------------------------------------------------------------
		if(	trim($sErrorOutput) === "" &&
			(	$__global_finished_SiteCreator_result == "NOERROR" ||
				$__global_finished_SiteCreator_result == "BOXDISPLAY"	)	)
		{ // no error occurred
			if(!STCheck::isDebug("test.see"))
				return ""; // should not write anything into report
		}else
			$testdebug['faults']= true;

		if(	(	$action == STINSERT ||
				$action == STUPDATE ||
				$action == STDELETE		) &&
			isset($testdebug['last-insert']) &&
			is_array($testdebug['last-insert'])	)
		{
			$pkColumn= array_key_first($testdebug['last-insert']);
			$action.= " PK ".$testdebug['last-insert'][$pkColumn];
		}
		$report= " *******************************************************************************\n";
		$report.= " ***      site Nr: $siteNr\n";
		$report.= " ***    container: $container\n";
		$report.= " ***        table: $table\n";
		$report.= " ***         step: $step for table in container\n";
		$report.= " ***       action: $action\n";
		$report.= " ***  description: $description\n";
		$report.= " ***       result: $__global_finished_SiteCreator_result\n";
		$report.= " ***\n";
		$report.= "\n";
		$report.= "$sErrorOutput\n\n";
		return $report;
	}
	private function writeEndTimeReport(array $testdebug) : string
	{
		$timestamp= time();
		$endtime= date("H:i:s", $timestamp);
		$finishedtime= $timestamp - $testdebug['start'];
		$pattern= "s";
		$item= "sec";
		if($finishedtime > 60)
		{
			$pattern= "i:s";
			$item= "min:sec";
			if($finishedtime > (60*60))
			{
				$pattern= "H:i:s";
				$item= "hour:min:sec";
			}
		}
		$finishedtime= date($pattern, $finishedtime);

		$report= " ***\n";
		$report.= " ***\n";
		if($testdebug['faults'] === true)
			$report.= " ***  Test finished with errors on $endtime\n";
		else
			$report.= " ***  Test finished on $endtime\n";
		$report.= " ***                in $finishedtime $item\n";
		$report.= " ********************************************************************************************************************************************************\n";
		$report.= "\n\n\n\n\n\n\n\n";
		return $report;
	}
}

?>