<?php
/*
Plugin Name: Widon’t Part Deux
Plugin URI: https://github.com/morganestes/wp-widont
Description: Eliminates widows in your post titles (and <a href="?page=wp-widont.php/wp-widont.php">posts</a>!) by inserting a non-breaking space between the last two words of a title. What is a widow? In typesetting, a widow is a single word on a line by itself at the end of a paragraph and is considered bad style.
Version: 1.0.1
Author: Morgan Estes
Author URI: http://www.morganestes.me/
License: GPLv3
*/

namespace MorganEstes;

class WidontPartDeux {

	/**#@+
	 * @var string
	 */
	protected $version = '1.0.1';
	/**
	 * The normalized path to the plugin file. Set in the constructor.
	 */
	protected $plugin = '';
	protected $plugin_name = 'Widon’t Part Deux';
	protected $plugin_shortname = 'widont_deux';
	protected $plugin_textdomain = 'widont';
	/**#@-*/

/**
 * Kicks off the settings for the rest of the plugin.
 */
	public function __construct() {
		$this->plugin = plugin_basename( __FILE__ );

		if ( false == get_option( $this->plugin_shortname ) ) {
			add_option( $this->plugin_shortname );
		}

		// Admin settings
		$this->init();
		add_action( 'admin_init', array( $this, 'plugin_register_settings' ) );
		add_action( 'admin_menu', array( $this, 'plugin_preferences_menu' ) );
		add_filter( "plugin_action_links_{$this->plugin}", array( $this, 'add_settings_link' ) );

		// Content filters
		add_filter( 'the_title', array( $this, 'widont' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ) );
	}

	public function init() {
		$this->version_check();
	}

	// Add settings link on plugin page
	function add_settings_link( $links ) {
		$settings_link = "<a href='options-general.php?page={$this->plugin}'>Settings</a>";
		array_unshift( $links, $settings_link );
		return $links;
	}

	protected function version_check() {
		// @todo Implement logic if we need to do anything if they're different.
		// For now, just store the current version.
		$options = get_option( $this->plugin_shortname );
		$options['version'] = $this->version;
		update_option( $this->plugin_shortname, $options );
	}

	/**
	 * Parse the string and add a non-breaking space.
	 *
	 * @param $str string
	 * @return string
	 */
	public function widont( $str = '' ) {
		return preg_replace( '|([^\s])\s+([^\s]+)\s*$|', '$1&nbsp;$2', $str );
	}

	public function filter_content( $content = '' ) {
		$tags = $this->options['extended_tags'];

		if ( ! empty( $tags ) && preg_match_all( '#<(' . $tags . ')>(.+)</\1>#', $content, $matches ) ) {
			foreach ( $matches[0] as $match ) {
				$content = str_replace( $match, $this->widont( $match ), $content );
			}
		}
		return $content;
	}

	public function plugin_preferences_menu() {
		if ( function_exists( 'add_submenu_page' ) ) {
			add_submenu_page( 'options-general.php', __( $this->plugin_name ), __( $this->plugin_name ), 'manage_options', $this->plugin , array( $this, 'options_page' ) );
		}
	}

	function plugin_register_settings() {
		add_settings_section(
			"{$this->plugin_shortname}_general_options",
			__( 'Extended Post/Page Content Elements', $this->plugin_textdomain ),
			array( $this, 'widont_settings_header' ),
			$this->plugin
		);
		add_settings_field(
			'extended_tags',
			__( 'HTML element names to filter*: ', $this->plugin_textdomain ),
			array( $this, 'widont_extended_tags_input' ),
			$this->plugin,
			"{$this->plugin_shortname}_general_options",
			array(
				'label_for' => 'extended_tags',
			)
		);
		register_setting(
			$this->plugin_shortname,
			$this->plugin_shortname,
			array( $this, 'widont_validate_tags_input' )
		);
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
		_e( $text, $this->plugin_textdomain );
	}

	function widont_extended_tags_input() {
		$options = get_option( $this->plugin_shortname );

		$extended_tags = '';
		if ( isset( $options['extended_tags'] ) ) {
			$extended_tags = str_replace( '|', ' ', $options['extended_tags'] );
		}

		$tags = esc_attr( $extended_tags );
		$name = esc_attr( "$this->plugin_shortname[extended_tags]" );
		$description = __( '*Elements not allowed in posts will be automatically stripped.', $this->plugin_textdomain );

		$input = <<<HTML
		<input type="text" name="$name" id="extended_tags" class="regular-text code" value="$tags" />
		<span class="description">$description</span>
HTML;

		_e( $input );
	}

	function widont_validate_tags_input( $input ) {
		if ( empty( $input ) ) return;

		$newinput['tags'] = trim( $input['extended_tags'] );
		$allowed_html = wp_kses_allowed_html( 'post' );
		$elements = explode( ' ', $newinput['tags'] );
		$elements2 = array();
		foreach ( $elements as $element ) {
			// make 'em look like actual tags
			$element = preg_replace( '/[<>]/', '', $element );
			$tag = "<$element>";
			array_push( $elements2, $tag );
		}
		$filtered_elements = wp_kses_post( implode( $elements2 ) );
		$newinput['tags'] = preg_replace( '#[\s,;<>]+#', '|', $filtered_elements );

		return trim( $newinput['tags'], '|' );
	}

	function options_page() {
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php  _e( "{$this->plugin_name} Options", $this->plugin_textdomain ); ?></h2>
		<form method="post" action="options.php">
		<?php
		settings_fields( $this->plugin_shortname );
		do_settings_sections( $this->plugin );
		submit_button( __( 'Update Preferences', $this->plugin_textdomain ) );
?>
		</form>
	</div>
<?php }

}

$widont = new WidontPartDeux();
