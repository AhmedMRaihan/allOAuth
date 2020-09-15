<?PHP
namespace MyanOAuth;

class myan_OAuth_Information{
public $PROVIDER = null;
public $OAUTH_DIV_ID = null;
public $PROVIDER_ICON_LOCATION = null;
private $whereToReturnOnSuccess = null;

public function __construct($provider, $whereToReturnOnSuccess=null) {
	$this->PROVIDER = $provider;
	if( $whereToReturnOnSuccess == null)
		$this->whereToReturnOnSuccess = "document.location.href";
	else
		$this->whereToReturnOnSuccess = "'$whereToReturnOnSuccess'";
		
	if( $this->PROVIDER == 'google')
	{
		$this->PROVIDER_ICON_LOCATION = "https://i.imgur.com/fNLRxeF.png"; // plugins_url() . "/allOAuth/images/google_signin.png";
	}
	else if( $this->PROVIDER == 'facebook')
	{
		$this->PROVIDER_ICON_LOCATION = "https://i.imgur.com/4xaw1sD.png"; //plugins_url() . "/allOAuth/images/fblogin.png";
	}
	else
		throw new Exception("Invalid provider: $provider");
	$this->OAUTH_DIV_ID = "OAUTH_CONNECT_".rand();
}

public function js_connect_info(){
	if($this->PROVIDER == 'google')
		$this->js_connect_info_google();
	else
		$this->js_connect_info_facebook();
}
private function js_connect_info_facebook(){
?>
<script type='text/javascript'>
(function(){
	var FACEBOOK_CLIENT_ID = "<?PHP echo get_option("facebook_oauth_appid_value"); ?>";

	document.getElementById("<?PHP echo $this->OAUTH_DIV_ID;?>").onclick = function() {
		var req = {
			"authUrl" : "https://www.facebook.com/dialog/oauth",
			"clientId" : FACEBOOK_CLIENT_ID,
			"redirectURI" : "<?PHP bloginfo("wpurl");?>/wp-admin/admin-ajax.php?action=myan_goauth_login" //If necessary this url must end with '/'
		};
		var loginUrl= req.authUrl+'?response_type=token&client_id='+req.clientId;
		loginUrl += '&scope=email&state=facebook_'+encodeURIComponent(encodeURIComponent(<?PHP echo $this->whereToReturnOnSuccess; ?>));
		loginUrl += '&redirect_uri='+encodeURIComponent(req.redirectURI);
		
		window.location = loginUrl;
	}
})();
</script>	
<?PHP	
}

// Based on: https://developers.google.com/identity/protocols/OAuth2UserAgent
// and https://developers.google.com/identity/protocols/OAuth2WebServer
private function js_connect_info_google(){
?>
<script type='text/javascript'>
(function(){
	var GOOGLE_AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
	var GOOGLE_CLIENT_ID = "<?PHP echo get_option("gplus_oauth_appid_value"); ?>";
	//var PLUS_ME_SCOPE = "https://www.googleapis.com/auth/userinfo.profile";
	var EMAIL_SCOPE = "https://www.googleapis.com/auth/userinfo.email";

	document.getElementById("<?PHP echo $this->OAUTH_DIV_ID;?>").onclick = function() {
		// adding a global variable to mark google option is taken
		if( window.googleProvider == null)
			googleProvider = true;
		else
		{
			alert("Cannot set oauth provider more than once");
			return;
		}
		var req = {
		"authUrl" : GOOGLE_AUTH_URL,
		"clientId" : GOOGLE_CLIENT_ID,
		"scopes" : [ EMAIL_SCOPE ], //"scopes" : [ PLUS_ME_SCOPE, PLUS_ANOTHER_SCOPE ]
		"redirectURI" : "<?PHP bloginfo("wpurl");?>/wp-admin/admin-ajax.php?action=myan_goauth_login" //If necessary this url must end with '/'
		};
		var loginUrl= req.authUrl+'?response_type=token&include_granted_scopes=true&client_id='+req.clientId;
		loginUrl += '&scope='+req.scopes.join(' ');
		loginUrl += '&state=google_'+encodeURIComponent(encodeURIComponent(<?PHP echo $this->whereToReturnOnSuccess; ?>));
		loginUrl += '&redirect_uri='+encodeURIComponent(req.redirectURI);
		
		window.location = loginUrl;
	}
})();
</script>
<?PHP
}

public function php_generic_validateToken($token){
	if($this->PROVIDER == 'google')
		return $this->php_validateToken_google($token);
	else if($this->PROVIDER == 'facebook')
		return $this->php_validateToken_facebook($token);
}

private function php_validateToken_google($token){
	// Step1: CSRF validation: Provided token should be issued for this app	
	$apiurl = "https://www.googleapis.com/oauth2/v2/tokeninfo?access_token=" . $token;
	$jsonContent = @file_get_contents($apiurl);
	$data = json_decode($jsonContent);
	
	if($jsonContent == null || $data->error)
		throw new Exception("Unable to connect to Google server. Please check your server log.");
	
	if( $data->audience != get_option("gplus_oauth_appid_value") )
	{
		throw new Exception("CSRF validation failure. Given token was not issued for this application ID");
	}

	// Step2: Get user credential for the provided token
	$apiurl = "https://www.googleapis.com/userinfo/v2/me?access_token=$token";
	$jsonContent = @file_get_contents($apiurl);
	$dataFromApi = json_decode($jsonContent);
	
	$userDataFromServer = new \stdClass();
	$userDataFromServer->id = $dataFromApi->id;
	$userDataFromServer->displayName = $dataFromApi->name;
	$userDataFromServer->profile_pic = $dataFromApi->picture;
	$userDataFromServer->email = $dataFromApi->email;
	
	return $userDataFromServer;
}
private function php_validateToken_facebook($token){
	$apiVersion = "v8.0";
	$facebookApiPrefix = sprintf("https://graph.facebook.com/%s", $apiVersion);
	
	// Step1: CSRF validation: Provided token should be issued for this app	
	// Undocumented but effective solution: https://stackoverflow.com/a/8730549/385205
	// $validationUrl = sprintf("https://graph.facebook.com/%s/debug_token?input_token=%s&access_token=%s", $apiVersion, $token, $token);

	$validationUrl = sprintf("%s/app?access_token=%s", $facebookApiPrefix, $token);
	$responseValidation = @file_get_contents($validationUrl);
	
	$appValidationResponse = json_decode($responseValidation);
	if($appValidationResponse == null || $appValidationResponse->error){
		throw new Exception("Unable to connect to Facebook server. Please check your server log.");
	}
	
	if( $appValidationResponse->id != get_option("facebook_oauth_appid_value") )
	{
		throw new Exception("CSRF validation failure. Given token was not issued for this application ID");
	}

	// Step2: Get user credential for the provided token
	$apiurl = $facebookApiPrefix .  "/me?fields=id,name,email,cover,picture.width(300).height(300)&access_token=" . $token;
	$jsonContent = @file_get_contents($apiurl);
	$data = json_decode($jsonContent);

	$userDataFromServer = new \stdClass();
	$userDataFromServer->id = $data->id;
	$userDataFromServer->displayName = $data->name;
	$userDataFromServer->profile_pic = $data->picture->data->url;
	$userDataFromServer->email = $data->email;

	return $userDataFromServer;
}

public function renderHtmlButton(){
?>
<div id="<?PHP echo $this->OAUTH_DIV_ID;?>" style="">
	<img style="height: 25px;width: 150px;cursor: pointer;" src="<?PHP echo $this->PROVIDER_ICON_LOCATION; ?>" />
</div>
<?PHP
}

}
?>
