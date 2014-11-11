<?php
/**
 *  \addToGroup Pub\Utilities
 */
namespace Pub\Utilities;

/**
 * Core functions for pub project
 * Nothing new should go into this class!
 * @author $Author$
 * @class Core
 */
class Core extends \Viacom\VMN\Crabapple\Utilities\Core
{
	/**
	 * File not found page.
	 */
	public static function fileNotFoundPage()
	{
		@ob_clean();
		header("HTTP/1.0 404 Not Found");
		die('Page not found');
	}
	
	/**
	 * recursivly remove a folder and it's contents
	 * @param string $dir
	 */
	public function rrmdir($dir)
	{
		if(is_dir($dir))
		{
			$objects = scandir($dir);
			foreach($objects as $object)
			{
				if($object != "." && $object != "..")
				{
					if(filetype($dir."/".$object) == "dir")
					{
						$this->rrmdir($dir."/".$object);
					}
					else
					{
						unlink($dir."/".$object);
					}
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}