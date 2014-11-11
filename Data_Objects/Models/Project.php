<?php
/**
 * This class belongs to the Pub Models namespace
 * \addToGroup Pub\Data_Objects\Models
 */
namespace Pub\Data_Objects\Models;

/**
 * Model for a project
 */
class Project extends \Viacom\Crabapple\Data_Objects\Base
{
	/**
	 * @var string
	 */
	public $id;
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $location;
	
	/**
	 * @var string
	 */
	public $docRoot;
	
	/**
	 * @var \Pub\Data_Objects\Models\Environment[]
	 */
	public $environments = array();
	
	/**
	 * @var string
	 */
	public $client;
	
	/**
	 * @var string
	 */
	public $publishGroupId;
}