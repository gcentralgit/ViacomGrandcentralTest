<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * A class for a log entry like the ones used in svn.
 */
class FileListEntry extends \Viacom\Crabapple\Data_Objects\Base
{
	/**
	 * is this entry a directory
	 * @var bool
	 */
	public $isDirectory = false;
	/**
	 * the file's name
	 * @var string
	 */
	public $name;
	/**
	 * the full path to the file
	 * @var string
	 */
	public $fullPath;
}