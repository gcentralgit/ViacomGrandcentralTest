<?php
/**
 * \addToGroup Pub\Data_Objects\Implementations\Source_Control\Clients\PHP_SVN
 */
namespace Pub\Data_Objects\Implementations\Source_Control\Clients\PHP_SVN;

/**
 * An abstract for a log entry like the ones used in svn.
 */
class LogEntryPath extends \Pub\Data_Objects\Models\LogEntryPath
{
	/**
	 * construct this object from a nested string array
	 * @param string[] $data
	 */
	function __construct($data)
	{
		$this->action = $data['action'];
		$this->actionName = static::translateActionToName($this->action);
		$this->path = $data['path'];
	}
}