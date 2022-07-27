<?PHP
namespace MyanOAuth;

include_once("myan_OAuth_Information.php");

class OAuthButton_Widget extends \WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => '',
			'description' => 'OAuth Facebook/Google'
		);
		parent::__construct( 'myan_widget_oauth', 'Login via: Facebook/Google', $widget_ops );
	}
	
	private function writeWidgetHTML($provider)
	{
		echo '<div style="margin: 0px 10px 25px 0px;float:left;">';
			execute_ALSDKFLSDMC347529DFIDK823($provider);
		echo '</div>';
	}
	
	// User interface
	public function widget( $args, $instance ) {
		if( is_user_logged_in() == false && function_exists('execute_ALSDKFLSDMC347529DFIDK823'))
		{?>
		<div class="">
			<div class="widget-title" style="margin-bottom:5px"><h2 class="title">সহজেই লগইন করুন</h2></div>
		<?PHP
			if($instance['gplus_oauth_providerGoogle'])
			{	
				$this->writeWidgetHTML('google');
			}
			if($instance['facebook_oauth_fbCheck'])
			{
				$this->writeWidgetHTML('facebook');
			}
		?>
		</div>
		<?PHP	
		}
	}

	// Admin panel
	public function form( $instance ) {
	    $defaults = array (
			'facebook_oauth_fbCheck' => !empty( $instance['facebook_oauth_fbCheck'] ) ? $instance['facebook_oauth_fbCheck'] : true,
			'gplus_oauth_providerGoogle' => !empty($instance['gplus_oauth_providerGoogle']) ? $instance['gplus_oauth_providerGoogle'] : true
		);
		$instance = wp_parse_args( (array) $instance, $defaults);
		?>
		<p>
		<input name="<?php echo $this->get_field_name( 'facebook_oauth_fbCheck' ); ?>"  type="checkbox" <?php echo checked($instance['facebook_oauth_fbCheck'],'on'); ?> />Facebook<br>
		<input name="<?php echo $this->get_field_name( 'gplus_oauth_providerGoogle' ); ?>" type="checkbox" <?php echo checked($instance['gplus_oauth_providerGoogle'],'on'); ?> />Google
		</p>
		<?php 
	}
	
	// Admin panel + New values updated
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['facebook_oauth_fbCheck'] = $new_instance['facebook_oauth_fbCheck'];
		$instance['gplus_oauth_providerGoogle'] = $new_instance['gplus_oauth_providerGoogle'];
		return $instance;
	}

} // class Foo_Widget


?>