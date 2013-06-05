<?php
/*
Plugin Name: EC Stars Rating
Plugin URI: http://emiliocobos.net/ec-stars-rating-wordpress-plugin
Description: EC Stars rating es el sistema de calificación por estrellas más sencillo y ligero que podrás encontrar en todo el directorio
Version: 1.0.1
Author: Emilio Cobos
Author URI: http://emiliocobos.net/
*/

/**
 * Base plugin class
 * @author Emilio Cobos <http://emiliocobos.net/>
 */
class ECStarsRating {
	/**
	 * Variables privadas para el plugin
	 */
	public $url;
	public $path;

	/**
	 * Response statuses
	 */
	private $STATUS_UNKNOWN = -1;
	private $STATUS_PREVIOUSLY_VOTED = 0;
	private $STATUS_SUCCESS = 1;
	private $STATUS_REQUEST_ERROR = 2;

	/**
	 * Create the required actions for the script to appear
	 * @uses add_action
	 * @see ECStarRating::head()
	 */
	public function __construct() {
		// $this->url = plugin_dir_url(__FILE__);
		// $this->path = plugin_dir_path(__FILE__);

		// Add the head script and styles
		add_action('wp_head', array($this, 'head'));

		// Set the options
		register_activation_hook(__FILE__, array($this, '_set_options'));
		register_deactivation_hook(__FILE__, array($this, '_unset_options'));

		add_action('admin_init', array($this, '_register_settings'));
		add_action('admin_menu', array($this, '_add_menu_page'));

		// AJAX functionality for admin and for users
		add_action('wp_ajax_ec_stars_rating', array($this, '_handle_vote'));
		add_action('wp_ajax_nopriv_ec_stars_rating', array($this, '_handle_vote'));
	}
	/**
	 * Echoes the main Javascript and CSS
	 * @return void
	 */
	public function head() {
		$this->headScript();
		$this->headCSS();
	}

	/**
	 * Enqueue the main script, usin jQuery or not
	 * @return void
	 * @see wp_enqueue_script
	 */
	public function headScript() {
		if( get_option('ec_stars_rating_use_jquery') ) {
			wp_enqueue_script( 'ec-stars-script', plugins_url( '/js/ec-stars-rating.js', __FILE__ ), array('jquery'));
		} else {
			wp_enqueue_script( 'ec-stars-script', plugins_url('/js/ec-stars-rating-nojq.js', __FILE__));
		}
		// The script with our messages, url, and status codes
		wp_localize_script( 'ec-stars-script', 'ec_ajax_data', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'codes' => array(
				'SUCCESS' => $this->STATUS_SUCCESS,
				'PREVIOUSLY_VOTED' => $this->STATUS_PREVIOUSLY_VOTED,
				'REQUEST_ERROR' => $this->STATUS_REQUEST_ERROR,
				'UNKNOWN' => $this->STATUS_UNKNOWN
			),
			'messages' => array(
				'success' => __('Has votado correctamente'),
				'previously_voted' => __('Ya habías votado anteriormente'),
				'request_error' => __('La solicitud ha sido mal formada'),
				'unknown' => __('Ha ocurrido un error desconocido')
			)
		));
	}

	/**
	 * Get the cookie name used for avoid duplicate votes
	 * @param int post_id
	 * @return string the cookie name
	 */
	public function getCookieName($post_id) {
		return 'ec_sr_' . $post_id;
	}

	/**
	 * Get the table name used for avoid duplicate votes
	 * @return string the table name
	 */
	public function getTableName() {
		global $wpdb;
		return $wpdb->prefix . 'ec_stars_votes';
	}

	/**
	 * The head CSS, with the custom options
	 */
	public function headCSS() {
		?>
		<style id="ec_stars_rating_head_css">
			.ec-stars-wrapper {
				font-size: 0;
				display: inline-block;
				position: relative;
			}
			.ec-stars-wrapper[data-tooltip]:hover:before {
				content: attr(data-tooltip);
				position: absolute;

				bottom: 90%;
				left: 50%;
				text-align: center;
				max-width: 100px;
				margin-left: -50px;
				
				background: rgba(0,0,0,.7);
				color: white;
				font-size: 10px;
				border-radius: 3px;
				padding: 3px;

			}
			.ec-stars-wrapper a {
				text-decoration: none;
				display: inline-block;
				font-size: <?php echo get_option('ec_stars_rating_size') ?>px;
				color: <?php echo get_option('ec_stars_rating_hover_color') ?>;
			}

			.ec-stars-wrapper:hover a,
			.ec-stars-wrapper.is-voted a {
				color: <?php echo get_option('ec_stars_rating_hover_color'); ?>;
			}
			.ec-stars-wrapper > a:hover ~ a {
				color: <?php echo get_option('ec_stars_rating_default_color') ?>;
			}
			.ec-stars-wrapper a:active {
				color: <?php echo get_option('ec_stars_rating_active_color') ?>;
			}
			.ec-stars-overlay {
				position: absolute;
				height: 100%;
				right: 0;
				top: 0;

				background-color: transparent;
				background-color: rgba(255,255,255,.5);

				/* OldIE support */
				zoom: 1;
				-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#7FFFFFFF,endColorstr=#7FFFFFFF)";
    			filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#7FFFFFFF,endColorstr=#7FFFFFFF);
			}
			.ec-stars-wrapper:hover .ec-stars-overlay {
				display: none;
			}
		</style><?php
	}


	/**
	 * Private functions for options
	 */
	public function _set_options() {
		$this->create_table();
		add_option('ec_stars_rating_size', '32');
		add_option('ec_stars_rating_show_votes', true);
		add_option('ec_stars_rating_use_microformats', false);
		add_option('ec_stars_rating_use_jquery', true);
		add_option('ec_stars_rating_default_color', '#888888');
		add_option('ec_stars_rating_hover_color', '#2782e4');
		add_option('ec_stars_rating_active_color', '#1869c0');
	}

	/**
	 * Delete the options from the database
	 */
	public function _unset_options() {
		// global $wpdb;
		delete_option('ec_stars_rating_size');
		delete_option('ec_stars_rating_show_votes');
		delete_option('ec_stars_rating_use_microformats');
		delete_option('ec_stars_rating_use_jquery');
		delete_option('ec_stars_rating_default_color');
		delete_option('ec_stars_rating_hover_color');
		delete_option('ec_stars_rating_active_color');
		// $wpdb->query($wpdb->prepare("DROP TABLE %s", $this->getTableName()));
	}

	/** 
	 * Register the settings page for the admin
	 */
	public function _register_settings() {
		register_setting('ec_stars_rating', 'ec_stars_rating_size', 'intval');
		add_settings_section(
			'ec_stars_rating_size',
			__('Tamaño de las estrellas (px) <em>por defecto 32, escoge el que se adapte a tus necesidades</em>'),
			array($this, '_number_input'),
			__FILE__
		);

		register_setting('ec_stars_rating', 'ec_stars_rating_default_color', array($this, '_validate_color'));
		add_settings_section(
			'ec_stars_rating_default_color',
			__('Color de las estrellas sin activar'),
			array($this, '_color_input'),
			__FILE__
		);

		register_setting('ec_stars_rating', 'ec_stars_rating_hover_color', array($this, '_validate_color'));
		add_settings_section(
			'ec_stars_rating_hover_color',
			__('Color de las estrellas al pasar el ratón por encima, o de las estrellas votadas'),
			array($this, '_color_input'),
			__FILE__
		);

		register_setting('ec_stars_rating', 'ec_stars_rating_active_color', array($this, '_validate_color'));
		add_settings_section(
			'ec_stars_rating_active_color',
			__('Color de las estrellas al hacer click'),
			array($this, '_color_input'),
			__FILE__
		);

		register_setting('ec_stars_rating', 'ec_stars_rating_show_votes');
		add_settings_section(
			'ec_stars_rating_show_votes',
			__('¿Mostrar los votos?'),
			array($this, '_bool_input'),
			__FILE__
		);

		register_setting('ec_stars_rating', 'ec_stars_rating_use_microformats');
		add_settings_section(
			'ec_stars_rating_use_microformats',
			__('¿Usar microformats? (en caso contrario se usará microdata) (<small>Microdata es el recomendado, pero con microformats se podrán ver las estrellas en google</small>)'),
			array($this, '_bool_input'),
			__FILE__
		);

		register_setting('ec_stars_rating', 'ec_stars_rating_use_jquery');
		add_settings_section(
			'ec_stars_rating_use_jquery',
			__('¿Usar jQuery? (La inmensa mayoría de las webs con wp lo usan, pero puedes elegir no hacerlo)<br/><small><em>Nota: no hay soporte para IE7, pero es asumible</em></small>'),
			array($this, '_bool_input'),
			__FILE__
		);
	}

	/**
	 * Create the table where the votes are going to be stored
	 */
	public function create_table() {
		global $wpdb;
		$table = $this->getTableName();
		$sql = "CREATE TABLE IF NOT EXISTS $table (
				`voter_ip` VARCHAR(15) NOT NULL,
				`post_id` BIGINT(20) UNSIGNED NOT NULL,
				KEY `post_id`(`post_id`),
				KEY `voter_ip`(`voter_ip`));";
		
		$wpdb->query($sql);
	}

	/**
	 * Validate a color input
	 * @param string $color the color input
	 */
	public function _validate_color($color) {
		if( preg_match("/^(#([A-fa-f0-9]{3}|[A-fa-f0-9]{6}))|(rgb\(\d{1,3},\s?\d{1,3},\s?\d{1,3}\))|(rgba\(\d{1,3},\s?\d{1,3},\s?\d{1,3},\s?\d{1,3}\))$/", $color) ) {
			return $color;
		}
		add_settings_error(
			'ec_stars_rating',
			'ec_stars_rating_error',
			__('Introduce un color correcto'),
			'error'
		);
	}

	/**
	 * Add a page in the admin menu
	 */
	public function _add_menu_page() {
		add_options_page( __('EC Stars Rating Options'), 'EC Stars Rating', 'administrator', __FILE__, array($this, '_options_page'));
	}

	/**
	 * Get a vote from the DB
	 * @param int $post_id
	 * @param string $voter_ip the voter ip
	 * @see WPDB::get_row
	 */
	public function getVote($post_id, $voter_ip) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare("SELECT `post_id` FROM $table WHERE `voter_ip` = %s AND `post_id` = %d LIMIT 0, 1", $voter_ip, $post_id));
	}

	/**
	 * Register the vote in the db
	 */
	public function _handle_vote() {
		global $wpdb;
		$table = $this->getTableName();

		if( ! defined('YEAR_IN_SECONDS') ) {
			define('YEAR_IN_SECONDS', 365 * 24 * 60 * 60);
		}
		/* Get the POST request data */
		// The post id
		$post_id = intval(@$_POST['post_id']);
		// The rating value (1-5)
		$rating = intval(@$_POST['rating']);
		// The ip
		$IP = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];

		$cookie_name = $this->getCookieName($post_id);

		// If we have voted set the cookie and return
		if( isset($_COOKIE[$cookie_name]) || $this->getVote($post_id, $IP) !== null) {
			setcookie( $cookie_name, 'true', time() + YEAR_IN_SECONDS, '/');
			die(json_encode(array('status' => $this->STATUS_PREVIOUSLY_VOTED)));
		}


		// else get the current rating and increase it appropiately
		$current_rating = intval(get_post_meta($post_id, 'ec_stars_rating', true));
		$current_votes = intval(get_post_meta($post_id, 'ec_stars_rating_count', true));

		// if there is no rating or no votes, or the rating is invalid, return
		if( (empty($current_rating) && $current_rating !== 0) || (empty($current_votes) && $current_votes !== 0) || $rating > 5 || $rating < 1) {
			die(json_encode(array(
				'status' => $this->STATUS_REQUEST_ERROR,
				'current_votes' => $current_votes,
				'current_rating' => $current_rating
			)));
		}

		// Update with the new rating
		update_post_meta($post_id, 'ec_stars_rating', $current_rating + $rating);
		update_post_meta($post_id, 'ec_stars_rating_count', $current_votes + 1);

		// Insert the vote in the db and set the cookie for a year
		$wpdb->insert(
			$table,
			array(
				'voter_ip' => $IP,
				'post_id' => $post_id
			),
			array('%s', '%d')
		);
		setcookie( $cookie_name, 'true', time() + YEAR_IN_SECONDS, '/');

		// Return a success message
		die(json_encode(array(
			'status' => $this->STATUS_SUCCESS,
			'votes' => $current_votes + 1,
			'total' => $current_rating + $rating,
			'result' => ($current_rating + $rating) / ($current_votes + 1)
		)));
	}

	/**
	 * The options page
	 */
	public function _options_page() {
		?><div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php printf(__('Options: %s'), 'EC Stars Rating'); ?></h2>
			<form action="options.php" method="post">
				<?php 
					settings_fields( 'ec_stars_rating' );
					do_settings_sections( __FILE__ );
				?>
				<?php submit_button(); ?>
			</form>
		</div><?php
	}

	/**
	 * Get a number input for the options page
	 * @param array $args the arguments returned
	 * @see ECStarsRating::_register_settings()
	 */
	public function _number_input($args) {
		?>
		<input value="<?php echo get_option($args['id']) ?>" min="1" type="number" pattern="[0-9]+" id="<?php echo $args['id'] ?>" name="<?php echo $args['id'] ?>">
		<label for="<?php echo $args['id'] ?>"><?php echo $args['title'] ?></label>
		<?php
	}

	/**
	 * Get a color input for the options page
	 * @param array $args
	 */
	public function _color_input($args) {
		?>
		<input type="color" value="<?php echo get_option($args['id']) ?>" id="<?php echo $args['id'] ?>" name="<?php echo $args['id'] ?>">
		<label class="screen-reader-text" for="<?php echo $args['id'] ?>"><?php echo $args['title'] ?></label>
		<?php
	}
	/**
	 * Get a boolean input for the options page
	 * @param array $args
	 */
	public function _bool_input($args) {
		$current_val = get_option($args['id']);
		?>
		<select name="<?php echo $args['id'] ?>" id="<?php echo $args['id'] ?>">
			<option value="1"><?php _e('Sí') ?></option>
			<option value="0"<?php if($current_val == 0) echo ' selected'?>><?php _e('No') ?></option>
		</select>
		<?php
	}
}

// Create the instance of our plugin
$ecStarRating = new ECStarsRating();

/**
 * Show the ratings in the loop
 */
function ec_stars_rating() {
	global $post;
	// Get the post rating, votes and options
	$rating = get_post_meta($post->ID, 'ec_stars_rating', true);
	$votes = get_post_meta($post->ID, 'ec_stars_rating_count', true);
	$microformats = get_option('ec_stars_rating_use_microformats');

	// if the rating is an empty string (we cannot use empty because empty('0') is true) create the post meta
	if( $rating === '' ) {
		$rating = 0;
		add_post_meta($post->ID, 'ec_stars_rating', 0);
	}
	// The same but for the votes count
	if( $votes === '' ) {
		$votes = 0;
		add_post_meta($post->ID, 'ec_stars_rating_count', 0);
	}

	// Cast them as int
	$votes = intval($votes);
	$rating = intval($rating);
	
	// Prevent division by 0
	if( $votes === 0 ) {
		$result = 0;
	} else {
		$result = $rating / $votes;
	}
// Show the data!
?>
<div class="ec-stars-outer<?php if($microformats) { echo ' hreview-aggregate';} ?>"<?php if( ! $microformats ) { echo ' itemscope itemtype="http://schema.org/AggregateRating"'; }?>>
	<div class="ec-stars-wrapper" data-post-id="<?php echo $post->ID ?>">
		<div class="ec-stars-overlay" style="width: <?php echo (100 - $result * 100 / 5) ?>%"></div>
		<a href="#" data-value="1" title="Votar con 1 estrellas">&#9733;</a>
		<a href="#" data-value="2" title="Votar con 2 estrellas">&#9733;</a>
		<a href="#" data-value="3" title="Votar con 3 estrellas">&#9733;</a>
		<a href="#" data-value="4" title="Votar con 4 estrellas">&#9733;</a>
		<a href="#" data-value="5" title="Votar con 5 estrellas">&#9733;</a>
	</div>
	<?php if(get_option('ec_stars_rating_show_votes')): // If we want to show the votes?>
		<div class="ec-stars-value">
			<?php if($microformats): // If we want to use microformats we need to put a link to the post?>
			<span class="item">
				<a href="<?php the_permalink() ?>" class="fn url"><?php the_title() ?></a>,
			</span>
			<?php endif; ?>
			<span <?php echo 'class="ec-stars-rating-value'; if($microformats) {echo ' rating"';} else { echo '" itemprop="ratingValue"'; }?>><?php
				// Show just two decimals
				echo is_int($result) ? $result : number_format($result, 2);
			?></span> / <span>5</span> (<span<?php echo ' class="ec-stars-rating-count'; if ($microformats) echo ' votes"'; else echo '" itemprop="ratingCount"'; ?>><?php echo $votes ?></span> <?php echo __('votos') ?>)
		</div>
	<?php elseif( ! $microformats ): ?>
		<meta itemprop="bestRating" content="5">
		<meta itemprop="ratingValue" content="<?php echo $result ?>">
		<meta itemprop="ratingCount" content="<?php echo $votes ?>">
	<?php endif; ?>
</div>
<noscript>Necesitas tener habilitado javascript para poder votar</noscript>
<?php
}
