<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Traditional change logs for a path.  Usually used on the dev environment of a project.
 * @author $Author$
 * @class ChangeLogs
 */
class ChangeLogs extends Generic
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
	 * @var string
	 */
	protected $logDateStart;
	protected $logDateEnd;

    protected $searchString;

	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determineFilePath()
			->determineResultSize()
			->determineDate()
            ->determineSearchString()
			->determineProjectId()
			->determineEnvironmentId();
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
	 * get the resultSize parameter
	 */
	protected function determineDate()
	{

		$weekOffset = intval($this->getPostParameterValue('week'));

		if ($weekOffset === null)
		{
			return;
		}

		$weekOffset = $weekOffset > 0 ? 0 : $weekOffset;

		$ts = time() + $weekOffset*7*24*60*60;
		$start = (date('w', $ts) == 0) ? $ts : strtotime('last monday', $ts);

		$this->logDateStart = date('Y-m-d', $start);
		$this->logDateEnd = $weekOffset == 0 ? 'HEAD' : date('Y-m-d', $start+7*24*60*60);
	}

    protected function determineSearchString()
    {
        $this->searchString = $this->getPostParameterValue('search');
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
	 * get the environmentId parameter (required);
	 */
	protected function determineEnvironmentId()
	{
		$this->environmentId = $this->getPostParameterValue('environmentId');
		if(!($this->environmentId > -1))
		{
			$this->requestFailed('environmentId was not found');
		}
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchLogs();
	}
	
	/**
	 * fetch the logs for the filePath and project
	 */
	protected function fetchLogs()
	{
		try
		{
			if (empty($this->logDateStart) && empty($this->logDateEnd))
			{
				$limit = $this->resultSize;
			}
			else
			{
				$limit = array($this->logDateStart,$this->logDateEnd);
			}
			$files = $this->sourceControlClient->log($this->filePath, $limit, $this->project,$this->searchString);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to fetch the log for the path: '.$this->filePath.' and projectId: '.$this->projectId,$e);
			$this->requestFailed('Failed to fetch the log for the path: '.$this->filePath);
			return;
		}
		$this->output->files = $files;
	}
}