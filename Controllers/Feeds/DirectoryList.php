<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * List all the files at a directory path for a project and environment
 * @author $Author$
 * @class DirectoryList
 */
class DirectoryList extends Generic
{
	/**
	 * @var integer
	 */
	protected $environmentId;
	
	/**
	 * @var string
	 */
	protected $node='';
	
	/**
	 * @var \Pub\Data_Objects\Models\FileListEntry[]
	 */
	protected $files='';
	
	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determineProjectId()
			->determineEnvironmentId()
			->determineNode();
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
	 * get the environment parameter (required)
	 */
	protected function determineEnvironmentId()
	{
		$environment = $this->getPostParameterValue('environment');
		if($environment > -1)
		{
			$this->environmentId = $environment;
		}
	}
	
	/**
	 * get the node (path) parameter (required)
	 */
	protected function determineNode()
	{
		$node = $this->getPostParameterValue('node','');
		if($node != 'THIS_IS_THE_ROOT')
		{
			$this->node = $node;
		}
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchDirectoryList();
	}
	
	/**
	 * get the list of files for a path from svn
	 */
	protected function fetchDirectoryList()
	{
		try
		{
			if($this->node == '')
			{
				$this->files = $this->sourceControlClient->ls($this->project->location.$this->environment->path);
			}
			else
			{
				$this->files = $this->sourceControlClient->ls($this->node);
			}
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed when getting file list for url: '.$this->project->location.$this->environment->path,$e);
			$this->requestFailed('Failed to fetch the directory list');
		}
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setProcessInformationPipeline()
	 */
	protected function setProcessInformationPipeline()
	{
		parent::setProcessInformationPipeline();
		
		$this->processInformationPipeline->formatDirectoryList();
	}
	
	/**
	 * format the file lists into a tree format needed for extjs
	 */
	protected function formatDirectoryList()
	{
		$treeNodes = array();
		foreach($this->files as $file)
		{
			$treeNodes[] = (object)array(
					'id' => $file->fullPath,
					'text' => $file->name,
					'leaf' => $file->isDirectory == false
			);
		}
		
		$this->output->files = $treeNodes;
	}
}