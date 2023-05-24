<?php
class WatuPROBrevoBridge {
   static function main() {
   	  global $wpdb;
   	  
   	  // save Ontaport API Key and APP ID
   	  if(!empty($_POST['set_key']) and check_admin_referer('watuprobrevo_settings')) {
		 $double_optin = empty($_POST['no_optin']) ? 0 : 1;   	 
			
   	  	  update_option('watuprobrevo_api_key', sanitize_text_field($_POST['api_key']));
   	  	  
   	  }
   	  
   	  $api_key = get_option('watuprobrevo_api_key');   	  
   	
   	  // select exams
   	  $exams = $wpdb->get_results("SELECT * FROM ".WATUPRO_EXAMS." ORDER BY name");
   	  
   	  // add/edit/delete relation
   	  if(!empty($_POST['add']) and check_admin_referer('watuprobrevo_rule')) {
				// no duplicates		
				$exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".WATUPROBRE_RELATIONS."
					WHERE exam_id=%d AND list_id=%d AND grade_id=%d", intval($_POST['exam_id']), intval($_POST['list_id']), intval($_POST['grade_id'])));   	  	
   	  	
   	  	if(!$exists) {
					$wpdb->query($wpdb->prepare("INSERT INTO ".WATUPROBRE_RELATIONS." SET 
						exam_id = %d, list_id=%s, grade_id=%d", intval($_POST['exam_id']), intval($_POST['list_id']), intval($_POST['grade_id'])));
					}   	  
   	  }
   
   		if(!empty($_POST['save']) and check_admin_referer('watuprobrevo_rule')) {
				$wpdb->query($wpdb->prepare("UPDATE ".WATUPROBRE_RELATIONS." SET 
					exam_id = %d, list_id=%d, grade_id=%d WHERE id=%d", 
					intval($_POST['exam_id']), intval($_POST['list_id']), intval($_POST['grade_id']), intval($_POST['id'])));   	  
   	  }
   	  
			if(!empty($_POST['del']) and check_admin_referer('watuprobrevo_rule')) {
				$wpdb->query($wpdb->prepare("DELETE FROM ".WATUPROBRE_RELATIONS." WHERE id=%d", intval($_POST['id'])));
			}   	  
   	  
   	  // select existing relations
   	  $relations = $wpdb->get_results("SELECT * FROM ".WATUPROBRE_RELATIONS." ORDER BY id");
   	  
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
   	     	  
   	   if(!empty($api_key)) {
            // get lists
            $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);

            $apiInstance = new SendinBlue\Client\Api\ContactsApi(
                new GuzzleHttp\Client(),
                $config
            );
            $limit = 10;
            $offset = 0;

            try {
                $_list = $apiInstance->getLists($limit, $offset);
                //print_r($result);
                $lists = $_list->getLists();
                print_r($lists);
            } catch (Exception $e) {
                echo 'Exception when calling ContactsApi->getFolderLists: ', $e->getMessage(), PHP_EOL;
            }
   	   }   	  
        
       include(WATUPROBRE_PATH."/views/main.html.php");
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
	 	  $relations = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".WATUPROBRE_RELATIONS." 
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
			$lname = $parts[1] ?? '';
	      
	      // add to Ontaport
	      $api_key = get_option('watuprobrevo_api_key');
   	   	   	   	
	   	$double_optin = (get_option('watuproget_no_optin') == '1') ? false : true;
	   	
	   $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);
	   $apiInstance = new SendinBlue\Client\Api\ContactsApi(
                    new GuzzleHttp\Client(),
                    $config
                );
	   	
	   	// select mailing lists from getresponse
	   	foreach($relations as $relation) {
			   // check grade
				if(!empty($relation->grade_id) and $relation->grade_id != $taking->grade_id) continue;		
				
				// create the contact if it does not exist
				try {                    
                    $result = $apiInstance->getContactInfo($email);                    
                } catch (Exception $e) {}
				
				if(empty($result) or !is_object($result)) {
                    $createContact = new \SendinBlue\Client\Model\CreateContact(); // Values to create a contact
                    $createContact['email'] = $email;
                    $createContact['attributes'] = [
                        'FIRSTNAME' => $fname,
                        'LASTNAME' => $lname,
                    ];
                    $createContact['listIds'] = [intval($relation->list_id)];
                    
                    try {
                        $result = $apiInstance->createContact($createContact);
                    // print_r($result);
                    } catch (Exception $e) {
                            print_r($e);
                        echo 'Exception when calling ContactsApi->createContact: ', $e->getMessage(), PHP_EOL;
                        return;
                    }
				}
                
				// now add the contact
				$contactIdentifiers = new \SendinBlue\Client\Model\AddContactToList();
                $contactIdentifiers['emails'] = [$email];
                
                try {
                    $result = $apiInstance->addContactToList(intval($relation->list_id), $contactIdentifiers);
                    //print_r($result);
                } catch (Exception $e) {
                    
                }
			} // end foreach relation	
   } // end complete exam
   
}
