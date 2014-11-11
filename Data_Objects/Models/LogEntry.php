<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * A class for a log entry like the ones used in svn.
 */
class LogEntry extends \Viacom\Crabapple\Data_Objects\Base
{
	/**
	 * @var string
	 */
	public $revision='';
	/**
	 * @var string
	 */
	public $author='';
	/**
	 * @var string
	 */
	public $commitDate=null;
	/**
	 * @var \Pub\Data_Objects\Models\LogEntryPath[]
	 */
	public $paths = array();
	/**
	 * @var string
	 */
	public $message = '';
}