<?php
namespace Pub\Data_Providers\Source_Control;

/**
 * Source Control Exceptions Handler
 * @author $Author$
 * @class Exception
 */
class Exception extends \Viacom\Crabapple\Exceptions\Exception
{
	public function __construct($message = '', ExceptionOptions $options = null, $previousException = null)
	{
		$tempstring = $message;
		if(!empty($options) && $options->hasValues())
		{
			$tempstring .= "\nParameters:\n";
			foreach($options->getValues() as $name=>$value)
			{
				if(is_object($value) || is_array($value))
				{
					continue;
				}
				$tempstring .= "$name=[$value]\n";
			}
		}
		parent::__construct($tempstring, 911, $previousException);
	}
}