<?php 

require_once( $_stobjectcontainer );
require_once( $_stframecontainer );

class STUserProjectManagement extends STBaseContainer
{
    /**
     * definition for default images
     * on login screen and navigation bar
     * @var array
     */
    private $image= array();
    private $database= null;
    private $loginMaskDescription= "";
    private $loginMask= null;
    private $accessableProjects= array();
    private $accessibilityString= "";
    private $accessibilityProjectMask= null;
    private $availableSite= null;
    /**
     * prefix path add to all projects inside database     * 
     * @var string
     */
    private $prefixPath= "";
    /**
     * add html tags before execute container
     * @var array
     */
    private $addedContent= array();
    
    public function __construct(string $name, $userDb, string $bodyClass= "ProjectAccessBody")
    {
        STCheck::paramCheck($userDb, 1, "STDatabase");
        
        $this->database= $userDb;
        STBaseContainer::__construct($name, $userDb, $bodyClass);
    }
    /**
     * method to create messages for different languages.<br />
     * inside class methods (create(), init(), ...) you get messages from <code>$this->getMessageContent(<message id>, <content>, ...)</code><br />
     * inside this method depending the <code>$language</code> define messages with <code>$this->setMessageContent(<message id>, <message>)</code><br />
     * see STMessageHandling
     *
     * @param string $language current language like 'en', 'de', ...
     */
    protected function createMessages(string $language)
    {
        STBaseContainer::createMessages($language);
        if($language == "de")
        {
            $this->setMessageContent("loginMaskDescription", "Bitte geben sie hier ihre Benuter-Daten ein:");
            $this->setMessageContent("accessibilityString", "<h1>Zur Verfügung stehende Webapplikationen:</h1>");
            $this->setMessageContent("noUserAccess", "mit diesem User-Namen besteht keine Berechtigung");
            $this->setMessageContent("wrongUserPassword", "Passwort stimmt nicht mit User-Namen &uuml;berein");
            $this->setMessageContent("multipleUser", "mehrdeutingen Benutzernamen in Datenbank gefunden!");
            $this->setMessageContent("externalAuthenticationError", "Unbekannter Fehler by externer Autentifizierung!");
            $this->setMessageContent("noPermission", "Sie haben keinen Zugriff auf diese Daten!");
            $this->setMessageContent("unknownError@", "Unbekannten Fehler-Code ('@') gefunden!");
            $this->setMessageContent("doNewLogin", "Bitte melden sie sich mit einem anderen Benutzer-Namen an.");
            
        }else // otherwise language have to be english "en"
        {
            $this->setMessageContent("loginMaskDescription", "Please insert your Access Data:");
            $this->setMessageContent("accessibilityString", "<h1>Existing Web-Applications:</h1>");
            $this->setMessageContent("noUserAccess", "with this user, no permission given");
            $this->setMessageContent("wrongUserPassword", "password or user name are incorrect");
            $this->setMessageContent("multipleUser", "multiple User name inside database found!");
            $this->setMessageContent("externalAuthenticationError", "Unknown error at external authentication!");
            $this->setMessageContent("noPermission", "you have no permission to this data!");
            $this->setMessageContent("unknownError@", "<b>UNKNOWN</b> Error type ('@') found!");
            $this->setMessageContent("doNewLogin", "Please login with an other user.");            
        }
    }
    public function setLoginMaskDescription(string $description)
    {
        $this->loginMaskDescription= $description;
    }
    public function setAccessibilityProjectString(string $string)
    {
        $this->accessibilityString= $string;
    }
    public function setHomepageLogo(string $address, int $width= null, int $height= 140, string $alt= "DB selftables Homepage")
    {
        $this->image['logo']['img']= $address;
        $this->image['logo']['height']= $height;
        $this->image['logo']['width']= $width;
        $this->image['logo']['alt']= $alt;
    }
    public function setHomepageBanner(string $address)
    {
        $this->image['logo']['banner']= $address;
    }
    public function setNavigationLogo(string $address, int $width= null, int $height= 70)
    {
        $this->image['nav']['img']= $address;
        $this->image['nav']['height']= $height;
        $this->image['nav']['width']= $width;
    }
    public function setNavigationBanner(string $address)
    {
        $this->image['nav']['banner']= $address;
    }
    /**
     * add a prefix path to all projects into database.<br />
     * this can be used for development where the user
     * should jump to the development path and not
     * to the correct original path where the project inside
     * real-time running
     * 
     * @param string $path prefix path add to the normal project path
     */
    public function addClientRootPath(string $path)
    {
        $this->prefixPath= $path;
    }
    public function getDatabase()
    {
        return $this->database;
    }
    protected function init(string $action, string $table)
    {
        
    }
    protected function create()
    {
        //$this->database->verifyLogin("##StartPage");
        STCheck::echoDebug("user", "entering ProjectAccess init function...");
        $cluster= $this->database->getTable("Cluster");
        $selector= new STDbSelector($cluster);
        $selector->select("Cluster", "ID", "ClusterID");
        $selector->select("Project", "ID");
        $selector->select("Project", "Name");
        $selector->select("Project", "Path");
        $selector->select("Project", "Description");//"Description");
        $selector->select("Project", "DateCreation");
        $selector->orderBy("Project", "Name");
        $selector->allowQueryLimitation(false);
        $selector->execute();
        $result= $selector->getResult();
        
        $user= STSession::instance();
        if(STCheck::isDebug("user"))
        {
            $msg= "sessionvar ST_LOGGED_IN is ";
            $loggedin= $user->getSessionVar("ST_LOGGED_IN");
            if(isset($loggedin))
                $msg.= print_r($loggedin, /*return*/true);
            else
                $msg.= "<b>not</b> defined";
            STCheck::echoDebug("user", $msg);
            
            $cluster_membership= $user->getSessionVar("ST_CLUSTER_MEMBERSHIP");
            $space= STCheck::echoDebug("user", "</b> User is currently member of following clusters: ");
            if(is_array($cluster_membership) && count($cluster_membership))
            {
                st_print_r($cluster_membership, 1, $space);
            }else
                st_print_r("<em>NO CLUSTER</em>", 1, $space);
        }
        foreach( $result as $row )
        {
            if(STCheck::isDebug("user"))
                echo "<br />\n";
            $access_msg= "Search whether user has access for Project Nr. ".htmlspecialchars( $row[ 'ID' ] )."): <em>";
            $access_msg.= "<b> - " .htmlspecialchars( $row[ 'Name' ] )."</em> - </b>:";
            if( $user->hasAccess( $row[ 'ClusterID' ], $access_msg ) )
            {
                if( Tag::isDebug() )
                {
                    $msg= "<b>GRANTED</b> (with membership in Cluster(";
                    $msg.= htmlspecialchars( $row[ 'ClusterID' ] )."))";
                    STCheck::echoDebug("user", $msg);
                }
                $this->accessableProjects[ $row[ 'ID' ] ] =
                                        array(
                                            'Name' => $row[ 'Name' ],
                                            'Path' => $row[ 'Path' ],
                                            'Description' => $row[ 'Description' ],
                                            'DateCreation' => $row[ 'DateCreation' ]	);
            }else
                STCheck::echoDebug("user", "<b>DENIED</b>");
        }
        if( Tag::isDebug("user") ) 
            echo "".$this->toString();
    }
    public function toString()
    {
        echo '<br /><br /><br />';
        STCheck::echoDebug("user", "init found following accessable projects:");
        $res  = '<table border="0" bgcolor="black" cellpadding="2" cellspacing="1" >';
        $res .= '<tr><td colspan="3" bgcolor="#E0E0E0">Accessable Projects</td></tr>';
        $res .= '<tr><td bgcolor="white">ID</td><td bgcolor="white">description</td><td bgcolor="white">Path</td></tr>';
        foreach( $this->accessableProjects as $projectID =>$project ){
            $res .= '<tr><td bgcolor="white" colspan="3">'.htmlspecialchars( $project[ 'Name' ] ).'</td></tr>';
            $res .= '<tr><td bgcolor="white">'.$projectID.'</td>';
            $res .= '    <td bgcolor="white">'.htmlspecialchars( $project[ 'Description' ] ).'</td>';
            $res .= '    <td bgcolor="white">'.htmlspecialchars( $project[ 'Path' ] ).'</td>';
            $res .= '</tr>';
        }
        $res .= '</table><br />';
        return $res;
        
    }
    public function hasProjects() : bool
    {
        if(count($this->accessableProjects) > 0)
            return true;
        return false;
    }
    public function getSiteTypeShow() : string
    {
        $available= $this->showAvailableSite();
        return $available['show'];
    }
    private function showAvailableSite()
    {
        if(isset($this->availableSite))
            return $this->availableSite;
        
        $this->createContainer();
        $this->availableSite= array();
        $this->availableSite['access']= false;
        
        $access= STSession::instance();
        if( isset($access) &&
            $access->isLoggedIn()  )
        {
            $this->availableSite['LoggedIn']= true;
            $this->availableSite['access']= true;
        }else
            $this->availableSite['LoggedIn']= false;
            
        $get= new STQueryString();
        $projectID= $get->getUrlParamValue("ProjectID");
        if(!is_numeric($projectID))
            $projectID= 0;
        $this->availableSite['project']= $projectID;
        
        $error= $access->getLoginError();
        if($error == 0)
        {
            $error= $get->getUrlParamValue("ERROR");
            if(!is_numeric($error))
                $error= 0;
        }
        $show= $get->getUrlParamValue("show");
        if( $projectID > 0 &&
            isset($this->accessableProjects[$projectID]) &&
            (   !isset($show) ||
                $show == "frame"   )                            )
        {
            $this->availableSite['show']= "frame";
        }elseif($projectID == 0)
            $this->availableSite['show']= "list";
        elseif(isset($show))
            $this->availableSite['show']= $show;
        elseif( !isset($this->accessableProjects[$projectID]))
        {// user has no access to project, so show login
            $this->availableSite['show']= "list";
            $this->availableSite['access']= false;
            $error= 5;//user has no access to data
        }else
            $this->availableSite['show']= "project";
        $this->availableSite['error']= $error;
        return $this->availableSite;
    }
    public function addObj(&$tag, $showWarning = false)
    {
        $this->addedContent[]= $tag;
    }
    protected function getProjectLink(int $projectID) : string
    {
        $get= new STQueryString();
        $debug= $get->getUrlParamValue("debug");
        $preg= preg_split("/\?/", $this->accessableProjects[$projectID]['Path']);
        $projectAddress= $preg[0];        
        if(preg_match("/^\//", $projectAddress))
            $projectAddress= $this->prefixPath.$projectAddress;
        $projectQueryString= null;
        if(isset($preg[1]))
            $projectQueryString= $preg[1];
        $projectQuery= new STQueryString($projectQueryString);
        if(isset($debug))
            $projectQuery->update("debug=$debug");
        $projectQuery->delete("ProjectID");
        $projectAddress.= $projectQuery->getUrlParamString();
        return $projectAddress;
    }
    public function execute(&$externSideCreator, $onError)
    {
        STBaseContainer::execute($externSideCreator, $onError);  
        $available= $this->showAvailableSite();
        if( STCheck::isDebug() &&
            (   STCheck::isDebug("user") ||
                STCheck::isDebug("access")  )   )
        {
            $dbg= "access";
            if(STCheck::isDebug("user"))
                $dbg= "user";
            $space= STCheck::echoDebug($dbg, "access to site:");
            st_print_r($available,1, $space);
        }
        
        if( STCheck::isDebug() &&
            $available['show'] == "frame"   )
        {
            $msg[]= "by output some text before FRAME, HTML FRAME does not work!";
            $msg[]= "so show available navigation not frame";
            STCheck::write($msg[0]);
            $space= STCheck::write($msg[1]) +86;
            echo "<br>";
            st_print_r($available, 1, $space);
            $div= new DivTag();
                $h1= new H1Tag();
                    $h1->add("Project: ");
                    $a= new ATag();
                        $projectAdr= $this->getProjectLink($available['project']);
                        $a->href($projectAdr);
                        $a->add($projectAdr);
                    $h1->add($a);
                $div->add($h1);
            $div->display();
            $available['show']= "navigation";
        }
        if($available['show'] == "frame")
        {
            if(isset($this->image['nav']['height']))
                $firstFrameHeight= $this->image['nav']['height']+5;
            elseif(isset($this->image['logo']['height']))
                $firstFrameHeight= $this->image['logo']['height']/2+5;
            else
                $firstFrameHeight= 70;
            $frame= new STFrameContainer();
            $frame->framesetRows("$firstFrameHeight,*");
            $frame->setFramePath("?show=navigation&ProjectID=".$available['project']);
            $frame->setFramePath($this->getProjectLink($available['project']));
            $result= $frame->execute($externSideCreator, $onError);
            
            $this->tag= "frameset";
            $this->class("STFrame");
            foreach($frame->inherit as $tag)
            {
                STBaseContainer::append($tag);
            }
            foreach($frame->aNames as $attribute => $value)
                $this->insertAttribute($attribute, $value);
            return $result;
        }
        //st_print_r($available);
        $get= new STQueryString();
        $user= STSession::instance();
        if($available['show'] == "list")
        {
            if(isset($this->image['logo']['img']))
            {
                $table= new st_tableTag();
                    $table->border(0);
                    $table->cellpadding(0);
                    $table->cellspacing(0);
                    $table->width("100%");                    
                    $a= new ATag();
                        $query= $user->getSessionUrlParameter();
                        if($query != "")
                            $query= "?".$query;
                        $userQuery= $get->getUrlParamValue("user");
                        if(isset($userQuery))
                        {
                            $userQuery= "user=".$userQuery;
                            if($query != "")
                                $query.= "&".$userQuery;
                            else
                                $query= "?".$userQuery;
                        }
                        $a->href("index.php".$query);
                        $a->target("_top");
                        $img= new ImageTag();
                            $img->src($this->image['logo']['img']);
                            $img->height($this->image['logo']['height']);
                            $img->width($this->image['logo']['width']);
                            $img->border(0);
                            $img->alt($this->image['logo']['alt']);
                        $a->add($img);
                    $table->add($a);
                    if(isset($this->image['logo']['banner']))
                    {
                        $table->columnBackground($this->image['logo']['banner']);
                        $table->columnHeight($this->image['logo']['height']);
                    }
                    if($user->isLoggedIn())
                    {
                        $div= new DivTag();
                            $logout= $user->getLogoutButton( "Logout" );
                            $div->add($logout);
                            $div->add(br());
                            $div->add("logged In as: ");
                            $b= new BTag();
                                $span= new SpanTag("colorONE");
                                    $span->add($user->getUserName());
                                $b->add($span);
                                $b->add("&nbsp;&nbsp;");
                            $div->add($b);
                            $table->add($div);
                        if(isset($this->image['logo']['banner']))
                        {
                            $table->columnBackground($this->image['logo']['banner']);
                            $table->columnHeight($this->image['logo']['height']);
                        }
                        //$table->width("100%");
                        $table->columnAlign("right");
                    }
                $this->append($table); 
            }
        }elseif($available['show'] == "navigation")
        {
            $get->delete("show");
            $get->delete("ERROR");
            $get->delete("ProjectID");
            //STCheck::debug(TRUE);
            if(isset($this->image['nav']))
                $logo= $this->image['nav'];
            elseif(isset($this->image['logo']))
            {
                $logo= $this->image['logo'];
                if(isset($logo['width']))
                    $logo['width']= $logo['width']/2;
                $logo['height']= $logo['height']/2;
            }else
                $logo= array();
            if(!isset($logo['alt']))
                $logo['alt']= "DB selftables Homepage";
            
            $table= new st_tableTag();
                $table->border(0);
                $table->cellpadding(0);
                $table->cellspacing(0);
                $table->width("100%");  
                $a= new ATag();
                    $a->href("index.php".$get->getUrlParamString());
                    $a->target("_top");
                    $img= new ImageTag();
                        if(isset($logo['img'])) 
                            $img->src($logo['img']);
                        if(isset($logo['width']))
                            $img->width($logo['width']);
                        if(isset($logo['height']))
                            $img->height($logo['height']);
                        $img->border(0);
                        $img->alt($logo['alt']);
                    $a->add($img);
                $table->add($a);
                if(isset($logo['banner']))
                {
                    //st_print_r($logo);
                    $table->columnBackground($logo['banner']);
                    $table->columnHeight($logo['height']);
                }
                $navSpan= new SpanTag();
                    $script= new JavaScriptTag();
                        $function= new jsFunction("myOnSubmit", "myTarget");
                            $function->add("top.location.href = myTarget;");
                            $function->add("return false;");
                        $script->add($function);
                    $navSpan->add($script);
                    $form= new FormTag();
                        $form->name("myForm");
                        $form->onSubmit("myOnSubmit( myForm.mySelect.value )");
                        $form->add("Web-Aplikation: ");
                        $select= new SelectTag();
                            $select->onChange("top.location.href=this.value;");
                            $select->name("mySelect");
                        foreach( $this->accessableProjects as $projectID => $project )
                        {
                            $option= new OptionTag();
                                $get->update("ProjectID=$projectID");
                                $option->value($get->getUrlParamString());
                                $option->add($project['Name']);
                            if($projectID == $available['project'])
                                $option->selected();
                            $select->add($option);
                        }
                        $form->add($select);
                        $input= new InputTag("button");
                            $input->type("submit");
                            $input->value("GO&nbsp;&gt;&gt;");
                        $form->add($input);
                    $navSpan->add($form);
                $table->add($navSpan);
                if(isset($logo['banner']))
                {
                    $table->columnBackground($logo['banner']);
                    $table->columnHeight($logo['height']);
                }
                $table->columnWidth("100%");
                $table->columnAlign("center");
                $table->columnClass("fontSmaller");
                $table->columnStyle("font-weight:bold;");
                $table->columnNowrap();
                $div= new DivTag();
                if($available['LoggedIn'])
                {                        
                    $logout= $user->getLogoutButton( "Logout" );
                    $div->add($logout);
                    $div->add(br());
                    $div->add("&#160;logged&#160;In&#160;as:&#160;");
                    $b= new BTag();
                        $span= new SpanTag("colorONE");
                            $span->add($user->getUserName());
                        $b->add($span);
                        $b->add("&nbsp;&nbsp;");
                    $div->add($b);
                }else
                {
                    $login= $user->getLogoutButton("Login");
                    $div->add($login);
                    $div->add(br());
                    $div->add(br());                        
                }
                $table->add($div);
                $table->width("100%");
                $table->columnAlign("right");
            $this->append($table); 
        }
        foreach($this->addedContent as $tag)
        {
            $this->appendObj($tag);
        }
        if( $available['show'] != "navigation" &&
            (   !$available['LoggedIn'] ||
                (   $available['project'] != 0 &&
                    !isset($this->accessableProjects[$available['project']])    )   )   )
        {
            $this->getLoginMask($available);  
            $this->appendObj($this->loginMask);
        }
        if( $available['show'] == "list" &&
            $this->hasProjects()            )
        {
            $this->getAccessibleProjectList();
            $this->appendObj($this->accessibilityProjectMask);
        }
        return "NOERROR";
    }
    private function getLoginErrorString(array $available) : string
    {
        if(isset($this->loginMask))
            return $this->loginMask;

        $onloadTerm= "self.focus();document.loginform.";
        if( isset($_GET['user']) &&
            trim($_GET['user']) != ""   )
        {
            $onloadTerm.= "user";
        }else
            $onloadTerm.= "pwd";
            $onloadTerm.= ".focus()";
            $this->insertAttribute("onload", $onloadTerm);
       
        $errorString= "";
        if($available['error'] == 0)
            return "";
        $content= null;
        switch ($available['error'])
        {
            case 1:
                $errorMsg= "noUserAccess";//mit diesem User-Namen besteht keine Berechtigung";
                break;
            case 2:
                $errorMsg= "wrongUserPassword";//"Passwort stimmt nicht mit User-Namen &uuml;berein";
                break;
            case 3:
                $errorMsg= "multipleUser";//"Multiple UserName in LDAP found!";
                break;
            case 4:
                $errorMsg= "externalAuthenticationError";//"Unknown error in LDAP authentication!";
                break;
            case 5:
                $errorMsg= "noPermission";//"Sie haben keinen Zugriff auf diese Daten!";
                break;
            default:
                $errorMsg= "unknownError@";//<b>UNKNOWN</b> Error type ($error) found!";
                $content= "$error";
                break;
        }
        $errorString= $this->getMessageContent($errorMsg, $content);
        if( $available['error'] == 5 &&
            $available['LoggedIn'] &&
            !$available['access']       )
        {
            $errorString.= " ".$this->getMessageContent("doNewLogin");
        }
        return $errorString;
    }
    private function &getLoginMask(array $available) : object
    {
        //$Get->delete("user");
        $session= STSession::instance();
        $user= $session->getUserName();
        if(	$user == "" &&
            isset($_GET["user"]) &&
            $_GET["user"]		)
        {
            $user= $_GET["user"];
        }
        
        $div= new DivTag("STLoginDiv");
            $div->add(br());
            $div->add(br());
            $div->add(br());
            $div->add(br());
            $layout= new st_tableTag();
                $layout->border(0);
                $layout->add("&#160;");
                $layout->columnWidth("10%");
                $divx= new DivTag("loginMaskDescription");
                if(isset($_GET["debug"]))
                {
                    $Get= new STQueryString();
                    $Get->delete("ERROR");
                    $Get->delete("doLogout");
                    $Get->delete("from");
                    $action= $Get->getStringVars();
                    $divx->add("INPUT of form-tag sending to address <b>'$action'</b><br />");
                }
                    $maskDescription= $this->loginMaskDescription;
                    if($maskDescription == "")
                        $maskDescription= $this->getMessageContent("loginMaskDescription");
                    $divx->add($maskDescription);
                $layout->add($divx);
                $layout->colspan(3);
            
            $layout->nextRow();
            $errorString= $this->getLoginErrorString($available);
            if($errorString != "")
            {
                $layout->add("&#160;");
                $layout->add("&#160;");
                $divE= new DivTag("loginError");
                    $divE->add($errorString);
                $layout->add($divE);
                $layout->colspan(2);
                $layout->nextRow();
                    
            }
            if(!$available['LoggedIn'])
            {
                $layout->add("&#160;");
                $layout->columnWidth("5%");
                $layout->add("&#160;");
                $layout->columnWidth("5%");
                $layout->add("&#160;");
                $layout->columnWidth("10%");
                    $table= $this->getLoginFormTable($user);
                $layout->add($table);
            }
            $div->add($layout);
            $script= new JavaScriptTag();
                $inputPos= 0;
                if($user != "")
                    $inputPos= 1;
                $function= new jsFunction("doFocus");
                    $function->add("tag= document.getElementsByClassName('loginInput');");
                    $function->add("tag[$inputPos].focus();");
                $script->add($function);
                $script->add("window.onload= doFocus();");
                //$script->add("inp= document.getElementsByClassName('loginInput');");
                //$script->add("console.log(inp);");
                //$script->add("inp[1].focus();");
            $div->add($script);
        $this->loginMask= $div;
        return $this->loginMask;
    }
    private function getLoginFormTable(string $user)
    {
        $Get= new STQueryString();
        $Get->delete("ERROR");
        $Get->delete("doLogout");
        $Get->delete("from");
        $action= $Get->getStringVars();
        
        $table= new TableTag("loginTable");
            $table->border(0);
            $table->cellpadding(0);
            $table->cellspacing(0);
            //$table->style("border-width:1; border-style:outset; border-darkcolor:#000000; border-lightcolor:#ffffff");
            $form= new FormTag();
                $form->name("loginform");
                $form->action($action);
                $form->method("post");
                $tr= new RowTag();
                    $td= new ColumnTag();
                        $td->width(80);
                        $td->align("right");
                        $p= new PTag();
                            $p->style("margin-right: 4;");
                            $p->add("User Name:&#160; ");
                        $td->add($p);
                    $tr->add($td);
                    $td= new ColumnTag();
                        $td->width(175);
                        $input= new InputTag("loginInput");
                            $input->type("text");
                            $input->name("user");
                            $input->maxlen(60);
                            $input->size(28);
                            $input->tabindex(1);
                            if($user == "")
                                $input->autofocus();
                            $input->value($user);
                            $td->add($input);
                        $tr->add($td);
                    $td= new ColumnTag();
                        $td->width(100);
                        $td->rowspan(2);
                        $td->valign("top");
                        $td->align("center");
                        $p= new PTag();
                            $p->style("margin-top:3; margin-left:10");
                            $input= new InputTag("myInput");
                                $input->type("submit");
                                $input->tabindex(3);
                                $input->value("Login");
                            $p->add($input);
                        $td->add($p);
                    $tr->add($td);
                $form->add($tr);
                $tr= new RowTag();
                    $td= new  ColumnTag();
                        $td->width(80);
                        $td->align("right");
                        $p= new PTag();
                            $p->style("margin-right: 4;");
                            $p->add("Password:&#160; ");
                        $td->add($p);
                    $tr->add($td);
                    $td= new  ColumnTag();
                        $td->width(175);
                        $input= new InputTag("loginInput");
                            $input->type("password");
                            $input->name("pwd");
                            $input->tabindex(2);
                            if($user != "")
                                $input->autofocus();
                            $input->size(28);
                            $input->maxlen(60);
                        $td->add($input);
                    $tr->add($td);
                    $td= new  ColumnTag();
                        $input= new InputTag();
                            $input->type("hidden");
                            $input->name("doLogin");
                            $input->value(1);
                        $td->add($input);
                    $tr->add($td);
                $form->add($tr);
            $table->add($form);  
        return $table;
    }
    private function &getAccessibleProjectList() : object
    {
        if(isset($this->accessibilityProjectMask))
            return $this->accessibilityProjectMask;
         
        $div= new DivTag("AccessibleProjectList");
            $table= new st_tableTag("ListTable");
                $table->border(0);
                $table->cellpadding("10px");
                $table->cellspacing("2px");
                $table->add("&#160");
                $table->columnWidth("20%");
                $p= new PTag();
                    $p->add(br());
                    $accessibilityString= $this->accessibilityString;
                    if($accessibilityString == "")
                        $accessibilityString= $this->getMessageContent("accessibilityString");
                    $p->add($accessibilityString);
                $table->add($p);
                $table->colspan(2);
            $table->nextRow();
                $table->add("&#160;");
                $table->columnWidth("20%");
                $table->add("&#160;");
                $table->columnWidth("10%");
                $divo= new DivTag();
                    $lu= new  st_tableTag("ListTable");
                    foreach( $this->accessableProjects as $projectID => $project )
                    {
                        $divI= new DivTag();
                            $a= new ATag();
                                $href= "?ProjectID=";
                                $href.= urlencode( $projectID );
                                $session= STSession::getSessionUrlParameter();
                                if($session != "")
                                    $href.= "&".$session;
                                $a->href($href);
                                $a->style("font-size:12pt;font-weight=bold;");
                                $a->add($project[ 'Name' ]);
                            $divI->add($a);
                            $divI->add(br());
                            $divI->add($project[ 'Description' ]);
                        $lu->add($divI);
                        $lu->nextRow();
                    }
                    $divo->add($lu);
                    $divo->add(br());
                    $divo->add(br());
                $table->add($divo);
                $table->columnValign("top");
                $table->columnAlign("left");
            $div->add($table);
        $this->accessibilityProjectMask= $div;
        return $this->accessibilityProjectMask;
    }
}

?>