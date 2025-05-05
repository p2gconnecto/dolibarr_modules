<?php
class ActionsHelpchat
{ 
	function printLeftBlock()
	{
	    global $user, $conf;
	    
		$userIdentity = $user->firstname.' '.$user->lastname;
		$userEmail = empty($user->email) ? $conf->global->MAIN_INFO_SOCIETE_MAIL : $user->email;
		
		$htmlChatScript = file_get_contents(DOL_DOCUMENT_ROOT .'/helpchat/includes/chatscript.php');
		
		$this->resprints = strtr($htmlChatScript, array(
		       '{USER_NAME}' => $userIdentity,
		       '{USER_EMAIL}' => $userEmail,
		       '{HASH}' => hash_hmac("sha256", $userEmail, "8ee9b3bda2d0d378ef61d06afda1cbe7239f866d"),
		       
		       '{USER_OFFICE_PHONE}' => $user->office_phone,
		       '{USER_MOBILE_PHONE}' => $user->user_mobile,
		       
		       '{COMPANY_NAME}' => $conf->global->MAIN_INFO_SOCIETE_NOM,
		       '{COMPANY_EMAIL}' => $conf->global->MAIN_INFO_SOCIETE_MAIL,
		       '{COMPANY_PHONE}' => $conf->global->MAIN_INFO_SOCIETE_TEL,
		       '{SIREN}' => $conf->global->MAIN_INFO_SIREN,
		       
		       '{DOLIBARR_VERSION}' => $conf->global->MAIN_VERSION_LAST_INSTALL,
		       ));
		       
		 return 1;
	}
}