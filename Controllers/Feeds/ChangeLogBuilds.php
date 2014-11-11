<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Used on environments that have build folders as the comments are collected from builds and not the tag's logs when this is true
 * @author $Author$
 * @class ChangeLogBuilds
 */
class ChangeLogBuilds extends Generic
{
	/**
	 * @var integer
	 */
	protected $resultSize=25;
	
	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determineResultSize()
			->determineProjectId()
			->determineEnvironmentId();
	}
	
	/**
	 * get the resultSize parameter
	 */
	protected function determineResultSize()
	{
		$resultSize = $this->getPostParameterValue('resultSize');
		if($resultSize > 0)
		{
			$this->resultSize = $resultSize;
		}
	}
	
	/**
	 * get the projectId parameter (required)
	 */
	protected function determineProjectId()
	{
		$this->projectId = $this->getPostParameterValue('projectId');
		if(empty($this->projectId)) $this->requestFailed('projectId not found');
	}
	
	/**
	 * get the environmentId parameter (required)
	 */
	protected function determineEnvironmentId()
	{
		$this->environmentId = $this->getPostParameterValue('environmentId');
		if(!($this->environmentId > 0))
		{
			$this->requestFailed('environmentId must be an integer');
		}
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchBuildLogs();
	}
	
	/**
	 * fetch the logs for the environment's build folder
	 */
	protected function fetchBuildLogs()
	{
		try
		{
            $this->resultSize = array(date('Y-m-d', time() - 10*24*60*60),'HEAD');
			$builds = $this->sourceControlClient->log($this->project->location.$this->environment->buildFolder, $this->resultSize, $this->project);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to get logs of build directory: '.$this->project->location.$this->environment->buildFolder,$e);
			$this->requestFailed('Failed to fetch the logs for the build directory of this environment');
			return;
		}
		
		//weed out the logs that only have to do with builds and convert their data into a build_log_entry
		$output = array();
		foreach($builds as $build)
		{
			$info = $this->sourceControlClient->parseBuildInfo($build);
			
			//only return true builds
			if($info)
			{
				$output[$info->buildNumber] = $info;
			}
		}
		$this->output->files = array_values($output);
	}
}