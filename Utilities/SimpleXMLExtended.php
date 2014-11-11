<?php
/**
 *  \addToGroup Pub\Utilities
 */
namespace Pub\Utilities;

/**
 * helper to allow us to be able to return attributes on a simple xml node
 * @author $Author$
 * @class SimpleXMLExtended
 */
class SimpleXMLExtended extends \SimpleXMLElement
{
	public function Attribute($name)
	{
		foreach($this->Attributes() as $key=>$val)
		{
			if($key == $name) return (string)$val;
		}
		return null;
	}
}