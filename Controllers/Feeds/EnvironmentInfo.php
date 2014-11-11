<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Returns the current build information for the environment if the environment has one
 * @author $Author$
 * @class EnvironmentInfo
 */
class EnvironmentInfo extends Generic
{
	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determineProjectId()
			->determineEnvironmentId();
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
		
		$this->fetchInformationPipeline->fetchBuildLog();
	}
	
	/**
	 * fetch the current build log for the environment tag
	 */
	protected function fetchBuildLog()
	{
		try
		{
			$builds = $this->sourceControlClient->log($this->project->location.$this->environment->path, 1, $this->project);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to get environment build logs: '.$this->project->location.$this->environment->path,$e);
			$this->requestFailed('Failed to fetch the build log of the current build for this environment');
			return;
		}
		
		if(empty($builds)) return;
		
		//weed out the logs that only have to do with builds and convert their data into a build_log_entry
		$info = $this->sourceControlClient->parseBuildInfo($builds[0]);
			
		if($info)
		{
			$this->output->data = $info;
		}
	}
}