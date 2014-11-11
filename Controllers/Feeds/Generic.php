<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Base class used by all feeds to provide authentication and other helper functions for requests
 * @author $Author$
 * @class Generic
 */
class Generic extends \Viacom\VMN\ENT\Crabapple\Controllers\Feeds\GenericJSON
{
    use \Pub\Controllers\Traits\Common;
	/**
	 * @var string
	 */
	protected $projectId;
	
	/**
	 * @var \Pub\Data_Objects\Models\Project
	 */
	protected $project;
	
	/**
	 * @var string
	 */
	protected $environmentId;
	
	/**
	 * @var \Pub\Data_Objects\Models\Environment
	 */
	protected $environment;
	
	/**
	 * @var \Pub\Data_Providers\Source_Control\SourceControlInterface
	 */
	protected $sourceControlClient;
	
	/**
	 * @var string
	 */
	protected $userId;
	
	/**
	 * setup the output variable and set the svn variable
	 */
	function __construct()
	{
		parent::__construct();
		
		$this->output = (object)array('success'=>true);
	}
	
	/**
	 * works like setPageNotReady but it does more to support the svn feeds
	 * @param string|null $msg
	 */
	protected function requestFailed($msg)
	{
		$this->pageReady=false;
		$this->output->success = false;
		if(!empty($msg))
		{
			$this->output->message = $msg;
		}
	}
	
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline
			->fetchProject()
			->fetchEnvironment()
			->getSourceControlClient()
			->validateAndRetrieveUserInfo()
			->setCredentials()
			->checkCacheLock();
	}
		
	/**
	 * fetch a project by projectId
	 */
	protected function fetchProject()
	{
		if(empty($this->projectId)) return;
		
		try
		{
			$this->project = static::getCrabappleSystem()->utilities->projects->getById($this->projectId);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to fetch project by id: '.$this->projectId, $e);
			$this->requestFailed('Failed to find the project');
		}
	}
	
	/**
	 * fetch the currentEnvironment by environmentId
	 */
	protected function fetchEnvironment()
	{
		if(empty($this->project)) return;
		
		if(empty($this->project->environments[$this->environmentId]))
		{
			$this->requestFailed('environmentId: '.$this->environmentId.' not found for projectId: '.$this->projectId);
			return;
		}
		$this->environment = $this->project->environments[$this->environmentId];
	}
	
	/**
	 * get the related source control client if a project object exists
	 */
	protected function getSourceControlClient()
	{
		if(empty($this->project)) return;
		
		$this->sourceControlClient = static::getCrabappleSystem()->utilities->sourceControl->getClient($this->project);
	}
	
	/**
	 * sets the credentials into svn so any call done during this thread will have the correct credentials
	 */
	protected function setCredentials()
	{
		if(empty($this->sourceControlClient)) return;
		
		$this->sourceControlClient->setUserId($this->userId);
	}
	
	/**
	 * Used to check if there's a current project/environment lock in place if the controller requires it
	 */
	protected function checkCacheLock()
	{
		if(!$this->doCacheLock()) return;
		
		$lockUserId = static::getCrabappleSystem()->utilities->sourceControl->getLock($this->project,$this->environment);
		if($lockUserId === false)
		{
			static::getCrabappleSystem()->utilities->sourceControl->setLock($this->project, $this->environment, $this->userId);
			
			return true;
		}
		else
		{
			$this->requestFailed("Cannot publish due to another publish that is being done by: ".$lockUserId);
		}
	}
	
	/**
	 * Override the display request method to add the json header
	 */
	protected function displayRequest()
	{
		//ensure this always is run even when an exception happens
		$this->releaseCacheLock();
		
		header("Content-Type: application/json");
		$this->handleCache();
		echo json_encode($this->output);
	}
	
	/**
	 * Used to ensure the current project/environment lock is removed if the current controller requires it
	 */
	protected function releaseCacheLock()
	{
		if(!$this->doCacheLock()) return;
		
		static::getCrabappleSystem()->utilities->sourceControl->releaseLock($this->project,$this->environment);
	}
	
	/**
	 * Helper function to deal with post data
	 * @param string $parameterName
	 * @param string $default
	 */
	protected function getPostParameterValue($parameterName,$default=null)
	{
		if(isset($_POST[$parameterName]))
		{
			return $_POST[$parameterName];
		}
		if(isset($_GET[$parameterName]))
		{
			return $_GET[$parameterName];
		}
		return $default;
	}
	
	protected function doCacheLock()
	{
		return false;
	}
}