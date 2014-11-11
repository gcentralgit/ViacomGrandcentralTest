<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Rollback feed is for changing which build is on an environment.  The builds must already exist.
 * @author $Author$
 * @class Rollback
 */
class Rollback extends Generic
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
	 * get the project id
	 */
	protected function determineProjectId()
	{
		$this->projectId = $this->getPostParameterValue('projectId');
		if(empty($this->projectId)) $this->requestFailed('projectId not found');
	}
	
	/**
	 * get the environment id/number
	 */
	protected function determineEnvironmentId()
	{
		$this->environmentId = $this->getPostParameterValue('environmentId');
		if(empty($this->environmentId)) $this->requestFailed('environmentId not found');
	}
	
	/**
	 * get the build number
	 */
	protected function determineBuildNumber()
	{
		$this->buildNumber = $this->getPostParameterValue('buildNumber');
		if(empty($this->buildNumber)) $this->requestFailed('buildNumber not found');
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setProcessInformationPipeline()
	 */
	protected function setProcessInformationPipeline()
	{
		parent::setProcessInformationPipeline();
		
		$this->processInformationPipeline->rollbackEnvironment();
	}
	
	/**
	 * Rollback the environment to a specific build
	 */
	protected function rollbackEnvironment()
	{
		try
		{
			$this->sourceControlClient->rollback($this->project,$this->environment,$this->buildNumber);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to rollback to build number: '.$this->buildNumber.' for environmentId: '.$this->environmentId.' with projectId: '.$this->projectId,$e);
			$this->requestFailed('Failed to rollback environment');
			return;
		}
		
		$this->output->success=true;
	}
	
	protected function doCacheLock()
	{
		return true;
	}
}