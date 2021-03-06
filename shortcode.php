<?php
/**
 * Shortcode Class
 *
 * @package     Plugin Core
 * @subpackage  Shortcode
 * @copyright   Copyright (c) 2014, Dev7studios
 * @license     http://opensource.org/licenses/GPL-3.0 GNU Public License
 * @since       2.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode Core Class
 *
 * @since 1.0
 */
class Dev7_Core_Shortcode {

	/**
	 * Plugin labels
	 *
	 * @var object
	 * @access private
	 * @since  2.2
	 */
	private $labels;

	/**
	 * Shortcode core scripts
	 *
	 * @var array
	 * @access private
	 * @since  2.2
	 */
	private $core_scripts;

	/**
	 * Shortcode scripts
	 *
	 * @var array
	 * @access private
	 * @since  2.2
	 */
	private $scripts;

	/**
	 * Shortcode core styles
	 *
	 * @var array
	 * @access private
	 * @since  2.2
	 */
	private $core_styles;

	/**
	 * Shortcode styles
	 *
	 * @var array
	 * @access private
	 * @since  2.2
	 */
	private $styles;

	/**
	 * Instance of Dev7 Images Core Class
	 *
	 * @var object
	 * @access private
	 * @since  2.2
	 */
	private $core_images;

	/**
	 * Main construct for the Dev7 core Images class
	 *
	 * @since 2.2
	 *
	 * @param array $labels Specific plugin label data
	 */
	public function __construct( $labels ) {

		$this->labels      = $labels;
		$this->core_images = new Dev7_Core_Images( $this->labels );

		$this->core_scripts  = apply_filters( $this->labels->post_type . '_shortcode_core_scripts', array() );
		$this->core_styles  = apply_filters( $this->labels->post_type . '_shortcode_core_styles', array() );

		$this->scripts = apply_filters( $this->labels->post_type . '_shortcode_scripts', array() );
		$this->styles  = apply_filters( $this->labels->post_type . '_shortcode_styles', array() );

		add_shortcode( $this->labels->shortcode, array( $this, 'shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_core_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_core_styles' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
	}

	/**
	 * Shortcode output for the plugin
	 *
	 * @since  2.2
	 *
	 * @param array $atts
	 *
	 * @access public
	 * @return string $output
	 */
	public function shortcode( $atts ) {
		extract(
			shortcode_atts(
				array(
					'id'   		=> 0,
					'slug' 		=> '',
					'template'	=>	"0"
				), $atts
			)
		);

		if ( ! $id && ! $slug ) {
			return sprintf( __( 'Invalid %1$s', 'dev7core' ), $this->labels->singular );
		}

		if ( ! $id ) {
			$object = get_page_by_path( $slug, OBJECT, $this->labels->post_type );
			if ( $object ) {
				$id = $object->ID;
			} else {
				return sprintf( __( 'Invalid %1$s Slug', 'dev7core' ), $this->labels->singular );
			}
		}

		$output  = '';
		$options = get_post_meta( $id, $this->labels->post_meta_key, true );
		$type    = $options[$this->labels->source_name];

		$defaults    = $this->core_images->image_sources_defaults();
		$slider_type = ( ! array_key_exists( $type, $defaults ) ) ? 'external' : '';

		$images = $this->core_images->get_images( $id );

		if ( $template == "1" ) {
			$this->enqueue_core_scripts( true );
			$this->enqueue_core_styles( true );
		}
		$this->enqueue_scripts( $options );
		$this->enqueue_styles( $options );

		if ( $images ) {
			$output = apply_filters( $this->labels->post_type . '_shortcode_output', $id, $output, $options, $images, $slider_type );
		}

		return $output;
	}

	/**
	 * Enqueues core shortcode scripts in <head>
	 *
	 * @param bool $force
	 *
	 * @since  2.2
	 * @access public
	 */
	public function enqueue_core_scripts( $force = false ) {
		global $post;
		if ( ! $force && ! isset( $post->post_content ) ) return;

		if ( $force || dev7_has_shortcode_wrap( $post->post_content, $this->labels->shortcode ) ) {
			if ( $this->core_scripts && count( $this->core_scripts ) > 0 ) {
				$required = array( 'jquery' );
				// Enqueue jquery just in case the theme hasn't
				wp_enqueue_script( 'jquery' );
				foreach ( $this->core_scripts as $name => $url ) {
					wp_enqueue_script( $name, $url, $required, $this->labels->plugin_version );
					$required[] = $name;
				}
			}
		}
	}

	/**
	 * Enqueues core shortcode styles in <head>
	 *
	 * @param bool $force
	 *
	 * @since  2.2
	 * @access public
	 */
	public function enqueue_core_styles( $force = false ) {
		global $post;
		if ( ! $force && ! isset( $post->post_content ) ) return;

		if ( $force || dev7_has_shortcode_wrap( $post->post_content, $this->labels->shortcode ) ) {
			if ( $this->core_styles && count( $this->core_styles ) > 0 ) {
				foreach ( $this->core_styles as $name => $url ) {
					wp_enqueue_style( $name, $url, array(), $this->labels->plugin_version );
				}
			}
		}
	}

	/**
	 * Enqueue shortcode scripts that are dependant on plugin options in <footer>
	 *
	 * @since  2.2
	 *
	 * @param array $options
	 *
	 * @access private
	 */
	private function enqueue_scripts( $options ) {
		$scripts = apply_filters( $this->labels->post_type . '_shortcode_scripts_enqueue', $this->scripts, $options );
		if ( $scripts && count( $scripts ) > 0 ) {
			foreach ( $scripts as $name => $url ) {
				wp_enqueue_script( $name );
			}
		}
	}

	/**
	 * Enqueue shortcode styles
	 *
	 * @since  2.2
	 *
	 * @param array $options
	 *
	 * @access private
	 */
	private function enqueue_styles( $options ) {
		$styles = apply_filters( $this->labels->post_type . '_shortcode_styles_enqueue', $this->styles, $options );
		if ( $styles && count( $styles ) > 0 ) {
			foreach ( $styles as $name => $url ) {
				wp_enqueue_style( $name );
			}
		}
	}

	/**
	 * Register shortcode scripts
	 *
	 * @since  2.2
	 * @access public
	 */
	public function register_scripts() {
		if ( $this->scripts && count( $this->scripts ) > 0 ) {
			$required = array( 'jquery' );
			foreach ( $this->scripts as $name => $url ) {
				wp_register_script( $name, $url, $required, $this->labels->plugin_version );
				$required[] = $name;
			}
		}
	}

	/**
	 * Register shortcode styles
	 *
	 * @since  2.2
	 * @access public
	 */
	public function register_styles() {
		if ( $this->styles && count( $this->styles ) > 0 ) {
			foreach ( $this->styles as $name => $url ) {
				wp_register_style( $name, $url, array(), $this->labels->plugin_version );
			}
		}
	}
}