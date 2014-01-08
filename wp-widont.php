<?php
/*
Plugin Name: Widon’t Part Deux
Plugin URI: https://github.com/morganestes/wp-widont
Description: Building on <a href="http://www.shauninman.com/archive/2008/08/25/widont_2_1_1" target="_blank">Shaun Inman’s plugin</a>, Widon’t Part Deux eliminates <a href="http://en.wikipedia.org/wiki/Widow_(typesetting)" target="_blank">widows</a> in the titles and content your posts and pages.
Version: 1.3.1
Author: Morgan Estes
Author URI: http://www.morganestes.me/
License: GPLv3
*/

class WidontPartDeux {

	/**#@+
	 * @var string
	 */
	protected $version = '1.3.1';
	/**
	 * The normalized path to the plugin file. Set in the constructor.
	 */
	protected $plugin = '';
	protected $plugin_name = 'Widon&#8217;t Part Deux';
	protected $plugin_shortname = 'widont_deux';
	/**#@-*/

	/**
	 * Kicks off the settings for the rest of the plugin.
	 */
	public function __construct() {
		$this->plugin = plugin_basename( __FILE__ );

		if ( false == get_option( $this->plugin_shortname ) ) {
			add_option( $this->plugin_shortname );
		}

		$this->plugin_init();
	}

	/**
	 * Wrapper function to set up plugin settings.
	 */
	public function plugin_init() {
		// Admin settings
		add_action( 'admin_init', array( $this, 'plugin_register_settings' ) );
		add_action( 'admin_menu', array( $this, 'plugin_preferences_menu' ) );
		add_filter( "plugin_action_links_{$this->plugin}", array( $this, 'add_settings_link' ) );

		// Content filters
		add_filter( 'the_title', array( $this, 'widont' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ) );

		// Localization support
		load_plugin_textdomain( 'widont', false, basename( dirname( __FILE__ ) ) . '/lang/' );

		$this->version_check();
		$this->add_starting_tags( 'p' );
	}

	/**
	 * Cheater method to get the options from the database.
	 *
	 * @uses get_option()
	 * @return array|string An array of settings unserialized; empty string if not found.
	 */
	public function get_options(){
		return get_option( $this->plugin_shortname, '' );
	}

	/**
	 * Cheater method to update the options in the database.
	 *
	 * @uses update_option()
	 * @param  array $options The full options field data to set.
	 *
	 * @return bool
	 */
	public function update_options( $options ) {
		return update_option( $this->plugin_shortname, $options );
	}

	/**
	 * Add settings link on plugin page.
	 *
	 * @param array $links Automatically provided by WordPress filter.
	 *
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( "options-general.php?page={$this->plugin}" ) . '">Settings</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Check the version of installed plugin and update the options field.
	 * @return bool If the update was successful.
	 */
	protected function version_check() {
		// @todo Implement logic if we need to do anything if they're different.
		// For now, just store the current version.
		$options = $this->get_options();
		$options['version'] = $this->version;
		$updated = $this->update_options( $options );

		return $updated;
	}

	/**
	 * Parse the string and add a non-breaking space.
	 *
	 * @param $str string
	 * @return string
	 */
	public function widont( $str = '' ) {
		return preg_replace( '/([^\s])\s+([^\s]+)\s*$/', '$1&nbsp;$2', $str );
	}

	public function filter_content( $content = '' ) {
		$options = $this->get_options();
		$tags = $options['tags'];

		if ( ! empty( $tags ) && preg_match_all( '#<(' . $tags . ')>(.+)</\1>#', $content, $matches ) ) {
			foreach ( $matches[0] as $match ) {
				if ( $this->safe_to_filter( $match ) ) {
					$content = str_replace( $match, $this->widont( $match ), $content );
				}
			}
		}
		return $content;
	}

	/**
	 * Check if the string is safe to filter.

	 * If an oEmbed (or any iframe, really) is used an iframe tag is generated.
	 * We need to take care to not look inside this, or any other specified tag, so we don't
	 * accidentally add in a break inside the tag itself (all spaces should be added to the text
	 * inside an element, not in the tag.
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	public function safe_to_filter( $string ) {
		$unsafe_tags = array( 'iframe', 'script', 'style', 'embed', 'object', 'video', 'audio' );
		$is_safe   = true;

		foreach ( $unsafe_tags as $tag ) {
			if ( preg_match( "#<$tag#", $string ) ) {
				$is_safe = false;
			}
		}

		return $is_safe;
	}

	public function plugin_preferences_menu() {
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'options-general.php', __( $this->plugin_name ), __( $this->plugin_name ), 'manage_options', $this->plugin , array( $this, 'options_page' ) );
		}
	}

	function plugin_register_settings() {
		add_settings_section(
			"{$this->plugin_shortname}_general_options",
			__( 'Post/Page Content Tags', 'widont' ),
			array( $this, 'widont_settings_header' ),
			$this->plugin
		);
		add_settings_field(
			'tags',
			__( 'Tags to filter in the post content*: ', 'widont' ),
			array( $this, 'html_input_tags' ),
			$this->plugin,
			"{$this->plugin_shortname}_general_options",
			array(
				'label_for' => 'tags',
			)
		);
		register_setting(
			$this->plugin_shortname,
			$this->plugin_shortname,
			array( $this, 'widont_validate_tags_input' )
		);
	}

	/**
	 * Start the plugin with some default tags for the content.
	 *
	 * @param string $tags A space-separated string of tags to start with.
	 */
	function add_starting_tags( $tags = '' ) {
		$options = $this->get_options();

		if ( !isset( $options['tags'] ) || null == $options['tags'] ) {
			$options['tags'] = $tags;
			$filtered_tags = $this->widont_validate_tags_input( $options );
			$this->update_options( $filtered_tags );
		}
	}

	/**
	 * Displays the HTML for the section inside the form.
	 *
	 * @return string
	 */
	function widont_settings_header() {
		$text = <<<HTML
		<p>With Widon’t your post titles are spared unwanted widows. Extend that courtesy to other tags in your posts* by entering tag names below.</p>
		<p>No need to include angle brackets. Separate multiple tag names with a space or comma (e.g. <code>h3 h4 h5</code> or <code>p, li, span</code>).</p>
HTML;
		_e( $text, 'widont' );
	}

	/**
	 * Displays the input field in the options form.
	 * @return string
	 */
	function html_input_tags() {
		$options = $this->get_options();

		$extended_tags = '';
		if ( isset( $options['tags'] ) ) {
			$extended_tags = str_replace( '|', ' ', $options['tags'] );
		}

		$tags = esc_attr( $extended_tags );
		$name = esc_attr( "$this->plugin_shortname[tags]" );
		$description = __( '*Elements not allowed in posts will be automatically stripped.', 'widont' );

		$input = <<<HTML
		<input type="text" name="$name" id="extended_tags" class="regular-text code" value="$tags" />
		<span class="description">$description</span>
HTML;

		_e( $input );
	}

	/**
	 * Validate and sanitize the input text field.
	 *
	 * @param  array $input An array of all options for the plugin.
	 *
	 * @return array The sanitized options to add to the database.
	 */
	function widont_validate_tags_input( $input ) {
		if ( empty( $input ) ) return;

		/**#@+
		 * @var array
		 */
		$elements = array();
		$elements2 = array();
		$newinput = array();

		/**#@+
		 * @var string
		 */
		$newinput['tags'] = '';
		/**
		 * The tags that are allowed inside posts as filtered by WP.
		 */
		$filtered_elements = '';

		// Strip out anything extra that may cause problems.
		$newinput['tags'] = preg_replace( '/[,;<>|\/\s]+/', ' ', trim( $input['tags'] ) );

		// Make a couple of arrays to use to filter through.
		$elements = explode( ' ', $newinput['tags'] );
		$elements2 = array();

		/** Loop through the tags and make 'em look like actual tags so wp_kses_post will handle them properly. */
		foreach ( $elements as $element ) {
			$element = preg_replace( '/[<>]/', '', $element );
			array_push( $elements2, "<$element>" );
		}

		$elements2 = array_unique( $elements2 );

		$filtered_elements = wp_kses_post( implode( $elements2 ) );

		$newinput['tags'] = preg_replace( '/[\s<>]+/', '|', $filtered_elements );
		$newinput['tags'] = trim( $newinput['tags'], '|' );

		return $newinput;
	}

	/**
	 * The Settings page displayed.
	 */
	function options_page() {
		echo '<div class="wrap">';

		/* screen_icon was deprecated in 3.8 */
		if ( version_compare( $GLOBALS['wp_version'], '3.8', '<' ) ) {
			screen_icon();
		}

		_e( sprintf( '<h2>%s Options</h2>', $this->plugin_name ), 'widont' );
		echo '<form method="post" action="options.php">';

		settings_fields( $this->plugin_shortname );
		do_settings_sections( $this->plugin );
		submit_button( __( 'Update Preferences', 'widont' ) );

		echo '</form></div>';
	}

}

$widont = new WidontPartDeux();
