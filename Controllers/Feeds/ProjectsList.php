<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Similar to the Projects feed except this one has its results formatted for ext js
 * @author $Author$
 * @class ProjectsList
 */
class ProjectsList extends Generic
{
	/**
	 * This feed is publically available
	 * @var boolean
	 */
	protected $isAuthProtected = false;
	
	/**
	 * @var \Pub\Data_Objects\Models\Project[]
	 */
	protected $projects;
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchProjects();
	}
	
	/**
	 * fetch all projects
	 */
	protected function fetchProjects()
	{
		try
		{
			$this->projects = static::getCrabappleSystem()->utilities->projects->getAll();
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to get all projects',$e);
			$this->requestFailed('Exception while fetching all projects');
			return;
		}
		
		if(empty($this->projects))
		{
			$this->requestFailed('No projects found');
			return;
		}
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setProcessInformationPipeline()
	 */
	protected function setProcessInformationPipeline()
	{
		parent::setProcessInformationPipeline();
		
		$this->processInformationPipeline->formatResultsForDisplay();
	}
	
	/**
	 * format the results for ext js
	 */
	protected function formatResultsForDisplay()
	{
		$projects = array_values($this->projects);
		$data = array();
		foreach($projects as $project){
			$data[strtolower($project->title)] = (object)array(
				'id' => $project->id,
				'title' => ucfirst($project->title),
				'icon' => $project->icon,
			);

			if (!empty($project->icon))
			{
				switch($project->icon)
				{
					case 'cross':
						$data[strtolower($project->title)]->iconAlt = 'Selenium tests fail';
						break;
					case 'accept':
						$data[strtolower($project->title)]->iconAlt = 'Selenium OK';
						break;
					case 'error':
						$data[strtolower($project->title)]->iconAlt = 'Cannot fetch selenium results';
						break;
				}

				$data[strtolower($project->title)]->seleniumLink = str_replace("api/json","",$project->selenium->url);
			}

		}
		ksort($data);
		
		$this->output->projects = array_values($data);
	}
}