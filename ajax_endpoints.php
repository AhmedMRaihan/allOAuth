<?PHP
include_once ("myan_OAuth_Information.php");

function returnOAuthPasswordByCreatingUser($username, $email, $displayName, $provider)
{
	$password = get_option('general_oauth_password');
	if($password == false || $password == '')
	{
		$password = rand();
		update_option("general_oauth_password", $password);
	}
	
	if( $provider == 'google')	
		$provider = 'google';
	else if($provider == 'facebook')	
		$provider = 'facebook';
	else
		throw new Exception("Invalid provider provided.");
		
	if ( !username_exists( $username ) )
	{
		$random_password = $password;
		$user_email=$email;//$username.'@example.com';
		$display_name = $displayName;
		$role = 'contributor';
		$user_id=wp_insert_user( array('user_login' => $username, 'user_pass'=>$random_password, 'role' => $role,
			'user_email'=>$user_email, 'first_name' => $displayName, 'display_name'=>$display_name) 
		);
		if( is_wp_error($user_id) )
			throw new Exception("New OAUTH user creation failed. " . $user_id->get_error_message());
			
		$loginInformation = "provider=$provider&id=".substr($username,1);	
		update_user_meta( $user_id, 'loggedInViaGeneralOAuth', $loginInformation );
	}
	return $password;
}

function myan_goauth_redirect(){
?>
<!doctype html>
<html><head>
</head>
<body>
Redirecting for validation &hellip;
<script type="text/javascript">
(function(){
	try{
		var params = {}, queryString = location.hash.substring(1),
		regex = /([^&=]+)=([^&]*)/g, m;
		while (m = regex.exec(queryString)) {
			params[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
		}
		//console.log(params);
		if( !!params.error)
		{
			alert(params.error);
			window.location = "<?PHP bloginfo('url'); ?>";
		}
		else if (params['state'] != null) {
			// return control to main window by calling a function of that window
			var oauthStateInfo = params['state'].split("_", 2);
			var redirectURI = "<?PHP bloginfo('wpurl') ?>/wp-admin/admin-ajax.php?action=myan_oauthWordpress_login"+'&access_token='+params['access_token']+"&redirect_uri="+encodeURIComponent(oauthStateInfo[1])+"&provider="+oauthStateInfo[0];
			
			window.location = redirectURI;
		} else {
			alert("Your browser seems to be stopping this window from communicating with the main window.");
			window.location = "<?PHP bloginfo('url'); ?>";
		}
	}catch(e)
	{
		if(console)
			console.log(e);
	}
})();

</script>
</body>
</html>
<?PHP
die;
}
add_action('wp_ajax_myan_goauth_login', 'myan_goauth_redirect');
add_action('wp_ajax_nopriv_myan_goauth_login', 'myan_goauth_redirect');

function myan_oauthWordpress_login_callback()
{ 
	try{
		if( !isset($_GET['access_token']) || !isset($_GET['redirect_uri']) || !isset($_GET['provider']) )
			throw new Exception('Redirect uri or OAUTH-Provider or Access token not found');
		$url = urldecode($_GET['redirect_uri']);
		$token= $_GET['access_token'];
		$provider = $_GET['provider'];
		
		if( $token == null || parse_url($url,PHP_URL_HOST) == '')
			throw new Exception('Redirect uri or Access token must have values');
		
		$oauth = new \MyanOAuth\myan_OAuth_Information($provider);
		$userDataFromServer = $oauth->php_generic_validateToken($token);
		
		$username = 's' . $userDataFromServer->id;
		if($userDataFromServer->displayName == '')
			$userDataFromServer->displayName = $username;
		
		$password = returnOAuthPasswordByCreatingUser($username, $userDataFromServer->email, $userDataFromServer->displayName, $provider);
		$userObject= get_user_by( 'login', $username );
		if(wp_check_password( $password, $userObject->user_pass, $userObject->ID) == false)
			wp_set_password( $password, $userObject->id );		
		if($userDataFromServer->email == '')
			$userDataFromServer->email = 's' . $userDataFromServer->id . '@example.com';
		
		$creds = array();
		$creds['user_login'] = $username;
		$creds['remember'] = true;
		$creds['ID'] = $userObject->id;
		$creds['user_email'] = $userDataFromServer->email;
		$creds['display_name']= $userDataFromServer->displayName;
		$creds['first_name'] = $userDataFromServer->displayName;
		$creds['user_password'] = $password;
		//even if a user change password, it should be updated here
		$user = wp_update_user($creds);
		if(is_wp_error($user))
			throw new Exception($user->get_error_message());
			
		/* Profile pic update for authors only */	
		$profilePictureUrl = $userDataFromServer->profile_pic;
		if(user_can($userObject->ID, 'edit_published_posts' ))
		{
			$localImagePath = plugin_dir_path( __FILE__ ) . "images/". $userObject->ID. ".jpg";
			$localImageExists = file_exists($localImagePath);
			
			if(get_user_meta($userObject->id, 'oauth_user_original_profile_pic', null) != $profilePictureUrl && $localImageExists)
			{
				unlink($localImagePath);
				$localImageExists = false;
			}
			if( $localImageExists == false)
			{
				$content = file_get_contents($profilePictureUrl);
				$fp = fopen($localImagePath, "w");
				fwrite($fp, $content);
				fclose($fp);
				$profilePictureUrl = plugin_dir_url( __FILE__ ) . "images/". $userObject->ID. ".jpg";
			}
		}
		
		/* Update metadata */
		update_user_meta($userObject->id, 'oauth_user_original_profile_pic', $userDataFromServer->profile_pic);
		update_user_meta($userObject->id, 'oauth_user_profile_pic', $profilePictureUrl);
		
		$user = wp_signon( $creds, is_ssl() );			
		if( is_wp_error($user) )
			throw new Exception($user->get_error_message());
		
		$home = urldecode($_GET['redirect_uri']);
		echo "<html><head><meta http-equiv='refresh' content='1; url=$home'></head><body>Validation is successful. Please wait &hellip;</body></html>";
	}
	catch(Exception $e)
	{
		//echo '<strong>Caught exception:</strong> ',  $e->getMessage(), "\n";
		//header('412 Precondition Failed');
		header('Location: '. get_bloginfo('url').'?p=-9999&error='. urlencode($e->getMessage()) );
	}
	DIE;
}
add_action('wp_ajax_myan_oauthWordpress_login', 'myan_oauthWordpress_login_callback');
add_action('wp_ajax_nopriv_myan_oauthWordpress_login', 'myan_oauthWordpress_login_callback');


?>