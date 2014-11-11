<?php
/**
 * \addToGroup Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN
 */
namespace Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN;

/**
 * An abstract for a log entry like the ones used in svn.
 */
class FileListEntry extends \Pub\Data_Objects\Models\FileListEntry
{
	/**
	 * create the object from simple xml object
	 * @param \Pub\Utilities\SimpleXMLExtended $xmlData
	 */
	function __construct($xmlData,$path)
	{
		//check if this is a directory
		$this->isDirectory = ( (string) $xmlData->Attribute('kind')) == 'dir';
		
		$this->name = (string)$xmlData->name;
		
		$this->fullPath = $path.'/'.$this->name;
	}
}