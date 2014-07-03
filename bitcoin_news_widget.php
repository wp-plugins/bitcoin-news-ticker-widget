<?php
/*
    Plugin Name: Bitcoin News Widget
    Plugin URI: 99bitcoins.com/bitcoin-news-ticker-widget-plugin-for-wordpress/
    Description: Displays a widget on your site of latest Bitcoin News
    Author: Ofir Beigel
    Version: 1.0.9
    Author URI: ofir@99bitcoins.com
*/

DEFINE("BITCOIN_NEWS_URL","http://feed.informer.com/digests/S0N5HYYTKJ/feeder.atom");
DEFINE("BITCOIN_NEWS_CACHE_DURATION",300); // 5 minutes, because API is regenerated every 5 minutes

function bitcoin_news_update_data(){

	$response = wp_remote_get( BITCOIN_NEWS_URL , array(
		"sslverify" => false,
		"timeout" => 10
	) );

	$btw_options_news_data = get_option("btw_options_news_data");

	$update_time = time();

	if( !$btw_options_news_data ) $btw_options_news_data = array();

	if( !$btw_options_news_data["data"] )
		$btw_options_news_data["data"] = array( 
			"news" => array() ,
			'updated' => $update_time
		);

	if ( is_wp_error( $response ) ):

		$btw_options_news_data["data"]["updated"] = $update_time;

		update_option( "btw_options_news_data" , array(
			"last_updated" => $update_time,
			"data" => $btw_options_news_data["data"]
		) );
		
		return;

	endif;
	
	if (!isset($response['body'])) {
		return;
	}
	
	$xml = simplexml_load_string($response['body']);
	$dataCounter = 0;
	$xmlresponse = array();
	foreach ($xml->entry as $en) {
	//var_dump($en);
		$xmlresponse['body'][$dataCounter]['url'] = (string)$en->link['href'];
		$xmlresponse['body'][$dataCounter]['host'] = (string)$en->source->link['href'];
		$xmlresponse['body'][$dataCounter]['title'] = (string)$en->title;
		$xmlresponse['body'][$dataCounter]['timestamp'] = (string)$en->updated;
		$dataCounter++;
	}

	//$json = json_decode( $xmlresponse["body"] , true );
	$json = $xmlresponse["body"];

	if( isset( $json["error"] ) && $json["error"] == true ):

		$btw_options_news_data["data"]["updated"] = $update_time;

		update_option( "btw_options_news_data" , array(
			"last_updated" => $update_time,
			"data" => $btw_options_news_data["data"]
		) );
	else :

		$json["updated"] = $update_time;

		update_option( "btw_options_news_data" , array(
			"last_updated" => $update_time,
			"data" => $json
		) );

	endif;
}

function bitcoin_news_get_options( $update = true){
	
	// Get Bitcoin news data
	$btw_options_news_data = get_option( "btw_options_news_data" );
	if( $update && ( !$btw_options_news_data || $btw_options_news_data["last_updated"] < time() - BITCOIN_NEWS_CACHE_DURATION ) ):
		bitcoin_news_update_data();
		$btw_options_news_data = get_option( "btw_options_news_data" );
	endif;
	
	$data = $btw_options_news_data['data'];

	return $data;
}


/**
 * Proper way to enqueue scripts and styles
 */
function bitcoin_news_ticker_scripts() {
	wp_enqueue_style( 'bitcoin-news-style',  plugin_dir_url(__FILE__) . 'css/style.css' );
	wp_enqueue_style( 'bitcoin-news-scrollbar-style',  plugin_dir_url(__FILE__) . 'plugin/custom-scrollbar-plugin/demo_files/jquery.mCustomScrollbar.css' );

        //if(!wp_script_is('jquery'))
           //wp_enqueue_script( 'bitcoin-news-jquery', plugin_dir_url(__FILE__) . 'js/jquery_1.10.js', array('jquery'), '', true );
			
		//wp_enqueue_script( 'bitcoin-news-jquery', plugin_dir_url(__FILE__) . 'js/jquery_1.10.js', array('jquery'), '', true );
		wp_enqueue_script( 'bitcoin-news-mCustomScrollbar', plugin_dir_url(__FILE__) . 'plugin/custom-scrollbar-plugin/js/uncompressed/jquery.mCustomScrollbar.js', array('jquery'), '', true );
}

function bitcoin_news_ticker_admin_scripts() {
		wp_enqueue_style( 'bitcoin-news-style',  plugin_dir_url(__FILE__) . 'css/style.css' );

        if(!wp_script_is('jquery'))
            wp_enqueue_script( 'jquery');
}

add_action( 'wp_enqueue_scripts', 'bitcoin_news_ticker_scripts' );
add_action( 'admin_enqueue_scripts', 'bitcoin_news_ticker_admin_scripts' );


/**
 * Adds Bitcoin widget.
 */

global $bitcoin_news_widget_index;

$bitcoin_news_widget_index = 0;

class Bitcoin_News_Widget extends WP_Widget {
	public $bitcoin_news_error;
	//public $error_msg;

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'bitcoin_news_widget', // Base ID
			'Bitcoin News Widget', // Name
			array( 'description' => __( 'Bitcoin News Widget', 'text_domain' ), ) // Args
		);
		
		$this->bitcoin_news_error = new WP_Error();
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$link = apply_filters( 'widget_title', $instance['link'] );
		echo $args['before_widget'];
		if (empty($instance)) {
			$instance['show_footer'] = '1';
		}

		$bitcoin_news_data = bitcoin_news_get_options();
		
		global $bitcoin_news_widget_index;

		$bitcoin_news_widget_index++;
		?>
		
		
		<!-- custom scrollbars plugin -->
		
		<div class="bitcoin_news_container" style="display:none;">
			<div class="bitcoin_news_widget_container">
				<div class="widget_frame">
					<div class="widget_content_container">
					<?php 
						for ( $counter = 0; $counter < count($bitcoin_news_data) - 1; $counter++ ) {
							$news_domain = parse_url($bitcoin_news_data[$counter]['url']);
							$site_domain = str_replace("www.", "", $news_domain['host']);
							$domain_split = explode(".", $site_domain);
							unset($domain_split[count($domain_split) - 1]);
							$domain_string = implode(" ", $domain_split);
							$site_domain = ucwords($domain_string);
							if (strlen($site_domain) > 25) {
								$display_domain = substr($site_domain, 0, 25).'...';
							}
							else {
								$display_domain = $site_domain;
							}
					?>
						<div class="news_container">
							<h1><a rel="nofollow" target="_blank" href="<?php echo $bitcoin_news_data[$counter]['url'];?>"><?php echo $bitcoin_news_data[$counter]['title'];?></a></h1>
							<div class="author" title="<?php echo $site_domain; ?>"><?php echo $display_domain;?></div>
							<div class="date"><?php echo date("M d", strtotime($bitcoin_news_data[$counter]['timestamp']));?></div>
							<div class="clear"></div>
						</div>
					<?php
					}
					?>
									 
					</div>
					<?php if ($instance['show_footer'] == '1') { ?><a rel="nofollow" target="_blank" href="http://99bitcoins.com/"><div class="bitcoin_news_logo"></div></a><?php } ?>
				</div>
			</div>
		</div>
		
		<script>
			jQuery(document).ready(function($){
					$(".widget_content_container").mCustomScrollbar({
						theme:'dark-thin',
						scrollButtons:{
							enable:true						
						}
					});
					
					jQuery(".bitcoin_news_container").css('opacity','1');
					
			});
			
			jQuery(".bitcoin_news_container").css("display","block");
			jQuery(".bitcoin_news_container").css({
					'opacity':0,
					'max-height':'420px'
				});
			
		</script>
			
                <?php
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
	
		//return;
						
		if( $instance) 
		{
			$show_footer = esc_attr($instance['show_footer']);
		}
		else
		{
			$show_footer = '1';
		}
	?>	
		<div>
			
			<table class="bt-table">
				<tr>
					<td>Show Widget Footer</td>
					<td><input type="checkbox" id="<?php echo $this->get_field_id('show_footer'); ?>" name="<?php echo $this->get_field_name('show_footer'); ?>" <?php if($show_footer!='' && $show_footer=='1') echo "checked" ;?> value="1" /></td>
					<td></td>
				</tr>
				
			</table>
			
		</div>
	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		
		$instance['show_footer'] = ( ! empty( $new_instance['show_footer'] ) ) ? strip_tags( $new_instance['show_footer'] ) : '';
		
		
		return $instance;
	}

} // class Bitcoin_News_Widget

function register_bitcoin_news_widget(){
    register_widget( 'Bitcoin_News_Widget' );
}
add_action( 'widgets_init', 'register_bitcoin_news_widget');


function bitcoin_news_activate() {

    // Activation code here...
    if(!function_exists('curl_version')){
        deactivate_plugins(__FILE__);
        wp_die('This plugin requires PHP CURL module which is not enabled on your server. Please contact your server administrator');
    }
	
}
register_activation_hook( __FILE__, 'bitcoin_news_activate' );
