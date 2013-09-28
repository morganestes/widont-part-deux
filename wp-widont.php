<?php
/*
Plugin Name: Widon't Part Deux
Plugin URI: https://github.com/morganestes/wp-widont
Description: Eliminates widows in your post titles (and <a href="?page=wp-widont.php/wp-widont.php">posts</a>!) by inserting a non-breaking space between the last two words of a title. What is a widow? In typesetting, a widow is a single word on a line by itself at the end of a paragraph and is considered bad style.
Version: 1.0.1
Author: Morgan Estes
Author URI: http://www.morganestes.me/
License: GPLv3
*/

namespace MorganEstes;

class WidontPartDeux {

	protected $version = '1.0.1';
	protected $plugin = '';
	protected $plugin_name = "Widon't Part Deux";
	protected $plugin_shortname = 'widont_deux';
	protected $text_domain = 'widont';

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
			add_submenu_page( 'options-general.php', __( $this->plugin_name ), __( $this->plugin_name ), 'manage_options', __FILE__ , array( $this, 'options_page' ) );
		}
	}

	function plugin_register_settings() {
		add_settings_section(
			"{$this->plugin_shortname}_general_options",
			__( 'Extended Post/Page Content Elements', $this->text_domain ),
			array( $this, 'widont_settings_header' ),
			$this->plugin
		);
		add_settings_field(
			'extended_tags',
			__( 'HTML element names to filter*: ', $this->text_domain ),
			array( $this, 'widont_extended_tags_input' ),
			$this->plugin,
			"{$this->plugin_shortname}_general_options",
			array(
				'label_for' => "{$this->plugin_shortname}_extended_tags",
			)
		);
		register_setting(
			$this->plugin_shortname,
			$this->plugin_shortname,
			array( $this, 'widont_validate_tags_input' )
		);
	}

	function widont_settings_header() {
		$text = <<<HTML
		<p>With Widon&rsquo;t your post titles are spared unwanted widows. Extend that courtesy to other tags in your posts* by entering tag names below.</p>
		<p>No need to include angle brackets. Separate multiple tag names with a space or comma (e.g. <code>h3 h4 h5</code> or <code>p, li, span</code>).</p>
HTML;
		_e( $text, $this->text_domain );
	}

	function widont_extended_tags_input() {
		$options = get_option( $this->plugin_shortname );

var_dump($options);
		if ( isset( $options['extended_tags'] ) ) {
			$extended_tags = $options['extended_tags'];
		} else {
			$extended_tags = '';
			$options['extended_tags'] = '';
			update_option( $this->plugin_shortname, $options );
		}

		$tags = str_replace( '|', ' ', $extended_tags );

		var_dump($options);

		echo '<input type="text" name="'. esc_attr( "$this->plugin_shortname[extended_tags]" ) . '" id="' . esc_attr( "{$this->plugin_shortname}_extended_tags" ) . '" class="regular-text code" value="' . esc_attr( $tags ) . '" />';
		echo '<span class="description">' . __( '*Elements not allowed in posts will be automatically stripped.', $this->text_domain ) . '</span>';
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
		<h2><?php  _e( "{$this->plugin_name} Options", $this->text_domain ); ?></h2>
		<form method="post" action="options.php">
		<?php
		settings_fields( $this->plugin_shortname );
		do_settings_sections( $this->plugin );
		do_settings_fields( 'widont_options', 'widont_main' );
		submit_button( __( 'Update Preferences', $this->text_domain ), $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null );
?>
		</form>
	</div>
<?php }

}

$widont = new WidontPartDeux();
