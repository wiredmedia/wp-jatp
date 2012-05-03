<?php
/*
Plugin Name: WP JATP (Wired Media)
Plugin URI: 
Description: Twitter feed, display using a widget, shortcode, or call directly in your template files. Forked code from 'Wickett Twitter Widget' Version: 1.0.6 http://wordpress.org/extend/plugins/wickett-twitter-widget
Version: 1.0
Author: Wired Media (ralcus)
Author URI: http://wiredmedia.co.uk
License: GPLv2

Requires php5.3+

TODO
- add short code for showing tweets
*/

/**
 * load Dependencies
 */
require( plugin_dir_path( __FILE__ ) . 'time-since.class.php' );
require( plugin_dir_path( __FILE__ ) . 'twitter.class.php' );

class Wired_JATP extends \WP_Widget {

	function Wired_JATP() {
		$widget_ops = array('classname' => 'widget_twitter', 'description' => __( 'Display your tweets from Twitter') );
		parent::WP_Widget('twitter', __('Twitter'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract( $args );
    
		$screen_name = trim( urlencode( $instance['screen_name'] ) );
		if ( empty($screen_name) ) return;
		$title = apply_filters('widget_title', $instance['title']);
		if ( empty($title) ) $title = __( 'Twitter' );
		$count = absint( $instance['count'] );  // # of Updates to show
		if ( $count > 200 ) // Twitter paginates at 200 max tweets. update() should not have accepted greater than 20
			$count = 200;
		$exclude_replies = (bool) $instance['exclude_replies'];

		echo "{$before_widget}{$before_title}<a href='" . esc_url( "http://twitter.com/{$screen_name}" ) . "'>" . esc_html($title) . "</a>{$after_title}";
    
    $args = array(
  		'screen_name' => $screen_name,
  		'count' => $count,
  		'exclude_replies' => $exclude_replies
  	);
    the_tweets($args);
		
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['screen_name'] = trim( strip_tags( stripslashes( $new_instance['screen_name'] ) ) );
		$instance['screen_name'] = str_replace('http://twitter.com/', '', $instance['screen_name']);
		$instance['screen_name'] = str_replace('/', '', $instance['screen_name']);
		$instance['screen_name'] = str_replace('@', '', $instance['screen_name']);
		$instance['screen_name'] = str_replace('#!', '', $instance['screen_name']); // screen_name for the Ajax URI
		$instance['title'] = strip_tags(stripslashes($new_instance['title']));
		$instance['count'] = absint($new_instance['count']);
		$instance['exclude_replies'] = isset($new_instance['exclude_replies']);

		wp_cache_delete( 'widget-twitter-' . $this->number , 'widget' );
		wp_cache_delete( 'widget-twitter-response-code-' . $this->number, 'widget' );

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('screen_name' => '', 'title' => '', 'count' => 5, 'exclude_replies' => false) );

		$screen_name = esc_attr($instance['screen_name']);
		$title = esc_attr($instance['title']);
		$count = absint($instance['count']);
		if ( $count < 1 || 20 < $count )
			$count = 5;
		$exclude_replies = isset($instance['exclude_replies']) ? (bool) $instance['exclude_replies'] : false;

		echo '<p><label for="' . $this->get_field_id('title') . '">' . esc_html__('Title:') . '
		<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('screen_name') . '">' . esc_html__('Twitter username:') . '
		<input class="widefat" id="' . $this->get_field_id('screen_name') . '" name="' . $this->get_field_name('screen_name') . '" type="text" value="' . $screen_name . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('count') . '">' . esc_html__('Maximum number of tweets to show:') . '
			<select id="' . $this->get_field_id('count') . '" name="' . $this->get_field_name('count') . '">';

		for ( $i = 1; $i <= 20; ++$i )
			echo "<option value='$i' " . ( $count == $i ? "selected='selected'" : '' ) . ">$i</option>";

		echo '		</select>
		</label></p>
		<p><label for="' . $this->get_field_id('exclude_replies') . '"><input id="' . $this->get_field_id('exclude_replies') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('exclude_replies') . '"';
		if ( $exclude_replies )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Hide replies') . '</label></p>';
		
	}

}// END: Wired_JATP

/* register widget with wp widget factory */
add_action( 'widgets_init', 'wired_JATP_widget_init' );
function wired_JATP_widget_init() {
	register_widget('Wired_JATP');
}



/* enable calling of the widget via a shortcode
/*-----------------------------------------------------------------------------------*/
/*
// [bartag foo="foo-value"]
function bartag_func( $atts ) {
	extract( shortcode_atts( array(
		'foo' => 'something',
		'bar' => 'something else',
	), $atts ) );

	return "foo = {$foo}";
}
add_shortcode( 'bartag', 'bartag_func' );
*/