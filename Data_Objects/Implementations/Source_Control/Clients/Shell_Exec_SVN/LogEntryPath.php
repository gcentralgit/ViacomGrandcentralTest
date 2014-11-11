<?php
/**
 * \addToGroup Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN
 */
namespace Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN;

/**
 * An abstract for a log entry like the ones used in svn.
 */
class LogEntryPath extends \Pub\Data_Objects\Models\LogEntryPath
{
	/**
	 * construct this object from an xml object
	 * @param \Pub\Utilities\SimpleXMLExtended $xmlObject
	 */
	function __construct($xmlObject)
	{
		$this->action = $xmlObject->Attribute('action');
		$this->actionName = static::translateActionToName($this->action);
		$this->path = (string) $xmlObject;
	}
}