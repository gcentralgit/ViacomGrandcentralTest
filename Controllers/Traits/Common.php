<?php
namespace Pub\Controllers\Traits;

trait Common{
	protected $isAuthProtected=true;
	
	function validateAndRetrieveUserInfo()
	{
		if(!$this->isAuthProtected) return;
		
		$cookieName = 'crabappleAuthorizationName';
		if(empty($_COOKIE[$cookieName]))
		{
			$this->setPageNotReady(null,'Unauthorized request.  This website is for viacom code monkeys only.');
			return;
		}
		
		$this->userId = $_COOKIE[$cookieName];
	}
}