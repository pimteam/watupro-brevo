<?php
class WatuPROntaBridge {
   static function main() {
   	  global $wpdb;
   	  
   	  // save Ontaport API Key and APP ID
   	  if(!empty($_POST['set_key']) and check_admin_referer('watupronta_settings')) {
			  $double_optin = empty($_POST['no_optin']) ? 0 : 1;   	 
			
   	  	  update_option('watupronta_api_key', sanitize_text_field($_POST['api_key']));
   	  	  update_option('watupronta_app_id', sanitize_text_field($_POST['app_id']));
   	  }
   	  
   	  $api_key = get_option('watupronta_api_key');
   	  $app_id = get_option('watupronta_app_id');
   	
   	  // select exams
   	  $exams = $wpdb->get_results("SELECT * FROM ".WATUPRO_EXAMS." ORDER BY name");
   	  
   	  // add/edit/delete relation
   	  if(!empty($_POST['add']) and check_admin_referer('watupronta_rule')) {
				// no duplicates		
				$exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".WATUPRONTA_RELATIONS."
					WHERE exam_id=%d AND campaign_id=%s AND grade_id=%d", $_POST['exam_id'], $_POST['campaign_id'], $_POST['grade_id']));   	  	
   	  	
   	  	if(!$exists) {
					$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPRONTA_RELATIONS." SET 
						exam_id = %d, campaign_id=%s, grade_id=%d", $_POST['exam_id'], $_POST['campaign_id'], $_POST['grade_id']));
					}   	  
   	  }
   
   		if(!empty($_POST['save']) and check_admin_referer('watupronta_rule')) {
				$wpdb->query($wpdb->prepare("UPDATE ".WATUPRONTA_RELATIONS." SET 
					exam_id = %d, campaign_id=%s, grade_id=%d WHERE id=%d", 
					$_POST['exam_id'], $_POST['campaign_id'], $_POST['grade_id'], $_POST['id']));   	  
   	  }
   	  
			if(!empty($_POST['del']) and check_admin_referer('watupronta_rule')) {
				$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPRONTA_RELATIONS." WHERE id=%d", $_POST['id']));
			}   	  
   	  
   	  // select existing relations
   	  $relations = $wpdb->get_results("SELECT * FROM ".WATUPRONTA_RELATIONS." ORDER BY id");
   	  
   	  // select all non-category grades and match them to exams and relations
   	  $grades = $wpdb->get_results("SELECT * FROM ".WATUPRO_GRADES." WHERE cat_id=0 ORDER BY gtitle");
   	  
   	  foreach($exams as $cnt=>$exam) {
   	  	  $exam_grades = array();
   	  	  foreach($grades as $grade) {
   	  	  	if($grade->exam_id == $exam->ID) $exam_grades[] = $grade;
			  }
			  
			  $exams[$cnt]->grades = $exam_grades;
   	  }
   	  
   	  foreach($relations as $cnt=>$relation) {
   	  	  $rel_grades = array();
   	  	  foreach($grades as $grade) {
   	  	  	if($grade->exam_id == $relation->exam_id) $rel_grades[] = $grade;
			  }
			  
			  $relations[$cnt]->grades = $rel_grades;
   	  }
   	     	  
   	     	  
   	  // get campaigns
		 // use OntraportAPI\Ontraport;

	    $client = new OntraportAPI\Ontraport($app_id, $api_key);    
	    $requestParams = array(
	        "listFields" => "id,name"
	    );
	    $response = $client->campaignbuilder()->retrieveMultiple($requestParams);   	 
				    
	    $response = json_decode($response);
	    //print_r($response);
	    $campaigns = $response->data;
	    	     	  
		 include(WATUPRONTA_PATH."/views/main.html.php");
   }

	 // actually subscribe the user
	 static function complete_exam($taking_id) {
	 	  global $wpdb;

	 	  // select taking		
	 	  $taking = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".WATUPRO_TAKEN_EXAMS." 	
	 	  	WHERE ID=%d", $taking_id));
	 	  	
	 	  // if email not available, return false
			if(empty($taking->user_id) and empty($taking->email)) return false;
			
	 	  // see if there are any relations for this exam ID
	 	  $relations = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPRONTA_RELATIONS." 
		 	  WHERE exam_id=%d", $taking->exam_id));
		 	  
		 	if(!count($relations)) return false;  
		 	 //echo "HERE BRIDGE 2";
	
			$email = $taking->email;
			$name = $taking->name;
			if(empty($email)) {
				$user = get_userdata($taking->user_id);
	      	$email = $user->user_email;
	      	$name = $user->display_name;
			}
			
			// name still empty? Default to guest although that's not a great idea
			if(empty($name)) $name = 'Guest';
			
			// break first & last			
			$parts = explode(" ", $name);
			$fname = $parts[0];
			$lname = @$parts[1];
	      
	      // add to Ontaport
	      $api_key = get_option('watupronta_api_key');
   	   $app_id = get_option('watupronta_app_id');
	   	   	
	   	$double_optin = (get_option('watuproget_no_optin') == '1') ? false : true;
	   	
	   	// use OntraportAPI\Ontraport;

         $client = new OntraportAPI\Ontraport($app_id, $api_key);    
         
         //print_r($client);
	   	
	   	// select mailing lists from getresponse
	   	foreach($relations as $relation) {
			   // check grade
				if(!empty($relation->grade_id) and $relation->grade_id != $taking->grade_id) continue;
				
				// check if email exists
				$requestParams = array(
			        "objectID" => 0, // Contact object
			        "email"    => $email
			    );
			    $response = $client->object()->retrieveIdByEmail($requestParams);
			    $response = json_decode($response);
			    if(empty($response->data->id)) {
			    	 $requestParams = array(
			        "firstname" => $fname,
			        "lastname"  => $lname,
			        "email"     => $email
				    );
				    $response = $client->contact()->create($requestParams);
				    
				    // get ID and add to campaign
				    $user = json_decode($response);
				    $user_id = $user->data->id;
			    }
				 else $user_id = $response->data->id;
	
			    // add to campaign
			    $requestParams = array(
			        "objectID" => $user_id, // Contact object
			        "ids"      => $user_id,
			        "add_list" => $relation->campaign_id,
			        "sub_type" => "Campaign"
			    );
			    $response = $client->object()->subscribe($requestParams);
				
			} // end foreach relation	
   } // end complete exam
   
   // get API endpoint URL based on settings
   static function get_url() {
   	 $is_360 = get_option('watuproget_is_360');
  	 
   	 if(!$is_360) return 'https://api.getresponse.com/v3';
   	 
   	 // else 
   	 return get_option('watuproget_type'); 
   }
}