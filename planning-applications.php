<?php
/*
Plugin Name: Planning Applications
Plugin URI: http://philipjohn.co.uk/category/plugins/planning-applications/
Description: A WordPress plugin that provides a widget for displaying nearby planning applications, based on data from OpenlyLocal.com
Version: 0.1
Author: Philip John
Author URI: http://philipjohn.co.uk
License: GPL2

    Copyright (C) 2012 Philip John Ltd

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/*
 * Localise the plugin
 */
load_plugin_textdomain('planning-alerts');

/**
 * Widget class
 */
class Planning_Applications extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_planning_applications', 'description' => __('Add a list of recent planning applications to your sidebar'));
		$control_ops = array('width' => 400, 'height' => 350);
		parent::__construct('planning_applications', __('Planning Applications'), $widget_ops, $control_ops);
	}

	function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance, $this->id_base);
		$council_id = empty($instance['council_id']) ? 156 : $instance['council_id'];
		$limit = empty($instance['limit']) ? 10 : $instance['limit'];
		$show_addr = isset($instance['show_addr']);
		
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 
		echo $this->widget_content($council_id, $limit, $show_addr);
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['council_id'] =  absint($new_instance['council_id']);
		$instance['limit'] =  absint($new_instance['limit']);
		$instance['show_addr'] =  isset($new_instance['show_addr']);
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'council_id' => 156, 'limit' => 10 ) );
		$title = strip_tags($instance['title']);
		$council_id = absint($instance['council_id']);
		$limit = absint($instance['limit']);
		$show_addr = isset($instance['show_addr']);
		
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		
		<p><label for="<?php echo $this->get_field_id('council_id'); ?>"><?php _e('Council:'); ?></label>
		<?php echo $this->councils_drop_down($council_id, $this->get_field_id('council_id'), $this->get_field_name('council_id')); ?>
		
		<p><label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Limit:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="text" value="<?php echo esc_attr($limit); ?>" /></p>
		
		<p><input id="<?php echo $this->get_field_id('show_addr'); ?>" name="<?php echo $this->get_field_name('show_addr'); ?>" type="checkbox" <?php checked(isset($instance['show_addr']) ? $instance['show_addr'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('show_addr'); ?>"><?php _e('Show address'); ?></label></p>
		
		<?php
	}
	
	// get the councils drop down
	function councils_drop_down($council_id = false, $field_id, $field_name){
		$data = $this->get_ol_data('http://openlylocal.com/councils.json#source=pjap_widget');
		
		if (empty($data)){
			return false;
		} else {
			$select = '<select name="'.$field_name.'" id="'.$field_id.'">';
			foreach($data->councils as $council){
				$select .= '<option value="'.$council->id.'"';
				$select .= ($council_id == $council->id) ? ' selected="selected"' : '';
				$select .= '>'.$council->name.'</option>';
			}
			$select .= '</select>';
			return $select;
		}
	}
	
	// wrapper function for CURL stuff
	function get_ol_data($url){
		$curl_handle=curl_init();
		curl_setopt($curl_handle,CURLOPT_URL,$url);
		curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
		curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);
		
		$buffer = json_decode($buffer);
		return $buffer;
	}
	
	// Return the HTML list of planning apps
	function widget_content($council_id = 156, $limit = 10, $show_addr = false){
		$data = $this->get_ol_data('http://openlylocal.com/councils/'.$council_id.'/planning_applications.json#source=pjap_widget');
		
		if (empty($data)){
			return false;
		} else {
			$i = 0;
			$list = '<ul>';
			foreach ($data->planning_applications as $app){
				$list .= '<li><a href="'.$app->url.'">'.$app->description.'</a>';
				$list .= ($show_addr) ? ' <span class="address">'.$app->address.'</span>' : '';
				$list .= '</li>';
				
				// update the counter and stop if limit reached
				$i++; if ($i>=$limit){ break; }
			}
			$list .= '</ul>';
			return $list;
		}
	}

}
function pjpa_widget_init(){
	register_widget('Planning_Applications');
}
add_action('widgets_init', 'pjpa_widget_init', 1);
?>