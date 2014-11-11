<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * A class for a log entry like the ones used in svn.
 */
class LogEntryPath extends \Viacom\Crabapple\Data_Objects\Base
{
	/**
	 * the action performed:
	 * A - The item was added.
	 * D - The item was deleted.
	 * M - Properties or textual contents on the item were changed.
	 * R - The item was replaced by a different one at the same location.
	 * @var string
	 */
	public $action='';
	/**
	 * the world readable version
	 * A - Added
	 * D - Deleted
	 * M - Modified
	 * R - Replaced
	 * @var string
	 */
	public $actionName='';
	/**
	 * the file's path
	 * @var string
	 */
	public $path='';
	
	/**
	 * @var string
	 */
	public $fileType;
	
	/**
	 * Helper function for understanding the single letter codes for a file change from svn
	 * @param string $action
	 */
	public static function translateActionToName($action)
	{
		switch($action)
		{
			case "A":
				return 'Added';
			case "D":
				return 'Deleted';
			case "M":
				return 'Modified';
			case "R":
				return 'Replaced';
			default:
				return 'Unknown';
		}
	}
}