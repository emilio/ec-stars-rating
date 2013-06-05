<?php
	class EC_Stars_Rating_Best_Rated_Widget extends WP_Widget {
		public function __construct() {
			$this->WP_Widget(
			// parent::__construct(
		 		'ec_stars_rating_best_rated', // Base ID
				__('EC stars rating\'s best rated posts', ECStarsRating::$textdomain), // Name
				array( 'description' => __( 'Best rated posts widget', ECStarsRating::$textdomain ), ) // Args
			);
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
			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );

			echo $before_widget;
			if ( ! empty( $title ) ) {
				echo $before_title . $title . $after_title;
			}
			$this->render($instance['count']);
			echo $after_widget;
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
			// Save the title
			$instance['title'] = strip_tags( $new_instance['title'] );
			// The post count
			if( is_numeric($new_instance['count']) ) {
				$instance['count'] = (int) $new_instance['count'];
			}
			return $instance;
		}

		/**
		 * Back-end widget form.
		 *
		 * @see WP_Widget::form()
		 *
		 * @param array $instance Previously saved values from database.
		 */
		public function form( $instance ) {
			$title = isset( $instance[ 'title' ] )  ? $instance[ 'title' ] : __( 'Top Rated Posts', ECStarsRating::$textdomain );
			$count = isset( $instance[ 'count' ] )  ? $instance[ 'count' ] : 5;
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of posts to display:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" min="1" value="<?php echo esc_attr( $count ); ?>" />
			</p>
			<?php 
		}

		/**
		 * Internal function where the posts are fetched and shown
		 * @param int $count the post count
		 * @return void
		 */
		public function render($count) {
			// We just order by total result (the sum of all results)
			// This means that a post with 500 one stars' votes will be the same in order than one with 100 five stars' votes
			// Reasons for this are:
			// * Prevent posts with 5 stars / 1 vote appear in above posts with 4.8 stars, 20 votes
			// * Simplify the query
			$query = new WP_Query(array(
				'post_status' => 'publish',
				'meta_key' => 'ec_stars_rating',
				'orderby' => 'meta_value_num',
				'order' => 'DESC',
				'posts_per_page' => $count,
				'paged' => 1
			));
			echo '<ol class="ec-stars-rating-plugin-list">';
			/** Loop over the results */
			while ( $query->have_posts() ) {
				$query->the_post();
				echo '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
			}
			echo '</ol>';
			/** Reset the data */
			wp_reset_postdata();
		}
	}
