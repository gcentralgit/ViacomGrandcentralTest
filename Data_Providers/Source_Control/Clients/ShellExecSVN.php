<?php
namespace Pub\Data_Providers\Source_Control\Clients;

/**
 * Does all the work for handling svn stuff
 * @author $Author$
 * @class ShellExecSVN
 */
class ShellExecSVN extends \Viacom\Crabapple\Core\Base implements \Pub\Data_Providers\Source_Control\SourceControlInterface
{
    Const CLASS_NAME = 'ShellExecSVN';
	/**
	 * @var string
	 */
	protected $userId;
	
	function __construct()
	{
		if(empty($this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}))
		{
			throw new \Pub\Data_Providers\Source_Control\Exception('client ShellExecSVN not found for dataProviders.source_control.clients in configuration');
		}
		
		if(empty($this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->bin))
		{
			throw new \Pub\Data_Providers\Source_Control\Exception('bin not found in dataProviders.source_control.clients.ShellExecSVN in configuration');
		}
	}
	
	/**
	 * blame used to get the author changes in a file
	 * @see \Pub\Data_Providers\Source_Control\SourceControlInterface::blame()
	 */
	public function blame($path,$project)
	{
		$response = array();
		$output = $this->executeReadCommand('blame -x -b --verbose "'.$path.'"');
		$file_separator = ") ";
		$data_lines = explode("\n",$output);
		foreach($data_lines as $index=>$line)
		{
			if(empty($line)) continue;
			
			$data = (object)array(
				'revision'=>0,
				'author'=>'',
				'date'=>'',
				'line'=>'',
				'lineNumber'=>$index+1
			);
			
			//parse line
			$line_start = strpos($line,$file_separator);
			$data->line = str_replace("\t","  ",substr($line, $line_start+strlen($file_separator)));
			
			//parse data
			$rest_of_data = explode(" ",substr($line,0,$line_start));
			$rest_of_data_index=0;
			$data->revision = $this->parse_blame_line($rest_of_data,$rest_of_data_index);
			$data->author = $this->parse_blame_line($rest_of_data,$rest_of_data_index);
			$date = $this->parse_blame_line($rest_of_data, $rest_of_data_index)."T".$this->parse_blame_line($rest_of_data, $rest_of_data_index).$this->parse_blame_line($rest_of_data, $rest_of_data_index);
			$data->date = date('m/d/Y h:i A',strtotime($date));
			
			array_push($response,$data);
		}
		
		return $response;
	}
	
	protected function parse_blame_line($lines,&$current_index)
	{
		for(;count($lines) > $current_index; $current_index++)
		{
			if(!empty($lines[$current_index]))
			{
				$current_index++;
				return $lines[$current_index-1];
			}
		}
		return '';
	}
	
	/**
	 * UserId to use indicating the person who is doing the publish
	 * @param string $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}
	
	/**
	 * check to see if the user is authorized to acces this path.
	 * 
	 * This function is usually used against a project's root folder and not against every tag, branch or trunk folders of a project
	 * @param string $path
	 */
	public function isAuthorized($path)
	{
		try
		{
			$output = $this->info($path);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns the files on the provided path.  This is not a recursive operation.
	 * 
	 * @param string $path
	 */
	public function ls($path)
	{
		$files = array();
		//get the data
		$output = $this->executeReadCommand('ls --xml "'.$path.'"');
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($output,'\Pub\Utilities\SimpleXMLExtended');
		if (!$xml)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Failed to retrieve a file list for the specified path: '.$path);
		}
		//process the data into objects
		foreach($xml->list->entry as $entry)
		{
			$files[] = new \Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN\FileListEntry($entry, $path);
		}
		return $files;
	}
	
	/**
	 * return a property for a path
	 * @param string $path
	 * @param string $property
	 * @return string
	 */
	public function getProperty($path,$property)
	{
		return trim($this->executeReadCommand('propget "'.$property.'" "'.$path.'"'));
	}
	
	/**
	 * set a property for a path
	 * @param string $path
	 * @param string $name
	 * @param string $value
	 */
	public function setProperty($path,$name,$value)
	{
		//must first checkout the file as we have to commit the final product to change a property
		$tempFolder = $this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->tempDirectory.'/svncrap_'.rand(0, 99999).'/';
		mkdir($tempFolder);
		//checkout
		$this->executeReadCommand('co "'.$path.'" -N "'.$tempFolder.'"');
		//svn apply property
		$this->executeWriteCommand('propset "'.$name.'" "'.$value.'" "'.$tempFolder.'"');
		//now commit the changes
		$this->executeWriteCommand('commit -m "Setting Property '.$name.' to '.$value.'" "'.$tempFolder.'"');
		//clean up the folder
		$this->getCrabappleSystem()->utilities->coreFunctions->rrmdir($tempFolder);
	}
	
	/**
	 * Returns the svn log history for the path with the given limit
	 * @param string $path
	 * @param integer $limit
	 * @param \Pub\Data_Objects\Models\Project $project
	 */
	public function log($path,$limit,$project,$searchString="")
	{
		$history = array();
		//get the data
		$command = 'log ';
		if (is_array($limit))
		{
			$command .= "-r" . ($limit[1] == 'HEAD' ? 'HEAD' : '{' . $limit[1] . '}') . ":{" . $limit[0] . "}";
		}
		else
		{
			$command .= "-l " . (intval($limit)>0 ? intval($limit) : 100);
		}

        if (!empty($searchString)) {
            $command .= " --search \"".$searchString."\"";
        }
		$command .= ' --verbose --stop-on-copy --xml ' . $path;

		$output = $this->executeReadCommand($command);
		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($output,'\Pub\Utilities\SimpleXMLExtended');
		if (!$xml)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Failed to get the history for the specified path: '.$path);
		}
		//collect paths to ignore
		$pathReplacements = array();
		foreach($project->environments as $environment)
		{
			$pathReplacements[] = $project->docRoot.$environment->path;
		}
		//process the data into objects
		foreach($xml->logentry as $entry)
		{
			$entry = new \Pub\Data_Objects\Implementations\Source_Control\Clients\Shell_Exec_SVN\LogEntry($entry,$pathReplacements);
			if(!empty($entry->paths))
			{
				$history[] = $entry;
			}
		}
		
		return $history;
	}
	
	/**
	 * Information for a given url.
	 * @param string $url
	 * @throws \Viacom\Crabapple\Exceptions\Exception
	 * @return \Pub\Data_Objects\Models\LogEntry
	 */
	public function info($url)
	{
		$results = $this->executeReadCommand('info --xml "'.$url.'"');
		if(empty($results))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('No response returned from SVN for the url: '.$url);
		}
		//convert the xml to an object
		libxml_use_internal_errors(true);
		$data = simplexml_load_string($results,'\Pub\Utilities\SimpleXMLExtended');
		if(empty($data->entry))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('No information returned from SVN for the url: '.$url);
		}
		
		//build log message
		$log = new \Pub\Data_Objects\Models\LogEntry;
		$log->revision = $data->entry->commit->Attribute('revision');
		$log->author = (string)$data->entry->commit->author;
		$log->commitDate = date('m/d/Y h:i A',strtotime((string)$data->entry->commit->date));
		
		return $log;
	}
	
	/**
	 * Copy files from one location to another
	 * @param string|string[] $files
	 * @param string $baseURL
	 * @param string $destinationURL
	 * @param string $message
	 */
	public function copy($files, $baseURL, $destinationURL, $message)
	{
		if(is_string($files))
		{
			//convert to an array
			$files = array($files);
		}
		//do the multi file execution
		foreach($files as $file)
		{
			$this->executeWriteCommand('cp --parents -m '.escapeshellarg($message).' '.$baseURL.$file.' '.$destinationURL.$file);
		}
		return true;
	}
	
	/**
	 * Delete a path
	 * @param string|string[] $files
	 * @param string $baseURL
	 * @param string $message
	 */
	public function delete($files, $baseURL, $message)
	{
		if(is_string($files))
		{
			$files = array($files);
		}
		
		foreach($files as $file)
		{
			$this->executeWriteCommand('delete -m '.escapeshellarg($message).' '.$baseURL.$file);
		}
	}
	
	/**
	 * generate a diff between the two urls
	 * @param string $fromURL
	 * @param string $toURL
	 */
	public function diff($fromURL, $toURL)
	{
		$results = $this->executeReadCommand('diff '.escapeshellarg($toURL).' '.escapeshellarg($fromURL).' --summarize --xml');
		
		if(empty($results))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('No information returned from SVN diff for the urls: '.$fromURL.' '.$toURL);
		}
		
		//convert the xml to an object
		libxml_use_internal_errors(true);
		$changes = simplexml_load_string($results,'\Pub\Utilities\SimpleXMLExtended');
		
		if(empty($changes))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('No information returned from SVN diff for the urls: '.$fromURL.' '.$toURL);
		}
		
		$response=array();
		foreach($changes->paths->path as $diff)
		{
			$path = new \Pub\Data_Objects\Models\LogEntryPath();
			$action = (string)$diff->Attribute('item');
			switch($action)
			{
				case 'added':
					$path->action = 'A';
					$path->actionName = 'Added';
					break;
				case 'deleted':
					$path->action = 'D';
					$path->actionName = 'Deleted';
					break;
				default:
					$path->action = 'M';
					$path->actionName = 'Modified';
			}
			$path->path = str_ireplace($toURL, '', (string)$diff);
			$path->fileType = (string)$diff->Attribute('kind');
			$response[]=$path;
		}
		return $response;
	}
	
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
	public function publish($files, $project, $currentEnvironment, $destinationEnvironment, $message)
	{
		//Handles creating a build from the destination environment base path
		$info = $this->info($project->location.$destinationEnvironment->path);
		$version = $info->revision;
		$buildPath = $project->location.$destinationEnvironment->buildFolder.'/'.$version;
		//since svn sometimes tries to append instead of error when a folder already exists we'll just always delete
		try
		{
			$revisionBuildPath = $this->ls($buildPath);
			throw new \Viacom\Crabapple\Exceptions\Exception('A build is already in progress');
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			//exception means we don't have a build against this revision already so we're good
		}
		
		if(!$this->copy('/', $project->location.$destinationEnvironment->path, $buildPath, 'PUB_OP: Creating build against svn revision: '.$version))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Tried to delete the build path and recreate it but could not '.$buildPath);
		}
		
		//Deleting all the files from the new build that are in the publish file list so we can copy over the new versions (svn doesn't to overwrite)
		$this->delete($files, $buildPath, 'PUB_OP: Creating build against svn revision: '.$version);
		//Copying over the files from the current environment base path
		foreach($files as $index => $file)
		{
			if($index+1 < count($files))
			{
				$this->copy($file, $project->location.$currentEnvironment->path, $buildPath, $this->createBuildInfo($this->userId, $version, $files, $message, ''));
			}
			else
			{//this comment will be used by the system to show the list of files that changed for the build
				$this->copy($file, $project->location.$currentEnvironment->path, $buildPath, $this->createBuildInfo($this->userId, $version, $files, $message, ''));
			}
		}
		
		//in svn the destinaton cannot exist when copying so we must always delete before copy
		$this->delete('/', $project->location.$destinationEnvironment->path, 'PUB_OP: Removing the current tag');
		//Copy the build over to the destinaton base path
		$this->copy('/', $buildPath, $project->location.$destinationEnvironment->path, $this->createBuildInfo($this->userId, $version, $files, $message, ''));
	}
	
	public function createBuildInfo($username, $version, $files, $details, $approvedBy)
	{
		$data='PUB_BUILD|buildNumber:'.$version.'|buildDate:'.date('m/d/Y h:i A').'|buildAuthor:'.$username.'|paths:'.(empty($files)?'':implode(',',$files)).'|approvedBy:'.$approvedBy.'|details:'.str_replace(array("\n","\r",'"',"'"),'',$details);
		return $data;
	}
	
	/**
	 * return a log_entry object parsed of its build info or false if the log_entry was not build info
	 * @param \Pub\Data_Objects\Models\LogEntry $log
	 * @return \Pub\Data_Objects\Models\BuildLogEntry|false
	 */
	public function parseBuildInfo($log)
	{
		//make sure this is a build info comment
		if(stripos($log->message,'PUB_BUILD|') !== 0) return false;
		
		//remove pub_build indicator
		$log->message = str_replace('PUB_BUILD|',"",$log->message);
		
		//create the buildInfo object
		$buildInfo = new \Pub\Data_Objects\Models\BuildLogEntry();
		
		//map log_entry data we're keeping onto buildInfo
		$buildInfo->author = $log->author;
		$buildInfo->commitDate = $log->commitDate;
		$buildInfo->revision = $log->revision;
		
		//get the build data from the comment
		$pieces = explode('|',$log->message);
		foreach($pieces as $piece)
		{
			$data = explode(':',$piece,2);
			if($data[0] == 'paths')
			{
				//fill the paths with the files in the build that are comma separated
				if(empty($data[1])) continue;
				
				$files = explode(',',$data[1]);
				foreach($files as $file)
				{
					$path = new \Pub\Data_Objects\Models\LogEntryPath();
					$path->action = 'A';
					$path->actionName = 'Add';
					$path->path = $file;
					$buildInfo->paths[] = $path;
				}
			}
			else
			{
				$buildInfo->$data[0] = $data[1];
			}
		}
		
		return $buildInfo;
	}
	
	/**
	 * Rollback a publish to another build
	 * @param \Pub\Data_Objects\Models\Project $project
	 * @param \Pub\Data_Objects\Models\Environment $environment
	 * @param string $buildNumber
	 */
	public function rollback($project,$environment,$buildNumber)
	{
		$buildLocation = $project->location.$environment->buildFolder.'/'.$buildNumber;
		
		//get the build's log message
		$logs = $this->log($buildLocation,1,$project);
		if(empty($logs))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Failed to find build info');
		}
		$log = $logs[0];
		
		//add in rollback message
		$startPos = stripos($log->message,'|originalBuildNumber:');
		if($startPos !== false)
		{
			//need to remove the current original build number to put ours in
			$log->message = substr($log->message,0,$startPos);
		}
		$log->message .= '|originalBuildNumber:'.$buildNumber;
		
		//get new build number
		$info = $this->info($project->location.$environment->path);
		$version = $info->revision;
		
		//remove current buildNumber
		$startPos = stripos($log->message,'|buildNumber:');
		$endPos = stripos($log->message,'|',$startPos+1);
		$log->message = substr($log->message,0,$startPos+strlen('|buildNumber:')).$version.substr($log->message,$endPos);
		
		//create rollback build
		$this->copy('/', $project->location.$environment->buildFolder.'/'.$buildNumber, $project->location.$environment->buildFolder.'/'.$version, $log->message);
		
		//in svn the destinaton cannot exist when copying so we must always delete before copy
		$this->delete('/', $project->location.$environment->path, 'PUB_OP: Removing the current tag');
		//Copy the build over to the destinaton base path
		$this->copy('/', $buildLocation,$project->location.$environment->path,$log->message);
	}
	
	protected function executeReadCommand($command)
	{
		return $this->_executeCommand(
				$this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->credentials->read->username,
				$this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->credentials->read->password,
				$command);
	}
	
	protected function executeWriteCommand($command)
	{
		return $this->_executeCommand(
				$this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->credentials->write->username,
				$this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->credentials->write->password,
				$command);
	}
	
	/**
	 * helper function for making the commands
	 * @param string $command
	 */
	protected function _executeCommand($username,$password,$command)
	{
		if(empty($username) || empty($password))
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('No username or password provided');
		}
		
		$command = $this->getCrabappleSystem()->components->configuration->dataProviders['source_control']->clients->{static::CLASS_NAME}->bin.' '.$command.' --username "'.$username.'" --password "'.$password.'" --non-interactive --trust-server-cert --no-auth-cache';// 2>&1';

		$output = shell_exec($command);
		//check for error messages
		if(stripos('is not a working copy',$output) > -1)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('directoy is not a working copy');
		}
		if(stripos('does not exist in revision',$output) > -1)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Path is incorrect for repository');
		}
		if(stripos('does not exist in revision',$output) > -1)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Failed to find repository location');
		}
		if(stripos('Could not authenticate to server',$output) > -1)
		{
			throw new \Pub\Exceptions\Auth('Invalid credentials');
		}
		if(stripos('svn: A problem occurred; see other errors for details',$output) > -1)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('SVN Call Error');
		}
		if(stripos("Type 'svn help info' for usage.",$output) > -1)
		{
			throw new \Viacom\Crabapple\Exceptions\Exception('Invalid SVN extensions used');
		}
		return $output;
	}
}