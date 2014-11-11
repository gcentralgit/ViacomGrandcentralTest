<?php
namespace Pub\Data_Providers\Source_Control;

/**
 * helper for getting the correct source_control client
 * @author $Author$
 * @class SourceControl
 */
class SourceControl extends \Viacom\Crabapple\Core\Base
{
	const LOCK_TIMEOUT = 120; //seconds
	
	/**
	 * returns a source control client
	 * @param \Pub\Data_Objects\Models\Project $project
	 * @return \Pub\Data_Providers\Source_Control\Source_Control_Interface
	 */
	public function getClient(\Pub\Data_Objects\Models\Project $project)
	{
		//check the configuration
		if(empty(static::getCrabappleSystem()->components->configuration->dataProviders['source_control']))
		{
			throw new Exception('no source_control data provider found in configuration');
		}
		if(empty(static::getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients))
		{
			throw new Exception('no source_control data provider clients found in configuration');
		}
		if(empty(static::getCrabappleSystem()->components->configuration->dataProviders['source_control']->defaultClient))
		{
			throw new Exception('no source_control data provider defaultClient parameter found in configuration');
		}
		
		$client_class = "\Pub\Data_Providers\Source_Control\Clients\\";
		$client_name = static::getCrabappleSystem()->components->configuration->dataProviders['source_control']->defaultClient;
		//check if the project has specified its own client
		if(!empty($project->client))
		{
			if(empty(static::getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{$project->client}))
			{
				$options = new ExceptionOptions.php;
				$options->addValue('projectId',$project->id);
				$options->addValue('client',$project->client);
				throw new Exception('project->client not found in data_provider.source_control.clients in configuration',$options);
			}
			$client_name = $project->client;
		}
		$client_class .= $client_name; 
		
		//instance the data provider and return it
		if(!class_exists($client_class))
		{
			$options = new ExceptionOptions;
			$options->addValue('client class',$client_class);
			$options->addValue('client',$client_name);
			throw new Exception('client class not found for source_control data provider',$options);
		}
		
		$client = new $client_class;
		
		if(empty(static::getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{$client_name}->tempDirectory))
		{
			$options = new ExceptionOptions;
			$options->addValue('client',$client_name);
			throw new Exception('client is missing tempDirectory in its data_provider.source_control.clients configuration',$options);
		}
		
		return $client;
	}
	
	protected function generateLockCacheKey(\Pub\Data_Objects\Models\Project $project, \Pub\Data_Objects\Models\Environment $environment)
	{
		return $project->id.'__'.$environment->title;
	}
	
	protected function getCacheResource()
	{
		return static::getCrabappleSystem()->components->cache->getCachePool('primary')->getProvider()->getResource();
	}
	
	public function getLock(\Pub\Data_Objects\Models\Project $project, \Pub\Data_Objects\Models\Environment $environment)
	{
		$resource = $this->getCacheResource();
		$metaObject = $resource->get($this->generateLockCacheKey($project,$environment));
		if (function_exists('gzuncompress') && !empty($metaObject))
		{
			$newData = gzuncompress($metaObject);
			if ($newData !== false)
			{
				$metaObject = $newData;
			}
		}
		
		return unserialize($metaObject);
	}
	
	public function setLock(\Pub\Data_Objects\Models\Project $project, \Pub\Data_Objects\Models\Environment $environment, $userId)
	{
		$resource = $this->getCacheResource();
		
		$value = (function_exists('gzcompress'))?(gzcompress(serialize($userId), 2)):(serialize($userId));
		$resource->setex($this->generateLockCacheKey($project,$environment), static::LOCK_TIMEOUT, $value);
	}
	
	/**
	 * release the lock for publishing 
	 * @param \Pub\Data_Objects\Models\Project $project
	 * @param \Pub\Data_Objects\Models\Environment $environment
	 */
	public function releaseLock(\Pub\Data_Objects\Models\Project $project, \Pub\Data_Objects\Models\Environment $environment)
	{
		$resource = $this->getCacheResource();
		$resource->del($this->generateLockCacheKey($project,$environment));
	}
}