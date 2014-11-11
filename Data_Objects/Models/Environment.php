<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * Model for an Environment
 */
class Environment extends \Viacom\Crabapple\Data_Objects\Base
{
	/**
	 * @var string
	 */
	public $title;
	
	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var string
	 */
	public $buildFolder;
	
	/**
	 * @var string
	 */
	public $publishFolder;
	
	/**
	 * @var boolean
	 */
	public $publishMessageRequired=false;
	
	/**
	 * @var \Pub\Data_Objects\Models\EmailOnPublish
	 */
	public $emailOnPublish;
}