<?php
/**
 * \addToGroup Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN
 */
namespace Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN;

/**
 * An abstract for a log entry like the ones used in svn.
 */
class LogEntry extends \Pub\Data_Objects\Models\LogEntry
{
	/**
	 * construct this object from an xml object
	 * @param \Pub\Utilities\SimpleXMLExtended $xmlObject
	 * @param string[] $rootPaths
	 */
	function __construct($xmlObject,$rootPaths=array())
	{
		$this->author = (string) $xmlObject->author;
		$this->message = (string) $xmlObject->msg;
		//remove quote marks
		if(!empty($this->message))
		{
			$this->message = str_replace("'",'',$this->message);
		}
		$this->commitDate = date('m/d/Y h:i A',strtotime((string)$xmlObject->date));
		$this->revision = $xmlObject->Attribute('revision');
		
		//add on the paths
		foreach($xmlObject->paths->children() as $path)
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