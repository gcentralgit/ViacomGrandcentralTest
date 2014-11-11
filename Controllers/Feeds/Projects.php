<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Feed for raw project data
 * @author $Author$
 * @class Projects
 */
class Projects extends Generic
{
	/**
	 * This feed is publically available
	 * @var boolean
	 */
	protected $isAuthProtected = false;

	/**
	 * Source - svn / git
	 * @var  string
	 */
	protected $source;

	/**
	 * @see \Viacom\Crabapple\Controllers\Common::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();

		$this->determineParametersPipeline->determineSource();
	}

	protected function determineSource()
	{
		$this->source = $this->determineParameterValue('source',false,'svn');
	}

	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();

		$methodName = 'fetch' . strtoupper($this->source) . 'Projects';
		if (!method_exists($this,$methodName)) {
			$this->fetchInformationPipeline->fetchSVNProjects();
		} else {
			$this->fetchInformationPipeline->{$methodName}();
		}
	}
	
	/**
	 * fetch all projects data
	 */
	protected function fetchSVNProjects()
	{
		try
		{
			$projects = static::getCrabappleSystem()->utilities->projects->getAll();
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logExceptions('Failed to get projects',$e);
			$this->requestFailed('Exception while fetching projects');
			return;
		}
		
		if(empty($projects))
		{
			$this->requestFailed('No project data found');
			return;
		}
		
		$this->output->projects = $projects;
	}

	protected function fetchGITProjects()
	{
		$this->output->projects = array();
	}
}