<?php
namespace Pub\Data_Providers\Projects;

/**
 * Projects Exceptions Handler Options
 * @author $Author$
 * @class ExceptionOptions
 */
class ExceptionOptions extends \Viacom\Crabapple\Exceptions\Options\Options
{
	protected $values=array();
	
	public function addValue($name,$value)
	{
		$this->values[$name]=$value;
	}
	
	public function getValues()
	{
		return $this->values;
	}
	
	public function hasValues()
	{
		return !empty($this->values);
	}
}