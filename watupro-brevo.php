<?php
/*
Plugin Name: WatuPRO to Brevo / SendInBlue Bridge 
Plugin URI: 
Description: Automatically subscribe users who take quizzes to Brevo
Author: Kiboko Labs
Version: 0.1
Author URI: http://calendarscripts.info/
License: GPLv2 or later
Text-domain: watubrevo
*/

define( 'WATUPROBRE_PATH', dirname( __FILE__ ) );
define( 'WATUPROBRE_RELATIVE_PATH', dirname( plugin_basename( __FILE__ )));
define( 'WATUPROBRE_URL', plugin_dir_url( __FILE__ ));

// require controllers and models
require_once(WATUPRONTA_PATH.'/models/basic.php');
require_once(WATUPRONTA_PATH.'/controllers/bridge.php');

add_action('init', array("WatuPROBrevo", "init"));

register_activation_hook(__FILE__, array("WatuPROBrevo", "install"));
add_action('watupro_admin_menu', array("WatuPROBrevo", "menu"));
