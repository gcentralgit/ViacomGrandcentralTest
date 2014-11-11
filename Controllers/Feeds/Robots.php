<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

/**
 * Robots.txt file class
 * @author $Author$
 * @class
 */
class Robots extends \Viacom\Crabapple\Controllers\Feeds\Robots
{	
	/**
	 * This renders the infamous robots.txt which we don't want anything to show up as allowed ever
	 */
	protected function renderContent()
	{
		echo "User-agent: *\n";
		echo "Disallow: /\n";
	}
}