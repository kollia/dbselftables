<?php

require_once($_stframecontainer);

class STProjectUserFrame extends STFrameContainer
{
    /**
     * definition for default images
     * on login screen and navigation bar
     * @var array
     */
    private $image= array();
    /**
     * prefix path add to all projects inside database     * 
     * @var string
     */
    private $prefixPath= "";
    /**
     * parent container from witch should be created
     * @var STObjectContainer
     */
    private $fromContainer;
    /**
     * cached project data from database (Name, Path)
     * @var array|null
     */
    private $projectData= null;

	public function __construct(string $name, STBaseContainer $fromContainer)
	{
        global $HTTP_SERVER_VARS;

        STFrameContainer::__construct($name);
        $this->fromContainer= $fromContainer;

        if(isset($this->image['nav']['height']))
            $firstFrameHeight= $this->image['nav']['height']+5;
        elseif(isset($this->image['logo']['height']))
            $firstFrameHeight= $this->image['logo']['height']/2+5;
        else
            $firstFrameHeight= 70;

        $query= new STQueryString();
        $projectID= $query->getParameterValue("ProjectID");
        if(!isset($projectID))
        {
            // cannot display any projects inside the two frames
            // so maybe the container creation is only for install container
            // do not produce any real content for this
            return;
        }
        
        // fetch project data (Name and Path) from database in single query
        $this->loadProjectData($projectID);
        if(isset($this->projectData['Name']))
            $this->title($this->projectData['Name']);
        
        $this->framesetRows("$firstFrameHeight,*");
        $file= $HTTP_SERVER_VARS["SCRIPT_NAME"];
        if($file == "/") // calling from vscode
            $file= "index_basic.php";
        $this->setFramePath("$file?show=navigation&ProjectID=$projectID");
        $this->setFramePath($this->getProjectLink($query));
	}
    /**
     * Load project data (Name and Path) from database
     * 
     * @param int $projectID the ID of the project
     */
    protected function loadProjectData(int $projectID) : void
    {
        $project= $this->fromContainer->getTable("Project");
        $selector= new STDbSelector($project);
        $selector->select("Project", "Name", "Name");
        $selector->select("Project", "Path", "Path");
        $selector->where("ID=$projectID");
        $selector->execute();
        $result= $selector->getResult();
        if(isset($result[0]))
            $this->projectData= $result[0];
    }
    protected function getProjectLink(STQueryString $query) : string
    {
        global $HTTP_SERVER_VARS;

        // use cached project data instead of new database query
        $projectAddress= $this->projectData['Path'] ?? "";
        if($projectAddress == "X")
            $projectAddress= $HTTP_SERVER_VARS["SCRIPT_NAME"];
    
        if(preg_match("/^\//", $projectAddress))
            $projectAddress= $this->prefixPath.$projectAddress;
        $query->update("show=project");
        $projectAddress.= $query->getUrlParamString();
        return $projectAddress;
    }
	public function getDatabase()
	{
		return $this->fromContainer->getDatabase();
	}
    public function addClientRootPath(string $path)
    {
        $this->prefixPath= $path;
    }
    public function setOverviewLogo(string $address, int $width= null, int $height= 140, string $alt= "DB selftables Homepage")
    {
        $this->image['logo']['img']= $address;
        $this->image['logo']['height']= $height;
        $this->image['logo']['width']= $width;
        $this->image['logo']['alt']= $alt;
    }
    public function setOverviewBanner(string $address)
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
}

?>