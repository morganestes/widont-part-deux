<?php
/*
Plugin Name: Widon't Part Deux
Plugin URI: https://github.com/morganestes/wp-widont
Description: Eliminates widows in your post titles (and <a href="?page=si-widont.php/si-widont.php">posts</a>!) by inserting a non-breaking space between the last two words of a title. What is a widow? In typesetting, a widow is a single word on a line by itself at the end of a paragraph and is considered bad style.
Version: 1.0.0
Author: Morgan Estes
Author URI: http://www.morganestes.me/
License: GPLv3
*/

namespace MorganEstes;

class WidontPartDeux {

protected $version = '1.0.0';
protected $plugin_name = "Widon't Part Deux";
protected $plugin_shortname = 'widont_deux';
protected $text_domain = 'widont';


	public function __construct() {
		// Admin settings
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'plugin_preferences_menu' ) );

		// Content filters
		add_filter( 'the_title', array( $this, 'widont' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ) );
}

public function init() {
	add_option( $this->plugin_shortname );

	$this->version_check();
	$this->plugin_register_settings();
}

protected function get_options($option) {
	$options = get_options( $this->plugin_shortname );

	return $options[$option];
}

protected function set_options($option, $value) {
	$options = get_option( $this->plugin_shortname );

	$options[$option] = $value;

	update_option( $this->plugin_shortname, $options );
}

protected function version_check() {
	$options = get_option( $this->plugin_shortname );
	// @todo Implement logic if we need to do anything if they're different.
	// For now, just store the current version.
	$options['version'] = $this->version;
	update_option( $this->plugin_shortname, $options );
}

public function widont( $str = '' ) {
	return preg_replace( '|([^\s])\s+([^\s]+)\s*$|', '$1&nbsp;$2', $str );
}

public function filter_content( $str = '' ) {
	$tags = get_option( 'widont_tags' );

	if ( ! empty( $tags ) && preg_match_all( '#<(' . $tags . ')>(.+)</\1>#', $str, $m ) ) {
		foreach ( $m[0] as $match ) {
			$str = str_replace( $match, $this->widont( $match ), $str );
		}
	}
	return $str;
}

public function plugin_preferences_menu() {
	if ( function_exists( 'add_submenu_page' ) ) {
		add_submenu_page( 'options-general.php', __( $this->plugin_name ), __( $this->plugin_name ), 'manage_options', __FILE__ , array( $this, 'save_options' ) );
	}
}

function plugin_register_settings() {
	add_settings_section( 'widont_main', __("Extended Post/Page Content Elements"), array( $this, 'widont_settings_header' ), __FILE__ );
	add_settings_field( 'widont_tags', __("HTML element names to filter*: "), array( $this, 'widont_extended_tags_input' ), 'widont_options', $section = 'widont_main', $args = array( 'label_for' => 'widont_tags' ) );
	register_setting( 'widont_options', $this->plugin_shortname, array( $this, 'widont_validate_tags_input' ) );
}

function widont_settings_header() {
	echo <<<HTML
		<p>With Widon&rsquo;t your post titles are spared unwanted widows. Extend that courtesy to other tags in your posts* by entering tag names below. </p>
		<p>No need to include angle brackets. Separate multiple tag names with a space or comma (e.g. <code>h3 h4 h5</code> or <code>p, li, span</code>).</p>
HTML;
}

 function widont_extended_tags_input() {
 	$options = get_option( $this->plugin_shortname );

 	if(!isset($options['extended_tags']))
 		$extended_tags = '';
 	else
 		$extended_tags = $options['extended_tags'];

	$tags = str_replace( '|', ' ', $extended_tags );
	echo "<input type='text' name='{$this->plugin_shortname}[extended_tags]' id='widont_tags' class='regular-text code' value='{$tags}' />";
	echo "<span class='description'>*Elements not allowed in posts will be automatically stripped.</span>";
}

 function widont_validate_tags_input( $input ) {
 	if( empty( $input ) ) return;

	$newinput['tags'] = trim( $input['extended_tags'] );
	$allowed_html = wp_kses_allowed_html( 'post' );
	$elements = explode( ' ', $newinput['tags'] );
	$elements2 = array();
	foreach( $elements as $element ) {
		// make 'em look like actual tags
		$element = preg_replace( '/[<>]/', '', $element );
		$tag = "<$element>";
		array_push( $elements2, $tag );
	}
	$filtered_elements = wp_kses_post( implode( $elements2 ) );
	$newinput['tags'] = preg_replace( '#[\s,;<>]+#', '|', $filtered_elements );

	return trim( $newinput['tags'], '|' );
}

 function save_options() {
	?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php echo __("{$this->plugin_name} Options"); ?></h2>
		<form method="post" action="options.php">
		<?php
		settings_fields( 'widont_options' );
		do_settings_sections( __FILE__ );
		do_settings_fields( 'widont_options', 'widont_main' );
		submit_button( 'Update Preferences', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null );
		?>
		</form>
	</div>
<?php } //function

} //class

$widont = new WidontPartDeux();
