<?php
// main model containing general config and UI functions
class WatuPROnta {
   static function install() {
   	global $wpdb;	
   	$wpdb -> show_errors();
   	
   	self::init();
	   
    // relations bewteen completed exams and mailing lists. 
    // For now not depending on exam result but place the field for later use
    if($wpdb->get_var("SHOW TABLES LIKE '".WATUPRONTA_RELATIONS."'") != WATUPRONTA_RELATIONS) {  
        $sql = "CREATE TABLE `".WATUPRONTA_RELATIONS."` (
				id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
				exam_id int(11) unsigned NOT NULL default '0',
				campaign_id VARCHAR(100) NOT NULL DEFAULT '',
				grade_id int(11) unsigned NOT NULL default '0'
			) CHARACTER SET utf8;";
        $wpdb->query($sql);         
    	}
	}	   
   
   // main menu
   static function menu() {
   	add_submenu_page('watupro_exams', __('Bridge to Ontraport', 'watupronta'), __('Bridge to Ontraport', 'watupronta'), 'manage_options', 
   		'watupronta', array('WatuPROntaBridge','main'));	
	}
	
	// CSS and JS
	static function scripts() {   
   	wp_enqueue_script('jquery');
	}
	
	// initialization
	static function init() {
		global $wpdb;
		load_plugin_textdomain( 'watuproget' );
		define('WATUPRONTA_RELATIONS', $wpdb->prefix.'watupronta_relations');
		
		add_action('watupro_completed_exam', array('WatuPROntaBridge', 'complete_exam'));
	}	
}