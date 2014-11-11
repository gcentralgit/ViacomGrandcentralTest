<?php
/**
 *  \addToGroup Pub\Controllers\Feeds
 */
namespace Pub\Controllers\Feeds;

//TODO: Remove this
require_once __DIR__ . '/../../../packages/phpmailer/class.phpmailer.php';

/**
 * Publisher feed
 * @author $Author$
 * @class Publish
 */
class Publish extends Generic
{
	/**
	 * @var string[]
	 */
	protected $filePaths;
	
	/**
	 * @var \Pub\Data_Objects\Models\Environment
	 */
	protected $publishEnvironment;
	
	/**
	 * @var string
	 */
	protected $message;
	
	/**
	 * @see Pub\Controllers\Feeds.Generic::setDetermineParametersPipeline()
	 */
	protected function setDetermineParametersPipeline()
	{
		parent::setDetermineParametersPipeline();
		
		$this->determineParametersPipeline
			->determineFilePaths()
			->determineProjectId()
			->determineEnvironmentId()
			->determineMessage();
	}
	
	/**
	 * get the filePaths parameter (required)
	 */
	protected function determineFilePaths()
	{
		$this->filePaths = $this->getPostParameterValue('filePaths');
		if(empty($this->filePaths)) $this->requestFailed('filePaths not found');
		
		$this->filePaths = explode(',',$this->filePaths);
	}
	
	/**
	 * get the projectId parameter (required)
	 */
	protected function determineProjectId()
	{
		$this->projectId = $this->getPostParameterValue('projectId');
		if(empty($this->projectId)) $this->requestFailed('projectId not found');
	}
	
	/**
	 * get the environmentId parameter (required)
	 */
	protected function determineEnvironmentId()
	{
		$this->environmentId = $this->getPostParameterValue('environmentId');
		if(!($this->environmentId > -1)) $this->requestFailed('environmentId not found');
	}
	
	protected function determineMessage()
	{
		$this->message = $this->getPostParameterValue('message',false);
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setFetchInformationPipeline()
	 */
	protected function setFetchInformationPipeline()
	{
		parent::setFetchInformationPipeline();
		
		$this->fetchInformationPipeline->fetchPublishEnvironments();
	}
	
	/**
	 * get the publishEnvironment and currentEnvironment objects
	 */
	protected function fetchPublishEnvironments()
	{
		if(empty($this->project->environments[$this->environmentId+1]))
		{
			$this->requestFailed('failed to find environment for project id: '.$this->projectId.' and environment id: '.($this->environmentId+1));
			return;
		}
		$this->publishEnvironment = $this->project->environments[$this->environmentId+1];
	}
	
	/**
	 * @see Viacom\VMN\ENT\Crabapple\Controllers.Common::setProcessInformationPipeline()
	 */
	protected function setProcessInformationPipeline()
	{
		parent::setProcessInformationPipeline();
		
		$this->processInformationPipeline
			->doPublish()
			->sendPublishEmail();
	}
	
	/**
	 * Do the publish
	 */
	protected function doPublish()
	{
		try
		{
			$response = $this->sourceControlClient->publish($this->filePaths, $this->project, $this->environment, $this->publishEnvironment, $this->message);
		}
		catch(\Viacom\Crabapple\Exceptions\Exception $e)
		{
			$this->logException('Failed to copy files: '.implode(",",$this->filePaths),$e);
			$this->requestFailed('Failed to copy files: '.implode(",",$this->filePaths));
			return;
		}
		$this->output->message = 'Successfully published';
	}
	
	protected function sendPublishEmail()
	{
		if(!$this->environment->emailOnPublish->enabled) return;
		
		$mail = $this->getCrabappleSystem()->components->mailer;
		
		$mail->SetFrom($this->environment->emailOnPublish->fromAddress);
		
		$toAddresses = explode(',',$this->environment->emailOnPublish->toAddresses);
		foreach($toAddresses as $address)
		{
			$mail->AddAddress(trim($address));
		}
		
		$mail->Subject = '[deploy] '.$this->project->id.' - '.$this->environment->title;
		
		//build message body
		$body = '<html><head><style>body{font-size:12px} div{margin:0;padding:0}</style><body>';
		$body .= '<div style="font-size:16px;font-weight:bold">'.$this->userId.' did a publish to '.$this->project->id.' - '.$this->environment->title.'</div><br>';
		if(!empty($this->message))
		{
			$body .= '<div style="font-size:16px;font-weight:bold">Message:</div>'.(empty($this->message)?'<None provided>':$this->message).'<br>';
		}
		$body .= '<div style="font-size:16px;font-weight:bold">Files:</div><ul>';
		foreach($this->filePaths as $path)
		{
			$body .= "<li>$path</li>";
		}
		$body .= '</ul>';
		
		$body .= '<div style="font-size:16px;font-weight:bold">Published By:</div>'.$this->userId.'<br>';
		
		$body .= '<br><br>Sincerely,<br><a href="http://grandcentral.comedycentral.com">Grandcentral Publishing</a>.';
		
		$body .= '</body></html>';
		
		$mail->MsgHTML($body);
		try
		{
			if(!$mail->Send())
			{
				throw new \Exception('Unknown problem sending email');
			}
		}
		catch(\Exception $e)
		{
			$this->requestFailed("Publish was successful but the server failed to send out the publish email due to: ".$e->getMessage());
		}
	}
	
	protected function doCacheLock()
	{
		return true;
	}
}