<?php
	/*
	Plugin Name: Sermo.nz
	Plugin URI: http://sermo.nz
	description: Sermo.nz Library Plugin
	Version: 1.0
	Author: Andrew Drake
	Author URI: http://andrew.drake.nz
	License: GPL3
	*/

add_action( 'admin_init', 'sermonz_settings_init' );

$option = get_option('sermonz_hostname');

function sermonz_settings_init() 
{
	add_option('sermonz', 'sermonz_options');
 
	// register a new section in the "wporg" page
	add_settings_section(
	'sermonz_section_settings',
	__( 'Sermo.nz sermon library plugin', 'sermonz' ),
	'sermonz_section_settings_cb',
	'sermonz'
	);
} 

add_action( 'admin_init', 'sermonz_settings_init' );

function sermonz_section_settings_cb( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Set your Sermo.nz API settings below. For more information, see ', 'sermonz' ); ?><a href="https://github.com/below43/sermonz_wordpress">https://github.com/below43/sermonz_wordpress</a></p>
	<?php
}


/**
 * top level menu
 */
function sermonz_options_page() 
{
	// add top level menu page
	add_menu_page(
	'Sermo.nz options',
	'Sermo.nz',
	'manage_options',
	'sermonz',
	'sermonz_options_page_html'
	);
}
add_action( 'admin_menu', 'sermonz_options_page' );


/**
 * Settings link in plugins area
 */
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'sermonz_add_settings_link' );

function sermonz_add_settings_link($links) {
	$link = '<a href="' .
		admin_url( 'admin.php?page=sermonz' ) .
		'">' . __('Settings') . '</a>';
	array_unshift($links, $link);
	return $links;
}

/**
 * top level menu:
 * callback functions
 */
function sermonz_options_page_html() 
{
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) 
	{
		return;
	}
	
	// add error/update messages
	
	// check if the user have submitted the settings
	// wordpress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) 
	{
	// add settings saved message with the class of "updated"
	add_settings_error( 'sermonz_messages', 'wporg_message', __( 'Settings Saved', 'sermonz' ), 'updated' );
	}
	
	// show error/update messages
	settings_errors( 'sermonz_messages' );
	?>
	<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
	<?php
	// output security fields for the registered setting "wporg"
	settings_fields( 'sermonz' );
	// output setting sections and their fields
	// (sections are registered for "wporg", each field is registered to a specific section)
	do_settings_sections( 'sermonz' );
	// output save settings button
	submit_button( 'Save Settings' );
	?>
	</form>
	</div>
	<?php
   
}