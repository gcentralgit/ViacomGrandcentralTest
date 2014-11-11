<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * Model for an EmailOnPublish
 */
class EmailOnPublish extends \Viacom\Crabapple\Data_Objects\Base
{
	/**
	 * @var boolean
	 */
	public $enabled=false;
	
	/**
	 * comma separated string
	 * @var string
	 */
	public $toAddresses;
	
	/**
	 * @var string
	 */
	public $fromAddress;
}