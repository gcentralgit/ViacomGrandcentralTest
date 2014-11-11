<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Getting a specific build's log information with the build information parsed out of the comment
 * @author $Author$
 * @class BuildInfo
 */
class BuildInfo extends Generic
{
	/**
	 * @var string
	 */
	protected $buildNumber;
	
	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determineProjectId()
			->determineEnvironmentId()
			->determineBuildNumber();
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
		if(empty($this->environmentId)) $this->requestFailed('environmentId not found');
	}
	
	/**
	 * get the buildNumber parameter (required)
	 */
	protected function determineBuildNumber()
	{
		$this->buildNumber = $this->getPostParameterValue('buildNumber');
		if(empty($this->buildNumber)) $this->requestFailed('buildNumber not found');
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchBuildInfo();
	}
	
	/**
	 * fetch the build info for the build number, environment and project
	 */
	protected function fetchBuildInfo()
	{
		try
		{
			$builds = $this->sourceControlClient->log($this->project->location.$this->environment->buildFolder.$this->buildNumber, 1, $this->project);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to get build log for path : '.$this->project->location.$this->environment->path,$e);
			$this->requestFailed('Failed to fetch the build log');
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