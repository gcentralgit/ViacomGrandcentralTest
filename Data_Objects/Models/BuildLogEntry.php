<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * A class for a log entry like the ones used in svn.
 */
class BuildLogEntry extends LogEntry
{
	/**
	 * @var string
	 */
	public $buildNumber='';
	
	/**
	 * @var string
	 */
	public $buildDate='';
	
	/**
	 * @var string
	 */
	public $buildDetails='';
	
	/**
	 * @var string
	 */
	public $buildApprovedBy='';
	
	/**
	 * @var string
	 */
	public $buildAuthor='';
	
	/**
	 * Used for rollbacks
	 * @var string
	 */
	public $originalBuildNumber='';
}