<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Handles the login check
 * @author $Author$
 * @class Login
 */
class Login extends Generic
{
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
			->checkLogin();
	}
	
	/**
	 * Fetch all the projects (we use the first one to do the auth check)
	 */
	protected function fetchProjects()
	{
		try
		{
			//right now we use the first project's root path.  should use a better method.
			$projects = static::getCrabappleSystem()->utilities->projects->getAll();
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to retrieve all projects',$e);
			$this->requestFailed('Exception while finding all projects');
			return;
		}
		
		if(empty($projects))
		{
			$this->requestFailed('No projects found');
		}
	}
	
	/**
	 * check that the credentials are valid
	 */
	protected function checkLogin()
	{
		try
		{
			$isAuthed = $this->sourceControlClient->isAuthorized($this->projects[0]->location);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Exception while trying to authenticate the user',$e);
			$this->requestFailed('Exception while trying to authenticate the user');
			return;
		}
		
		$this->output->success = $isAuthed;
	}
}