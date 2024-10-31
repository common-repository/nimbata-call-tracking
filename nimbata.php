<?php
/*
Plugin Name: Nimbata Call Tracking
Plugin URI: https://wordpress.org/plugins/nimbata-call-tracking/
Description: Dynamically swap your site's phone number with a nimbata tracking number. Track which sources or sessions make your phones ring. This plugin will use the configuration you've setup through the nimbata app.
Version: 1.7.1
Author: Nimbata
Author URI: http://www.nimbata.com
License: GPL v3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

const VERSION = '1.7';

function nimbata_admin_init() {
  register_setting('nimbata', 'nimbata_dni_id');
}

function nimbata_admin_menu() {
  add_options_page('Nimbata', 'Nimbata', 'manage_options', 'nimbata', 'nimbata_options');
}

function nimbata_admin_notice() {
  $nimbata_dni_id = get_option('nimbata_dni_id');
  $is_plugins_page = (substr( $_SERVER["PHP_SELF"], -11 ) == 'plugins.php') ? true : false;
  if( $is_plugins_page && $nimbata_dni_id == 0 && function_exists( "admin_url" ) ){
    echo  "<div class='error'>
            <p><strong>" .
              sprintf( __('<a href="%s">Enter your Nimbata DNI ID</a> to enable dynamic call tracking.', 'nimbata' ), admin_url( 'options-general.php?page=nimbata' ) ) .
            "</strong></p></div>";
  }
}

function run_nimbata_dni() {
  $nimbata_dni_id = get_option('nimbata_dni_id');
  if (!empty($nimbata_dni_id) and ($nimbata_dni_id != '0') )
  {
    $dniIds = explode(",", $nimbata_dni_id);
    foreach ($dniIds as $dniId)
    {

      wp_enqueue_script( 'nimbata-ct-script-'.trim($dniId), '//cdn.dni.nimbata.com/' . trim($dniId) . '.min.js', array(), VERSION, true );

    }

  }
}

function add_nimbata_script_attributes( $tag, $handle, $src ) {
    $dniId = get_option('nimbata_dni_id');
    $hash = get_option('nimbata_dni_sri');

    if ( 'nimbata-ct-script-' . trim($dniId) === $handle && !empty($hash) ) {
        $tag = '<script type="text/javascript" src="' . esc_url( $src ) . '" integrity="'.$hash.'" crossorigin="anonymous"></script>';
    }

    return $tag;
}

function activate_nimbata() { add_option('nimbata_dni_id', '0'); }
function deactive_nimbata() { delete_option('nimbata_dni_id'); }

register_activation_hook(__FILE__, 'activate_nimbata');
register_deactivation_hook(__FILE__, 'deactive_nimbata');

if (is_admin()) {
  add_action('admin_init', 'nimbata_admin_init');
  add_action('admin_menu', 'nimbata_admin_menu');
  add_action('admin_notices', 'nimbata_admin_notice');
} else{
  add_action('wp_enqueue_scripts', 'run_nimbata_dni');
  add_filter( 'script_loader_tag', 'add_nimbata_script_attributes', 10, 3 );
}

function nimbata_options() {

  if (!current_user_can('manage_options')) {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

  if( ( isset($_POST['nimbata_dni_id_input']) && $_POST['nimbata_dni_id_input'] != '0' ) or
      ( isset($_POST['nimbata_dni_sri_input']) )
    ){

    if (isset($_POST['nimbata_dni_id_input'])){
      update_option('nimbata_dni_id', preg_replace('/\s+/', '', $_POST['nimbata_dni_id_input']) );
    }

    if(isset($_POST['nimbata_dni_sri_input'])){
      update_option('nimbata_dni_sri', preg_replace('/\s+/', '', $_POST['nimbata_dni_sri_input']) );
    }

    $status_msg = "<div class='update'><p><strong>Your Nimbata settings were saved successfully.</strong></p></div>";
  } else {
    $status_msg ="";
  }

  echo "
  <div class='wrap'>
    <h2>Nimbata Settings</h2>
    <p>Dynamically swap your site's phone number with a nimbata tracking number. Track which sources or sessions make your phones ring. This plugin will use the configuration you've setup through the nimbata app.</p>
    <form method='POST' action=''>
      <?php wp_nonce_field('update-nimbata-options'); ?>
      <?php settings_fields('nimbata'); ?>
      <p>Don't have a DNI ID yet? Visit <a href='https://app.nimbata.com/login'>Nimbata</a>. Go to 'Setup' > 'Dynamic Number Insertion'.</p>
      <table class='form-table'>
        <tr valign='top'>
          <th scope='row'>Nimbata DNI ID(s): </th>
          <td>
            <input type='text' name='nimbata_dni_id_input' value='".get_option('nimbata_dni_id')."' />
            <p>Comma separate to add multiple DNI IDs to a single site.</p>
          </td>
        </tr>
        <tr valign='top'>
          <th scope='row'>Subresource Integrity (SRI) Hash (optional) </th>
          <td>
            <input type='text' name='nimbata_dni_sri_input' placeholder='shaNNN-XXXXXXXX' value='".get_option('nimbata_dni_sri')."' />
          </td>
        </tr>
      </table>
      <p class='submit'>
        <input type='submit' name='Submit' class='button-primary' value='Save Changes' />
      </p>
    </form>
  </div>".$status_msg;

}


