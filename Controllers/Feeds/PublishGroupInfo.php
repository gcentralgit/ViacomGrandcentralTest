<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Publish Group Info feed used by servers to know what doc roots to svn update or svn switch
 * @author $Author$
 * @class PublishGroupInfo
 */
class PublishGroupInfo extends Generic
{
	/**
	 * This feed is publically available
	 * @var boolean
	 */
	protected $isAuthProtected = false;
	
	/**
	 * @var string
	 */
	protected $publishGroupId;
	
	/**
	 * @var integer
	 */
	protected $environmentId=0;
	
	/**
	 * @var string[]
	 */
	protected $projectIds;
	
	/**
	 * @var \Pub\Data_Objects\Models\Project[]
	 */
	protected $projects;
	
	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determinePublishGroupId()
			->determineEnvironmentId();
	}
	
	/**
	 * get publishGroupId parameter (required)
	 */
	protected function determinePublishGroupId()
	{
		$this->publishGroupId = $this->determineParameterValue('publishGroupId');
		if(empty($this->publishGroupId))
		{
			die();
		}
	}
	
	/**
	 * get environmentId parameter (required)
	 */
	protected function determineEnvironmentId()
	{
		$this->environmentId = $this->determineParameterValue('environmentId',true,$this->environmentId); //need to set the initial value as 0 is not interperted correctly here
		if(!is_numeric($this->environmentId) || $this->environmentId < 0) 
		{
			die();
		}
	}
	
	/**
	 * @see Pub\Controllers\Feeds.Generic::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline
			->fetchProjects()
			->fetchPublishGroupProjectIds();
	}
	
	/**
	 * fetch all the projects
	 */
	protected function fetchProjects()
	{
		try
		{
			$this->projects = static::getCrabappleSystem()->utilities->projects->getAll();
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to fetch all projects',$e);
			die();
		}
	}
	
	/**
	 * fetch the project ids for the publishGroupId if any exist
	 */
	protected function fetchPublishGroupProjectIds()
	{
		try
		{
			$this->projectIds = static::getCrabappleSystem()->utilities->projects->getPublishGroupProjectIds($this->publishGroupId);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Invalid publish group id.  No project ids found.',$e);
			die();
		}
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setProcessInformationPipeline()
	 */
	protected function setProcessInformationPipeline()
	{
		parent::setProcessInformationPipeline();
		
		$this->processInformationPipeline->generateOutput();
	}
	
	/**
	 * Create the stdClass[] object for output
	 * Each stdClass will contain folderPath and svnPath
	 */
	protected function generateOutput()
	{
		$response = array();
		
		foreach($this->projectIds as $projectId)
		{
			if(empty($this->projects[$projectId]))
			{
				continue;
			}
			$project = $this->projects[$projectId];
			
			if(count($project->environments) < $this->environmentId+1)
			{
				continue;
			}
			$environment = $project->environments[$this->environmentId];
			
			if(empty($environment->publishFolder))
			{
				continue;
			}
			
			if($this->environmentId > 0)
			{
				$publishEnvironment = $project->environments[$this->environmentId-1];
				//check the cache as the last step and if there's a pending publish then return nothing to this builder to prevent a situation when blank occurs on live
				if(static::getCrabappleSystem()->utilities->sourceControl->getLock($project, $publishEnvironment)) die("");
			}
			
			//we have found a match so we'll build the output object
			die($environment->publishFolder.' '.$project->location.$environment->path);
		}
	}
}