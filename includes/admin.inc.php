<?php

add_action( 'admin_init', 'sermonz_settings_init' );

function sermonz_settings_init() 
{
	add_option('sermonz', 'sermonz_options');
 
	add_settings_section(
		'sermonz_section_settings',
		__( 'Sermo.nz sermon library plugin', 'sermonz' ),
		'sermonz_section_settings',
		'sermonz-settings-group'
	);

	register_setting( 'sermonz-settings-group', 'sermonz_api_url' );
	register_setting( 'sermonz-settings-group', 'sermonz_page' );
	register_setting( 'sermonz-settings-group', 'sermonz_css' );
} 

add_action( 'admin_init', 'sermonz_settings_init' );

function sermonz_section_settings( $args ) {
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
	
	settings_fields( 'sermonz-settings-group' );
	
	do_settings_sections( 'sermonz-settings-group' );

	?>
    <table class="form-table">
        <tr valign="top">
			<th scope="row">Sermo.nz API URL</th>
			<td>
				<input type="text" name="sermonz_api_url" style="width: 400px" value="<?php echo esc_attr( get_option('sermonz_api_url') ); ?>" placeholder="https://yoursite.sermo.nz/api/" /><br/>
				<br/><i>Format: https://yoursite.sermo.nz/api/</i>.
			</td>
        </tr>

		<tr valign="top">
			<th scope="row">Sermon library page</th>
			<td>
				<p>Specify the page that will be the root page for the sermon library:</p>
				<select name='sermonz_page'>
					<option value='0'><?php _e('Select a Page', 'textdomain'); ?></option>
					<?php 
					$pages = get_pages(); 
					$sermonz_page = get_option('sermonz_page');
					?>
					<?php foreach( $pages as $page ) { ?>
						<option value='<?php echo $page->ID; ?>' <?php selected( $sermonz_page, $page->ID ); ?> ><?php echo $page->post_title; ?></option>
					<?php }; ?>
				</select><br/>
				<p>This is the page that all the sermons and series will be linked from eg. if your page is called /listen, your sermons might be be /listen/234/genesis-1/joe-bloggs/the-meaning-of-life</p>
			</td>
		</tr>
        <tr valign="top">
			<th scope="row">Custom CSS (optional)</th>
			<td>
				<textarea style="width: 400px" name="sermonz_css" ><?php echo esc_html( get_option('sermonz_css') ); ?></textarea>
			</td>
        </tr>
    </table>
	<?php
		submit_button( 'Save Settings' );
	?>
	</form>
	</div>
	<?php
}

function sermonz_shortcode($atts = [], $content = null, $tag = '')
{

    $o = '<div class="wporg-box">Coming soon...</div>';
 
    // return output
    return $o;
}
 
function sermonz_shortcodes_init()
{
    add_shortcode('sermonz', 'sermonz_shortcode');
}
 
add_action('init', 'sermonz_shortcodes_init');

//todo - add rewrite rule for /listen/series, listen/books, listen/speakers, listen/series/id/series-name, listen/sermon/id/passage/preacher/sermon-title


