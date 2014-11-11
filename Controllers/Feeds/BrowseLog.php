<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * BrowseLog feed
 * @author $Author$
 * @class BrowseLog
 */
class BrowseLog extends Generic
{
	/**
	 * @var string
	 */
	protected $filePath='';
	
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
			->determineProjectId()
			->determineEnvironmentId()
			->determineFilePath()
			->determineResultSize();
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
		if(is_null($this->environmentId)) $this->requestFailed('environmentId not found');
	}
	
	/**
	 * get the filePath parameter (required)
	 */
	protected function determineFilePath()
	{
		$this->filePath = $this->getPostParameterValue('filePath');
		if(empty($this->filePath)) $this->requestFailed('filePath not found');
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
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchBlame();
	}
	
	/**
	 * get the logs for the project and filePath
	 */
	protected function fetchBlame()
	{
		try
		{
			$files = $this->sourceControlClient->blame($this->filePath, $this->project);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to fetch blame for path: '.$this->filePath.' for projectId: '.$this->projectId,$e);
			$this->requestFailed('Failed to fetch blame for path: '.$this->filePath);
			return;
		}
		$this->output->files = $files;
	}
}