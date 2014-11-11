<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Similar to the Projects feed except this one has its results formatted for ext js
 * @author $Author$
 * @class Selenium
 */
class Selenium extends Generic
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

		$this->fetchInformationPipeline
			->fetchProjects()
			->fetchSeleniumResults();
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

		foreach ($this->projects as $projectName=>$projectData)
		{
			if(empty($projectData->selenium))
			{
				unset($this->projects[$projectName]);
			}
		}

	}

	/**
	 * get test results from selenium servers
	 */
	protected function fetchSeleniumResults()
	{
		foreach ($this->projects as $projectName=>&$projectData)
		{
			$projectData->icon = "";

			$response = static::getCrabappleSystem()->utilities->coreFunctions->fetchRemoteURLWithCaching($projectData->selenium->url,20,false,50,60);
			$response = json_decode($response);

			$setRequest = new \Viacom\Crabapple\Cache\Requests\Set();
			$setRequest->setKey("Selenium|" . $projectName);

			$setRequest->setTtl(
				new \Viacom\Crabapple\Cache\Configurations\TTL((object)array(
					"hard" => 2*60*60, // 2 hours
					"soft" => 60*60 // 1 hour
				))
			);

			if (empty($response->jobs))
			{
				$projectData->icon = "error";
			}
			else
			{
				$projectData->icon = "error";

				foreach ($response->jobs as $job)
				{
					if (preg_match($projectData->selenium->pattern,$job->name))
					{
						if (strpos($job->color,"red")===0) {
							$projectData->icon = "cross";
							break;
						}
						elseif (strpos($job->color,"blue")===0)
						{
							$projectData->icon = "accept";
						}
					}
				}
			}

			$setRequest->setValue($projectData->icon);

			static::getCrabappleSystem()->components->cache->setInCache($setRequest);
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
		$this->output->projects = array();
		foreach ($this->projects as $projectName=>$projectData)
		{
			$this->output->projects[$projectName] = $projectData->icon;
		}

	}
}