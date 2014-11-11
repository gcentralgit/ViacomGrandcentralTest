<?php
namespace Pub\Data_Providers\Source_Control;

/**
 * Does all the work for handling svn stuff
 * @author $Author$
 * @class SourceControlInterface
 */
interface SourceControlInterface
{
	/**
	 * Set the user id that should be used as the publish author
	 * 
	 * @param string $userId
	 */
	function setUserId($userId);
	
	/**
	 * check to see if the user is authorized to acces this path.
	 * 
	 * This function is usually used against a project's root folder and not against every tag, branch or trunk folders of a project
	 * @param string $path
	 */
	function isAuthorized($path);
	
	/**
	 * Returns the files on the provided path.  This is not a recursive operation.
	 * 
	 * @param string $path
	 */
	function ls($path);
	
	/**
	 * return a property for a path
	 * @param string $path
	 * @param string $property
	 */
	function getProperty($path,$property);
	
	/**
	 * set a property for a path
	 * @param string $path
	 * @param string $name
	 * @param string $value
	 */
	function setProperty($path,$name,$value);
	
	/**
	 * Returns the svn log history for the path with the given limit
	 * @param string $path
	 * @param integer $limit
	 * @param \Pub\Data_Objects\Models\Project $project
	 */
	function log($path,$limit,$project,$searchString="");
	
	/**
	 * Information for a given url.
	 * @param string $url
	 * @throws \Viacom\Crabapple\Exceptions\Exception
	 * @return \Pub\Data_Objects\Models\Log_Entry
	 */
	function info($url);
	
	/**
	 * Copy files from one location to another
	 * @param string|string[] $files
	 * @param string $baseURL
	 * @param string $destinationURL
	 * @param string $message
	 */
	function copy($files, $baseURL, $destinationURL, $message);
	
	/**
	 * Delete a path
	 * @param string|string[] $files
	 * @param string $baseURL
	 * @param string $message
	 */
	function delete($files, $baseURL, $message);
	
	/**
	 * generate a diff between the two urls
	 * @param string $fromURL
	 * @param string $toURL
	 */
	function diff($fromURL, $toURL);
	
	/**
	 * Get the current environment revision
	 * Handles creating a build from the destination environment base path
	 * Deleting all the files from the new build that are in the publish file list
	 * Copying over the files from the current environment base path
	 * Copy the build over to the destinaton base path
	 * @param string|string[] $files
	 * @param \Pub\Data_Objects\Models\Project $project
	 * @param \Pub\Data_Objects\Models\Environment $currentEnvironment
	 * @param \Pub\Data_Objects\Models\Environment $destinationEnvironment
	 * @param String $message
	 */
	function publish($files, $project, $currentEnvironment, $destinationEnvironment, $message);
	
	/**
	 * Rollback a publish to another build
	 * @param \Pub\Data_Objects\Models\Project $project
	 * @param \Pub\Data_Objects\Models\Environment $environment
	 * @param string $buildNumber
	 */
	function rollback($project,$environment,$buildNumber);
	
	/**
	 * return a log_entry object parsed of its build info or false if the log_entry was not build info
	 * @param \Pub\Data_Objects\Models\Log_Entry $log
	 * @return \Pub\Data_Objects\Models\Build_Log_Entry|false
	 */
	function parseBuildInfo($log);
	
	/**
	 * get the line revision history for a file
	 * @param string $path
	 * @param \Pub\Data_Objects\Models\Project $project
	 */
	function blame($path,$project);
}