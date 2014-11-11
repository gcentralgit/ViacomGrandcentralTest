<?php
/**
 *  \addToGroup Pub\Exceptions
 */
namespace Pub\Exceptions;

class Auth extends \Viacom\Crabapple\Exceptions\Exception
{
	/**
	 * Just setting this to be conformant but it doesn't do anything that I know of
	 * @var string
	 */
	protected $exceptionType = 'authorization';
}