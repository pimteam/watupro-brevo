<?php
// main model containing general config and UI functions
class WatuPROBrevo {
   static function install() {
        global $wpdb;	
        $wpdb -> show_errors();
        
        self::init();
        
        // relations bewteen completed exams and mailing lists. 
        // For now not depending on exam result but place the field for later use
        if($wpdb->get_var("SHOW TABLES LIKE '".WATUPROBRE_RELATIONS."'") != WATUPROBRE_RELATIONS) {  
                $sql = "CREATE TABLE `".WATUPROBRE_RELATIONS."` (
                        id mediumint unsigned NOT NULL auto_increment PRIMARY KEY,
                        exam_id int(11) unsigned NOT NULL default '0',
                        list_id mediumint NOT NULL DEFAULT 0,
                        grade_id int(11) unsigned NOT NULL default '0'
                    ) CHARACTER SET utf8;";
                $wpdb->query($sql);             	
        }	   
	}
   
   // main menu
   static function menu() {
        add_submenu_page('watupro_exams', __('Bridge to Brevo/SendInBlue', 'watuprobrevo'), __('Bridge to Brevo/SendInBlue', 'watuprobrevo'), 'manage_options', 
   		'watuprobrevo', array('WatuPROBrevoBridge','main'));	
	}
	
	// CSS and JS
	static function scripts() {   
        wp_enqueue_script('jquery');
	}
	
	// initialization
	static function init() {
		global $wpdb;
		load_plugin_textdomain( 'watuproget' );
		define('WATUPROBRE_RELATIONS', $wpdb->prefix.'watuprobrevo_relations');
		
		add_action('watupro_completed_exam', array('WatuPRObrevoBridge', 'complete_exam'));
	}	
}
