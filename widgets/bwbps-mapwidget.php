<?php

class PhotoSmash_Map_Widget extends WP_Widget {

	function PhotoSmash_Map_Widget(){
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'photosmash_map', 'description' => 'Displays a Google Map widget' );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => 'psmap-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'psmap-widget', 'PhotoSmash Map', $widget_ops, $control_ops );

	
	}
	
	function widget( $args, $instance ) {
		global $bwbPS;
		extract( $args );
		
		$map_id = PixooxHelpers::alphaNumeric( $instance['map_id'], false, true );		
		if($map_id == 'widget'){ $map_id = 'gmap_widget'; }
		$map_id = $map_id ? $map_id : 'gmap_widget';

		
		// Checks to see if a gallery has already loaded Map code...if not, bombs out
		if( (int)$instance['when_called'] && ( !is_array($bwbPS->gmaps) || empty($bwbPS->gmaps[ $map_id ]) ) ){ return; }
		
		/* User-selected settings. */
		$title = apply_filters('widget_title', $instance['title'] );
				
		$div_height = (int)$instance['div_height'] ? (int)$instance['div_height'] : 350;
		$div_width = (int)$instance['div_width'] ? (int)$instance['div_width'] : 250;
		
		//Tag Galleries
		if($instance['gallery_type'] == "tags"){
			$tags = strip_tags($instance['tags']);
			if( $tags ){
				$tags = ' tags="' . esc_attr($tags) .'"';
			}
		}
		
		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Title of widget (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

			
		$sc = "[photosmash_gmap id=$map_id height=$div_height width=$div_width]";
				
		echo do_shortcode($sc);
		
		
		/* After widget (defined by themes). */
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );
		$new_instance['map_id'] = str_replace("-", "_", $new_instance['map_id']);
		$instance['map_id'] = PixooxHelpers::alphaNumeric( strip_tags( $new_instance['map_id'] ), false, true) ;
		
		$instance['map_id'] = ($instance['map_id'] == 'widget') ? 'gmap_widget' : $instance['map_id'];
		
		$instance['when_called'] = isset( $new_instance['when_called'] ) ? 1 : 0;
		$instance['div_height'] = (int)( $new_instance['div_height'] );
		$instance['div_width'] = (int)( $new_instance['div_width'] );
		
		return $instance;
	}
	
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 
			'title' => 'Mapped Images',
			'map_id' => 'gmap_widget',
			'div_height' => 350,
			'div_width' => 250
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label><br/>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>"   />
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'map_id' ); ?>">Map ID :</label><br/>
			<input id="<?php echo $this->get_field_id( 'map_id' ); ?>" name="<?php echo $this->get_field_name( 'map_id' ); ?>" value="<?php echo $instance['map_id']; ?>"   /> 
			<br/>
			<span style='font-size: 9px;'>You will specify this map ID in your gallery</span><br/>
			<span style='font-size: 9px;'>shortcode to show its images on this map.</span>
			<span style='font-size: 9px;'>Example: [photosmash gmap='gmap_widget']</span>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'when_called' ); ?>">Show only when used:</label><br/>
			<input type='checkbox' id="<?php echo $this->get_field_id( 'when_called' ); ?>" name="<?php echo $this->get_field_name( 'when_called' ); ?>" <?php if( $instance['when_called'] ){ echo 'checked=checked'; } ?>   /> <span style='font-size: 9px;'>only show when used by a gallery</span>
		</p>
			
		<p>
			<label for="<?php echo $this->get_field_id( 'div_height' ); ?>">Width x Height:</label><br/>
			<input style="width: 50px; padding:3px;" id="<?php echo $this->get_field_id( 'div_width' ); ?>" name="<?php echo $this->get_field_name( 'div_width' ); ?>" value="<?php echo (int)$instance['div_width']; ?>"   /> x 			
			<input style="width: 50px; padding:3px;" id="<?php echo $this->get_field_id( 'div_height' ); ?>" name="<?php echo $this->get_field_name( 'div_height' ); ?>" value="<?php echo (int)$instance['div_height']; ?>"   /> (px)<br/>(optional)
		</p>
		
		<?php
	}
	
}

?>