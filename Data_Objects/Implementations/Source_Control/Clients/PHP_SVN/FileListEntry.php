<?php
/**
 * \addToGroup Pub\Data_Objects\Implementations\Source_Control\Clients\PHP_SVN
 */
namespace Pub\Data_Objects\Implementations\Source_Control\Clients\PHP_SVN;


/**
 * An abstract for a log entry like the ones used in svn.
 */
class FileListEntry extends \Pub\Data_Objects\Models\FileListEntry
{
	/**
	 * create the object from simple nested string arrays
	 * @param string[] $data
	 * @param string $path
	 */
	function __construct($data,$path)
	{
		//check if this is a directory
		$this->isDirectory = ( $data['type'] == 'dir' );
		
		$this->name = $data['name'];
		
		$this->fullPath = $path.'/'.$this->name;
	}
}