<?php
/*
Plugin Name: Filetype Icons
Plugin URI: http://wordpress.org/extend/plugins/filetype-icons/
Description: This plugin adds an Filetype extension icon before the link text
Author: Ralf Weber
Version: 1.1.1
Author URI: http://weber-nrw.de/
*/

// Set global variables
load_plugin_textdomain('FTIs', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');

// set globals
$FTIs = FTIs_getIcons();
$options = get_option('FTIs_options');


function FTIs( $content ) 
{
	
	$pattern = '/((<a .*)href=(")([^"]*)"([^>]*)>)(.*)(<\/a>)/U';
	$content = preg_replace_callback( $pattern , 'FTIs_worker', $content);
	
	// Returns the content.
	return $content;
}
if ( isset($options['the_content'])  ) add_filter( 'the_content', 'FTIs');
if ( isset($options['the_excerpt'])  ) add_filter( 'the_excerpt', 'FTIs');
if ( isset($options['comment_text']) ) add_filter( 'comment_text', 'FTIs');
if ( isset($options['widget_text'])  ) add_filter( 'widget_text', 'FTIs');

function FTIs_worker( $match ) 
{
	global $FTIs, $icon_Height;

	$options = get_option('FTIs_options');
	$icon_Height = $options['icon_Height'];

	$type = strtolower( end( explode ( '.' , str_replace( '"', '', $match[4] ) ) ) );
	// quick and dirty workaround for missing office 2013 icons
	$office_new = array( 'docx', 'xlsx' );
	$office_old = array( 'doc', 'xls' );
	if ( in_array($type, $office_new) ) $type = str_replace($office_new, $office_old, $type);

	$new_match = '';

	if ( in_array( $type, $FTIs ) ) {
		$new_match = $match[1].'<img src="'.plugins_url( 'icons/'.$icon_Height.'/file_extension_'.$type.'.png' , __FILE__ ).'" class="mime-type-icon '.$type.'">'.$match[6].'</a>';

	}

	// Returns the match.
	return (!empty($new_match))? $new_match : $match[0];
	
}

function FTIs_getIcons() 
{
	$options = get_option('FTIs_options');
	$icon_Height = $options['icon_Height'];
	
	$dir = plugin_dir_path( __FILE__ ).'icons/'.$icon_Height; 
	$fh = opendir($dir);
	$icons = array();
	while (true == ($file = readdir($fh)))
	{
		if ( strstr($file, 'file_extension_') ) {
			$icons[]=str_replace('file_extension_', '', basename( $file, '.png') );
		}
	}
	
	// Returns the FiletypeIcons
	return $icons;
}

function FTIs_getRandomIcon()
{
	global $FTIs;
	$rnd = intval(rand(0, count($FTIs)));
	
	return $FTIs[$rnd];
}

function FTIs_header_content() 
{
	$options = get_option('FTIs_options');
	echo '<style>.mime-type-icon {';
	if ( isset($options['css']) ) echo $options['css'];
	echo '} </style>';
}
add_action( 'wp_head', 'FTIs_header_content' );

// Set-up Action and Filter Hooks
register_activation_hook(__FILE__, 'FTIs_add_defaults');
register_uninstall_hook(__FILE__, 'FTIs_delete_plugin_options');
add_action('admin_init', 'FTIs_init' );
add_action('admin_menu', 'FTIs_add_options_page');
add_filter( 'plugin_action_links', 'FTIs_plugin_action_links', 10, 2 );


// Delete options table entries ONLY when plugin deactivated AND deleted
function FTIs_delete_plugin_options() {
	delete_option('FTIs_options');
}


// Define default option settings
function FTIs_add_defaults() 
{
	$tmp = get_option('FTIs_options');
    if((!empty($tmp['chk_default_options_db']))||(!is_array($tmp))) {
		delete_option('FTIs_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
		$arr = array(	"chk_default_options_db" => "",
						"the_content" 	=> "1",
						"the_excerpt" 	=> "0",
						"comment_text" 	=> "0",
						"widget_text" 	=> "0",
						"icon_Height" 	=> "16",
						"css"			=> "margin: 0 0.3em 0 0;
vertical-align: text-bottom;
"
		);
		update_option('FTIs_options', $arr);
	}
}


// Init plugin options to white list our options
function FTIs_init()
{
	register_setting( 'FTIs_plugin_options', 'FTIs_options', 'FTIs_validate_options' );
}


// Add menu page
function FTIs_add_options_page() 
{
	add_options_page('FTIs_plugin_options', 'Filetype Icons', 'manage_options', __FILE__, 'FTIs_render_form');
}


// Render the Plugin options form
function FTIs_render_form() 
{
	FTIs_add_defaults();
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Filetype Icons</h2>
		<form method="post" action="options.php">
			<?php settings_fields('FTIs_plugin_options'); ?>
			<?php $options = get_option('FTIs_options'); ?>
			<?php $randomIcon = FTIs_getRandomIcon(); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e('Choose icon size', 'FTIs'); ?></th>
					<td>
						<label><input name="FTIs_options[icon_Height]" type="radio" value="16" <?php checked('16', $options['icon_Height']); ?> /> 16px height <span style="color:#666666;margin-left:32px;"><img src="<?php echo plugins_url( 'icons/16/file_extension_'.$randomIcon.'.png' , __FILE__ ); ?>"></span></label><br />
						<label><input name="FTIs_options[icon_Height]" type="radio" value="32" <?php checked('32', $options['icon_Height']); ?> /> 32px height <span style="color:#666666;margin-left:32px;"><img src="<?php echo plugins_url( 'icons/32/file_extension_'.$randomIcon.'.png' , __FILE__ ); ?>"></span></label><br /><span style="color:#666666;"><?php _e('The used icon sets are licensed under a Creative Commons Attribution 3.0 License by', 'FTIs'); ?> <a href="fatcow.com" target="_blank" style="color:#72a1c6;">FatCow</a><?php _e('. Thanks.', 'FTIs'); ?></span>
					</td>
				</tr>
				<tr><td colspan="2"><div style="margin-top:10px;"></div></td></tr>
				<tr valign="top" style="border-top:#dddddd 1px solid;" valign="top">
					<th scope="row"><?php _e('Apply to...', 'FTIs'); ?></th>
					<td>
						<label><input name="FTIs_options[the_content]" type="checkbox" value="1" <?php if (isset($options['the_content'])) { checked('1', $options['the_content']); } ?> /> <?php _e('the post content', 'FTIs'); ?></label><br />
						<label><input name="FTIs_options[the_excerpt]" type="checkbox" value="1" <?php if (isset($options['the_excerpt'])) { checked('1', $options['the_excerpt']); } ?> /> <?php _e('the post excerpt (or post content, if there is no excerpt)', 'FTIs'); ?></label><br />
						<label><input name="FTIs_options[comment_text]" type="checkbox" value="1" <?php if (isset($options['comment_text'])) { checked('1', $options['comment_text']); } ?> /> <?php _e('the comment text', 'FTIs'); ?></label><br />
						<label><input name="FTIs_options[widget_text]" type="checkbox" value="1" <?php if (isset($options['widget_text'])) { checked('1', $options['widget_text']); } ?> /> <?php _e('the widget text', 'FTIs'); ?></label><br />
					</td>
				</tr>
				<tr><td colspan="2"><div style="margin-top:10px;"></div></td></tr>
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row"><?php _e('CSS', 'FTIs'); ?></th>
					<td>
						<textarea name="FTIs_options[css]" rows="7" cols="50" type='textarea'><?php echo $options['css']; ?></textarea><br /><span style="color:#666666;margin-left:2px;"><?php _e('Add your own css for the img-tag. Be carefull: no syntax check!', 'FTIs'); ?></span>
					</td>
				</tr>
				<tr><td colspan="2"><div style="margin-top:10px;"></div></td></tr>
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row"><?php _e('Database Options', 'FTIs'); ?></th>
					<td>
						<label><input name="FTIs_options[chk_default_options_db]" type="checkbox" value="1" <?php if (isset($options['chk_default_options_db'])) { checked('1', $options['chk_default_options_db']); } ?> /> <?php _e('Restore defaults', 'FTIs'); ?></label>
					</td>
				</tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
		<p style="margin-top:15px;">
			<p style="font-style: italic;font-weight: bold;color: #26779a;">
				<?php _e('If you have found this plugin at all useful, please consider making a ', 'FTIs'); ?><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4026696" target="_blank" style="color:#72a1c6;"><?php _e('donation', 'FTIs'); ?></a><?php _e('. Thanks.', 'FTIs'); ?>
			</p>
		</p>
	</div>
	<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function FTIs_validate_options($input) 
{
	 // strip html from textboxes
	return $input;
}

// Display a Settings link on the main Plugins page
function FTIs_plugin_action_links( $links, $file ) 
{
	if ( $file == plugin_basename( __FILE__ ) ) {
		return array_merge(
			$links,
			array(
				sprintf(
					'<a href="%soptions-general.php?page=%s">%s</a>',
					get_admin_url(),
					plugin_basename( __FILE__ ),
					__('Settings')
				)
			),
			array(
				sprintf(
					'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4026696">%s</a>',
					__('Donate', 'FTIs')
				)
			)
		);
	}
	return $links;
}

?>