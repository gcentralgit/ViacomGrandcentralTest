<?php
namespace Pub\Data_Providers\Projects;

/**
 * Provides the projects (in xml format) that are available from the /projects folder
 * @author $Author$
 * @class Projects
 */
class Projects extends \Viacom\Crabapple\Core\Base
{
	/**
	 * @var \Pub\Data_Objects\Models\Project[]
	 */
	protected $projects=array();
	
	/**
	 * @var string[]
	 */
	protected $publishGroups = array();
	
	/**
	 * Return the list of project ids for a publish group id
	 * @param string $publishGroupId
	 * @param boolean $forceRefresh
	 * @throws \Viacom\Crabapple\Exceptions\Exception
	 */
	public function getPublishGroupProjectIds($publishGroupId,$forceRefresh=false)
	{
		$this->getAll($forceRefresh);
		if(empty($this->publishGroups) || empty($this->publishGroups[$publishGroupId])) throw new \Viacom\Crabapple\Exceptions\Exception('No project ids found for publishGroupId: '.$publishGroupId);
		
		return $this->publishGroups[$publishGroupId];
	}
	
	/**
	 * get a project by it's string id
	 * @param string $id
	 * @param boolean $forceRefresh
	 * @return \Pub\Data_Objects\Models\Project
	 */
	public function getById($id,$forceRefresh=false)
	{
		$projects = $this->getAll($forceRefresh);
		if(empty($projects[$id]))
		{
			$options = new ExceptionOptions;
			$options->addValue('id',$id);
			throw new Exception('Project not found',$options);
		}
		return $projects[$id];
	}
	
	/**
	 * returns all the projects and their environments
	 * @param boolean $forceRefresh
	 * @return \Pub\Data_Objects\Models\Project[]
	 */
	public function getAll($forceRefresh=false)
	{
		if(!empty($this->projects)) return $this->projects;

		//check to see if we've loaded all projects
		$callback = new \Viacom\Crabapple\Cache\Configurations\Callback();
		$callback
			->setClassName(__CLASS__)
			->setMethodName("_nonCacheLoadAll")
			->setParameters(array());
	
		$ttl = new \Viacom\Crabapple\Cache\Configurations\TTL();
		$ttl->setSoft(120);
		$ttl->setHard(300);
	
		$getRequest = new \Viacom\Crabapple\Cache\Requests\Get();
		$getRequest->setTTL($ttl)
			->setCallback($callback)
			->setForceRefresh($forceRefresh)
			->setPoolName(static::getCrabappleSystemStatic()->components->configuration->cache->defaults->pool)
			->setKey('all_pub_projects');
	
		//get the data and save it to this object
		$data = static::getCrabappleSystemStatic()->components->cache->getFromCache($getRequest);
		$this->projects = $data->projects;
		$this->publishGroups = $data->publishGroups;

		foreach ($this->projects as $projectName=>&$projectData)
		{
			$projectData->icon = "";
			if (!empty($projectData->selenium))
			{
				$getRequest = new \Viacom\Crabapple\Cache\Requests\Get();
				$getRequest->setKey("Selenium|" . $projectName);

				try
				{
					$projectData->icon = static::getCrabappleSystemStatic()->components->cache->getFromCache($getRequest);
				}
				catch (\Viacom\Crabapple\Exceptions\Exception $e)
				{
					$projectData->icon = "error";
				}
			}
		}

		return $this->projects;
	}
	
	/**
	 * Function that actually loads all projects (don't call this function directly)
	 * @throws \Viacom\Crabapple\Exceptions\Exception
	 * @return \Pub\Data_Objects\Models\Project[]
	 */
	public static function _nonCacheLoadAll()
	{
		//find where the project folder is located
		if(empty(static::getCrabappleSystemStatic()->components->configuration->dataProviders['projects']) || empty(static::getCrabappleSystemStatic()->components->configuration->dataProviders['projects']->projectFolder))
		{
			$projectFolder = static::getCrabappleSystemStatic()->components->configuration->site->documentRoot . '/projects';
		}
		else
		{
			$projectFolder = static::getCrabappleSystemStatic()->components->configuration->dataProviders['projects']->projectFolder;
		} 
		
		//make sure the directory exists
		if(!file_exists($projectFolder))
		{
			$options = new ExceptionOptions;
			$options->addValue('project folder', $projectFolder);
			throw new \Exception('Project Folder not found',$options);
		}
		
		//get the list of files from the projects folder
		$files = scandir($projectFolder);
		if(!$files)
		{
			$options = new ExceptionOptions;
			$options->addValue('project folder', $projectFolder);
			throw new \Exception('Failed while scanning project folder',$options);
		}
		if(count($files) == 0)
		{
			$options = new ExceptionOptions;
			$options->addValue('project folder', $projectFolder);
			throw new \Exception('No project xml files found',$options);
		}
		
		libxml_use_internal_errors(true);
		$projects = array();
		$publishGroups = array();
		
		foreach($files as $fileName)
		{
			if(stripos($fileName,'.xml') === false) continue;
			
			//parse the xml
			$data = file_get_contents($projectFolder.'/'.$fileName);
			$dataProject = simplexml_load_string($data);
		
			if(empty($dataProject))
			{
				//we encountered an xml problem so we're done
				$errors = array();
				foreach (libxml_get_errors() as $error)
				{
					$errors[] = 'XML Parse Error on line: '.$error->line.' with message: '.$error->message;
				}
					
				libxml_clear_errors();
				
				$options = new ExceptionOptions;
				$options->addValue('file', ($projectFolder.'/'.$fileName) );
				$options->addValue('xml errors',implode(", ",$errors));
				$exception = new Exception('XML problem with project file',$options);
				static::getCrabappleSystemStatic()->components->logger->logException($exception);
				continue;
			}
			
			//build a new project object
			$project = new \Pub\Data_Objects\Models\Project();
			$project->id = strtolower(str_ireplace('.xml', '', $fileName));
			$project->title = (string)$dataProject->title;
			if(empty($project->title))
			{
				$options = new ExceptionOptions;
				$options->addValue('file', ($projectFolder.'/'.$fileName) );
				$exception = new Exception('XML problem with project file missing <title> tag',$options);
				static::getCrabappleSystemStatic()->components->logger->logException($exception);
				continue;
			}
			$project->location = (string)$dataProject->location;
			if(empty($project->location))
			{
				$options = new ExceptionOptions;
				$options->addValue('file', ($projectFolder.'/'.$fileName) );
				$exception = new Exception('XML problem with project file missing <location> tag',$options);
				static::getCrabappleSystemStatic()->components->logger->logException($exception);
				continue;
			}
			$project->docRoot = (string)$dataProject->docRoot;
			if(empty($project->docRoot))
			{
				$options = new ExceptionOptions;
				$options->addValue('file', ($projectFolder.'/'.$fileName) );
				$exception = new Exception('XML problem with project file missing <docRoot> tag',$options);
				static::getCrabappleSystemStatic()->components->logger->logException($exception);
				continue;
			}
			$project->publishGroupId = (string)$dataProject->publishGroupId;
			if(empty($project->publishGroupId))
			{
				$options = new ExceptionOptions;
				$options->addValue('file', ($projectFolder.'/'.$fileName) );
				$exception = new Exception('XML problem with project file missing <publishGroupId> tag',$options);
				static::getCrabappleSystemStatic()->components->logger->logException($exception);
				continue;
			}
			
			//build publish group so we can quickly reference it from a feed for server updates
			if(!empty($publishGroups[$project->publishGroupId]))
			{
				array_push($publishGroups[$project->publishGroupId],$project->id);
			}
			else
			{
				$publishGroups[$project->publishGroupId] = array($project->id);
			}
			
			foreach($dataProject->environments->environment as $envIndex => $dataEnvironment)
			{
				//create the environment information and validate the data
				$env = new \Pub\Data_Objects\Models\Environment();
				$env->title = (string)$dataEnvironment->title;
				if(empty($env->title))
				{
					$options = new ExceptionOptions;
					$options->addValue('file', ($projectFolder.'/'.$fileName) );
					$options->addValue('environment index',$envIndex);
					$exception = new Exception('XML problem with project file missing environment <title> tag',$options);
					static::getCrabappleSystemStatic()->components->logger->logException($exception);
					continue;
				}
				$env->path = (string)$dataEnvironment->path;
				if(empty($env->path))
				{
					$options = new ExceptionOptions;
					$options->addValue('file', ($projectFolder.'/'.$fileName) );
					$options->addValue('environment index',$envIndex);
					$exception = new Exception('XML problem with project file missing environment <path> tag',$options);
					static::getCrabappleSystemStatic()->components->logger->logException($exception);
					continue;
				}
				$env->buildFolder = (string)$dataEnvironment->buildFolder;
				//the first environment won't have a build folder.  The rest must.
				if(empty($env->buildFolder) && $envIndex > 0)
				{
					$options = new ExceptionOptions;
					$options->addValue('file', ($projectFolder.'/'.$fileName) );
					$options->addValue('environment index',$envIndex);
					$exception = new Exception('XML problem with project file missing environment <buildFolder> tag',$options);
					static::getCrabappleSystemStatic()->components->logger->logException($exception);
					continue;
				}
				
				$env->publishFolder = (string)$dataEnvironment->publishFolder;
				//the first environment may not have a publish folder.  The rest must.
				if(empty($env->publishFolder) && $envIndex > 0)
				{
					$options = new ExceptionOptions;
					$options->addValue('file', ($projectFolder.'/'.$fileName) );
					$options->addValue('environment index',$envIndex);
					$exception = new Exception('XML problem with project file missing environment <publishFolder> tag',$options);
					static::getCrabappleSystemStatic()->components->logger->logException($exception);
					continue;
				}

				$env->publishMessageRequired = (isset($dataEnvironment->publishMessageRequired) && $dataEnvironment->publishMessageRequired == "true");
				
				$env->emailOnPublish = new \Pub\Data_Objects\Models\EmailOnPublish();
				
				if(isset($dataEnvironment->emailOnPublish))
				{
					$env->emailOnPublish->enabled = (isset($dataEnvironment->emailOnPublish->enabled) && $dataEnvironment->emailOnPublish->enabled == "true");
					$env->emailOnPublish->fromAddress = (string)$dataEnvironment->emailOnPublish->fromAddress;
					$env->emailOnPublish->toAddresses = (string)$dataEnvironment->emailOnPublish->toAddresses;
					if(empty($env->emailOnPublish->fromAddress) || empty($env->emailOnPublish->toAddresses))
					{
						$options = new ExceptionOptions;
						$options->addValue('file', ($projectFolder.'/'.$fileName) );
						$options->addValue('environment index',$envIndex);
						$exception = new Exception('XML problem with project file. Both <fromAddress> and <toAddresses> in <email> are required. Disabling emailing until this is resolved.',$options);
						static::getCrabappleSystemStatic()->components->logger->logException($exception);
						$env->emailOnPublish->enabled = false;
					}
				}
				
				$project->environments[] = $env;
			}
			//if we don't have any environments then we won't add this project
			if(empty($project->environments) && $envIndex > 0)
			{
				$options = new ExceptionOptions;
				$options->addValue('file', ($projectFolder.'/'.$fileName) );
				$exception = new Exception('Project file has no environments',$options);
				static::getCrabappleSystemStatic()->components->logger->logException($exception);
				continue;
			}

			if (!empty($dataProject->selenium))
			{
				$project->selenium = new \stdClass();
				$project->selenium->url = (string)$dataProject->selenium->url;
				$project->selenium->pattern = (string)$dataProject->selenium->pattern;
			}

			$projects[$project->id]=$project;
		}
		
		return (object)array('projects'=>$projects,'publishGroups'=>$publishGroups);
	}
}