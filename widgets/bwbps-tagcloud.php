<?php

class PhotoSmash_TagCloud extends WP_Widget {

	function PhotoSmash_TagCloud(){
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'PhotoSmash Tag Cloud', 'description' => 'Displays a Tag Cloud of PhotoSmash Image tags' );

		/* Widget control settings. */
		$control_ops = array( 'width' => 250, 'height' => 350, 'id_base' => 'photosmash-tags-widget' );

		/* Create the widget. */
		$this->WP_Widget( 'photosmash-tags-widget', 'PhotoSmash Tag Cloud', $widget_ops, $control_ops );

	
	}
	
	function widget( $args, $instance ) {
		extract( $args );

		/* User-selected settings. */
		$title = apply_filters('widget_title', $instance['title'] );
				
		/* Before widget (defined by themes). */
		echo $before_widget;

		/* Title of widget (before and after defined by themes). */
		if ( $title )
			echo $before_title . $title . $after_title;

		/* display the tag cloud */
		wp_tag_cloud( array( 'taxonomy' => 'photosmash' ) );

		
		/* After widget (defined by themes). */
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		/* Strip tags (if needed) and update the widget settings. */
		$instance['title'] = strip_tags( $new_instance['title'] );
		
		return $instance;
	}
	
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 
			'title' => 'Photo Tags'
		);
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		
		
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label><br/>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>"   />
		</p>
				
		<?php
	}
	


}

?>