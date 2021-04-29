<?php
//namespace MyanOAuth;

/*
Plugin Name: OAuthLogin Support
Plugin URI: https://github.com/AhmedMRaihan/allOAuth
Description: This plugin allow users to login via Google and Facebook's OAuth.
Author: seoul
Author URI: http://www.mythicangel.com
Version: 5.3
*/
include_once ("myan_OAuth_Information.php");
include_once ("ajax_endpoints.php");

function myan_delete_user( $user_id ) {
	$localImagePath = plugin_dir_path( __FILE__ ) . "images/". $user_id. ".jpg";
	if(file_exists($localImagePath))
		unlink($localImagePath);
}
add_action( 'delete_user', 'myan_delete_user' );


// Apply filter
add_filter( 'get_avatar' , 'myan_OAuth_custom_avatar' , 10 , 4 );
function myan_OAuth_custom_avatar( $avatar, $id_or_email, $size, $alt ) {
	if( get_option('general_oauth_showProfilePic', 'hide') != 'show')
		return $avatar;
		
	$user = false;
	if ( is_numeric( $id_or_email ) ) {
		$id = (int) $id_or_email;
		$user = get_user_by( 'id' , $id );
	} elseif (is_object( $id_or_email )) {
		if ( ! empty( $id_or_email->user_id ) ) {
		$id = (int) $id_or_email->user_id;
		$user = get_user_by( 'id' , $id );
	}
	} else {
		$user = get_user_by( 'email', $id_or_email );	
	}
	if ( $user && is_object( $user ) ) {
		$avatarNew = get_user_meta( $user->data->ID, "oauth_user_profile_pic", true);
		if($avatarNew != '')
			$avatar = "<img alt='{$alt}' src='{$avatarNew}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	}
	//print_r($avatar);die;
	return $avatar;
}

add_action( 'show_user_profile', 'myan_show_extra_profile_fields'  );
add_action( 'edit_user_profile', 'myan_show_extra_profile_fields' );
 
function myan_show_extra_profile_fields( $user ) { 
$isOAuthUser = get_user_meta($user->ID, 'loggedInViaGeneralOAuth');
if(count($isOAuthUser) == 0)
	return $user;
?> 
<h3>OAuth profile information</h3>
<table class="form-table">
	<tr>
		<th><label for="image">User ID</label></th>
	<td>
		<span class="description">
		<?PHP 
			$providerInfo = get_user_meta($user->ID, 'loggedInViaGeneralOAuth', true); 
			echo "<pre>$providerInfo</pre>"; ?>
		</span>
	</td>
	</tr>
	<tr>
		<th><label for="image">Profile pic</label></th>
	<td>
		<span class="description">
		<?PHP 
			$link = get_user_meta($user->ID, 'oauth_user_profile_pic', true); 
			echo "<img width='64px' height='64px' src='$link'/>"; ?>
		</span>
	</td>
	</tr>
</table>
<?PHP
return $user;
}

function execute_ALSDKFLSDMC347529DFIDK823($OAUTH_PROVIDER, $whereToReturnOnSuccess="")
{	
	try{
		$oauth = new \MyanOAuth\myan_OAuth_Information($OAUTH_PROVIDER, $whereToReturnOnSuccess);
		$oauth->renderHtmlButton();
		$oauth->js_connect_info();
	}catch(Exception $e)
	{
		echo "<script type='text/javascript'>if(console) console.log('Error in providing OAuth plugin: { $e->getMessage()}')</script>";
	}
}



class oauth_options_page {
	function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'widgets_init', array( $this, 'widget_menu') );
		add_filter( 'login_message', array( $this, 'login_message_customOAuth') );
		add_action( 'wp_head', array( $this, 'open_graph_tags_inject') );
	}
	function widget_menu() {
		include_once('widget.php');
		register_widget( '\MyanOAuth\OAuthButton_Widget' );
	}
	function admin_menu () {
		add_options_page('OAuth Appid Management','OAuth Settings','manage_options','general_oauth_appid', array( $this, 'general_oauth_menupage' ) );
		// add_options_page('OAUTH Login Information', 'OAUTH Login Manage', 'edit_posts', 'oauth-msg-to-user', array($this, 'msg_to_user'));
	}
	
	function login_message_customOAuth() {
		if( is_user_logged_in() == false && function_exists('execute_ALSDKFLSDMC347529DFIDK823'))
		{
			echo '<ul><li style="float:right;list-style:none; margin: 5px;">';
			execute_ALSDKFLSDMC347529DFIDK823('google', get_bloginfo('wpurl').'/wp-admin');
			echo '</li>';
			
			echo '<li style="float:right;list-style:none; margin: 5px;">';
			execute_ALSDKFLSDMC347529DFIDK823('facebook', get_bloginfo('wpurl').'/wp-admin');
			echo '</li></ul>';
		}
	}
	function open_graph_tags_inject(){
		global $post;
		$description = strip_tags(get_bloginfo('description'));

		$title = get_bloginfo('name') . ' | '. $description;
		
		$fbappid = get_option('facebook_oauth_appid_value', '1382449782041883');
		$ogImage = get_option('general_oauth_openGraphImage', 
				      "https://openclipart.org/image/2400px/svg_to_png/237756/KnightHorseback3.png");
?>
		<meta property="og:type" content="article"/>
		<meta property="og:site_name" content="<?PHP bloginfo('name'); ?>"/>
		<meta property="og:locale" content="en_US" />
		<meta property="og:url" content="<?php
			$ssl = is_ssl() ? "s" : "";
			echo "http{$ssl}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
		?>"/>
		<meta property='fb:app_id' content="<?PHP echo $fbappid; ?>" />
<?PHP		
		$author = wp_get_current_user()->display_name;
		
		if(is_single()) {
			the_post();
			$excerpt = get_the_excerpt();
			$description = $excerpt == '' ? $description : strip_tags($excerpt);
			$title = get_the_title();

			$authorID = get_the_author_meta( 'ID' );
			$oauthPersonID = get_user_meta($authorID, 'loggedInViaGeneralOAuth');
			if( count($oauthPersonID) > 0)
			{
				parse_str($oauthPersonID[0], $params);
				$author = $params['id'];
				?>
				<meta property="article:section" content="Individual Post" />
				<meta property="article:published_time" content="<?PHP the_time('c'); ?>" />
				<meta property="article:modified_time" content="<?PHP the_modified_time('c'); ?>" />
				<?PHP
			}
			
			// og:image
			if(has_post_thumbnail())
			{	
				$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
				$ogImage = $src[0];
			}
			rewind_posts();
		}
		
		$publisher = get_option('general_oauth_openGraphPublisher', "");
		if($publisher != "") { 
			//$author = $publisher;	
		?>
			<meta property="article:publisher" content="<?PHP echo $publisher; ?>" />
		<?PHP }
?>
		<meta property="og:title" content="<?php echo htmlentities($title, ENT_QUOTES); ?>" />
		<meta property="article:author" content="<?php echo $author; ?>" />
		<meta name="author" content="<?PHP echo $author; ?>" />
		<meta property="og:image" content="<?PHP echo $ogImage; ?>"/>
		<meta property="og:description" content="<?PHP echo htmlentities($description, ENT_QUOTES); ?>" />
<?PHP
	}
	
	function guidv4()
	{
		if (function_exists('com_create_guid') === true)
			return trim(com_create_guid(), '{}');

		$data = openssl_random_pseudo_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	function general_oauth_menupage(){
		if( isset($_GET['gplus_oauth_appid_value']) )
		{
			update_option("gplus_oauth_appid_value", $_GET['gplus_oauth_appid_value']);
		}
		if( isset($_GET['facebook_oauth_appid_value']) )
		{
			update_option("facebook_oauth_appid_value", $_GET['facebook_oauth_appid_value']);
		}
		if( isset($_GET['general_oauth_password']) )
		{
			$passwordOAuth = $_GET['general_oauth_password'];
			if (strlen($passwordOAuth) < 32) {
				$passwordOAuth = $this->guidv4();
			}
			update_option("general_oauth_password", $passwordOAuth);
		}
		if( isset($_GET['general_oauth_showProfilePic']) )
		{
			update_option("general_oauth_showProfilePic", $_GET['general_oauth_showProfilePic']);
		}
		if( isset($_GET['general_oauth_openGraphImage']) )
		{
			update_option("general_oauth_openGraphImage", $_GET['general_oauth_openGraphImage']);
		}
		if( isset($_GET['general_oauth_openGraphPublisher']) )
		{
			update_option("general_oauth_openGraphPublisher", $_GET['general_oauth_openGraphPublisher']);
		}
		$pageURL = get_bloginfo('wpurl'). "/wp-admin/options-general.php?page=general_oauth_appid";
		$valueForPassword = get_option('general_oauth_password');
		$valueFromWPGoogle = get_option('gplus_oauth_appid_value');
		$valueFromWPFacebook = get_option('facebook_oauth_appid_value');
		$valueForOGImage = get_option('general_oauth_openGraphImage', 'https://openclipart.org/image/2400px/svg_to_png/237756/KnightHorseback3.png');
		$valueFromWPFacebookPublisher = get_option('general_oauth_openGraphPublisher', null);
		$valueForProfilePic = get_option('general_oauth_showProfilePic');
		
		$inputValues = array();
		$inputValues[] = (object) array('message'=>'OAuth password for all user', 'value' => $valueForPassword, 'name' => 'general_oauth_password');
		$inputValues[] = (object) array('message'=>'Type a google app id*', 'value' => $valueFromWPGoogle, 'name' => 'gplus_oauth_appid_value');
		$inputValues[] = (object) array('message'=>'Type a facebook app id**', 'value' => $valueFromWPFacebook, 'name' => 'facebook_oauth_appid_value');
		$inputValues[] = (object) array('message'=>'Open graph image url', 'value' => $valueForOGImage, 'name' => 'general_oauth_openGraphImage');
		$inputValues[] = (object) array('message'=>'Publisher url to override author url', 'value' => $valueFromWPFacebookPublisher, 'name' => 'general_oauth_openGraphPublisher', 'isDisable' => true);

		foreach($inputValues as $userInput)
		{ 
			$disable = isset($userInput->isDisable);
		?>
			<h1><?PHP echo $userInput->message;?>:</h1> <form method='get' action='<?PHP echo $pageURL ?>'>
			
			<input type='text' class='regular-text' name='<?PHP echo $userInput->name;?>' value='<?PHP echo $userInput->value;?>' size='100' onclick='select()' />
			<input type='hidden' name='page' value='general_oauth_appid'/> 
			<input type='submit' class='button button-primary' <?PHP if($disable) echo "disabled='$disable'"; ?> value='Submit'/></form>
			<br/><strong>Current value:</strong> <?PHP echo $userInput->value;?>		
		<?PHP
		}
		?>
		<h1>Select choice on profile picture display:</h1> <form method='get' action='<?PHP echo $pageURL;?>'>
		<input type='radio' name='general_oauth_showProfilePic' value='show'>Yes, 'show' from OAuth provider<br>
		<input type='radio' name='general_oauth_showProfilePic' value='hide'>No, 'hide' it and show default<br>
		<input type='hidden' name='page' value='general_oauth_appid'/> <input type='submit' class='button button-primary' value='Submit'/></form>
		<br/><strong>Current value:</strong> <?PHP echo $valueForProfilePic; ?>
		
		<hr/>
		<br/>*=Get a google app id <a href='https://console.developers.google.com/apis/credentials' target='_blank'>here</a> and test <a href='https://developers.google.com/apis-explorer/'>here</a>
		<br/>**=Get a facebook app id <a href='https://developers.facebook.com/' target='_blank'>here</a>, and test <a href='https://developers.facebook.com/tools/explorer/145634995501895'>here</a>
		<?PHP
	}
	function msg_to_user(){
		$current_user = wp_get_current_user();
		$displayName = $current_user->display_name;
		echo "<h2>Dear $displayName,</h2>Thank you for creating your account. <br/><br/>If you chose to delete apps for connecting to this site, then please follow the suitable instruction for you:<ol>";
		echo "<li>If you have logged in via <strong>Google OAuth</strong> and wish to disconnect this app, then you can do it <a target='_blank' href='https://www.google.com/accounts/IssuedAuthSubTokens?hl=en'>here</a></li>";
		echo "<li>If you have logged in via <strong>Facebook connect</strong> and wish to disconnect this app, then you can do it <a target='_blank' href='https://www.facebook.com/settings?tab=applications'>here</a></li>";
		echo '</ol>';
		
		echo "<br/><br/>Regards,<br/>-admin.";
	}
}
new oauth_options_page;
?>
