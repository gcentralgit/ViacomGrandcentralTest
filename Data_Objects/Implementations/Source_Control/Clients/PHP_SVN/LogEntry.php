<?php
/**
 * \addToGroup Pub\Data_Objects\Implementations\Source_Control\Clients\PHP_SVN
 */
namespace Pub\Data_Objects\Implementations\Source_Control\Clients\PHP_SVN;

/**
 * An abstract for a log entry like the ones used in svn.
 */
class LogEntry extends \Pub\Data_Objects\Models\LogEntry
{
	/**
	 * construct this object from a nested string array
	 * @param string[] $data
	 * @param string[] $rootPaths
	 */
	function __construct($data,$rootPaths=array())
	{
		$this->author = $data['author'];
		$this->message = $data['msg'];
		//remove quote marks
		if(!empty($this->message))
		{
			$this->message = str_replace("'",'',$this->message);
		}
		$this->commitDate = date('m/d/Y h:i A',strtotime($data['date']));
		$this->revision = $data['rev'];
		
		//add on the paths
		foreach($data['paths'] as $path)
		{
			$path = new LogEntryPath($path);
			$path->path = str_ireplace($rootPaths, '', $path->path);
			if($path->path == '')
			{
				//removing all the paths that cannot be used for operations upon them aka trunk copies or copies from other projects
				continue;
			}
			$this->paths[] = $path;
		}
	}
}