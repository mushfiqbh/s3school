<?php

function s3s_load_widget() {
	register_widget( 's3s_latestNews' );
	register_widget( 's3s_latestNotice' );
}
add_action( 'widgets_init', 's3s_load_widget' );



/*
* Latest News
*/

class s3s_latestNews extends WP_Widget {

	function __construct() {
		parent::__construct(
			'latestNews',
			__('Latest News', 's3school'),
			array( 'description' => __( '', 's3school' ), )
		);
	}

	// Creating widget front-end
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		?>
		<div class="b-aside-item">
			<div class="aside-popular">
				<?php if ( ! empty( $title ) ){?>
					<h5 class="aside-title">
						<?= $title ?>
						<i class="fa fa-bolt" aria-hidden="true"></i>
					</h5>
				<?php } ?>
				<?php
					$args = [
					  'post_status'   => 'publish',
					  'category_name' => 'latest-news'
					];
					$the_query = new WP_Query( $args );
					if( $the_query->have_posts() ) {
						echo '<div class="b-popular">';
						while ( $the_query->have_posts() ) {
							$the_query->the_post(); ?>

							<div class="blog-item-content clearfix">
								<div class="blog-item-caption">
									<div class="item-data">
										<?= get_post_time('j M Y. h:i a', true) ?>
									</div>
									<h4 class="item-name">
										<a href="<?= the_permalink(); ?>"><?php the_title() ?></a>
									</h4>
								</div>
							</div> <?php
						}
						echo "</div>";
					}
				?>
			</div>
		</div>
		<?php
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Latest News', 's3school' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}


/*
* Latest Notice
*/

class s3s_latestNotice extends WP_Widget {

	function __construct() {
		parent::__construct(
			'latestNotice',
			__('Latest Notice', 's3school'),
			array( 'description' => __( '', 's3school' ), )
		);
	}

	// Creating widget front-end
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		?>
		<div class="b-aside-item">
			<div class="aside-popular">
				<?php if ( ! empty( $title ) ){?>
					<h5 class="aside-title">
						<?= $title ?>
						<i class="fa fa-bolt" aria-hidden="true"></i>
					</h5>
				<?php } ?>
				<?php
					$args = [
					  'post_status'   => 'publish',
					  'category_name' => 'latest-notice'
					];
					$the_query = new WP_Query( $args );
					if( $the_query->have_posts() ) {
						echo '<div class="b-popular">';
						while ( $the_query->have_posts() ) {
							$the_query->the_post(); ?>

							<div class="blog-item-content clearfix">
								<div class="blog-item-caption">
									<div class="item-data">
										<?= get_post_time('j M Y. h:i a', true) ?>
									</div>
									<h4 class="item-name">
										<a href="<?= the_permalink(); ?>"><?php the_title() ?></a>
									</h4>
								</div>
							</div> <?php
						}
						echo "</div>";
					}
				?>
			</div>
		</div>
		<?php
	}

	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Latest Notice', 's3school' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}

?>