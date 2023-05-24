<div class="wrap">
	<h1><?php _e('WatuPRO to Ontraport Bridge', 'watupronta')?></h1>
	
	<p><?php _e('This bridge lets you automatically subscribe users who take your exams into select list in Ontraport.', 'watupronta')?></p>
	
	<?php if ( ! extension_loaded('curl') or ! is_callable('curl_init')):?>
		<p class="error">You will not be able to use this bridge because PHP curl extension is not installed on your server. Please contact your server admin or hosting support about this. Do not send email to us.</p>
	<?php endif;?>
	
	<p><?php _e('Note that in order to have quiz taker subscribed you need their email address. So this will work the user is logged or (for non-logged in users) when you have selected "Send email to the user with their results" in the exam settings page.','watuproget')?> </p>
	
	<form method="post">
		<p>Your Ontraport API Key: <input type="text" name="api_key" value="<?php echo $api_key?>" size="60"> </p>
		<p>Your Ontraport APP ID: <input type="text" name="app_id" value="<?php echo $app_id?>" size="60"> </p>
		
		
		<input type="submit" name="set_key" value="<?php _e('Save Settings', 'watupronta');?>" class="button-primary"></p>
		<?php wp_nonce_field('watupronta_settings');?>
	</form> 
	
	<?php if(empty($api_key)):?>
		<p><b><?php _e('You will not be able to add any rules until you enter your Ontraport API Key and App ID.', 'watupronta');?></b></p>
	<?php return;
	endif;
	if(!empty($response->code) or empty($response)):?>
		<p><?php _e("We couldn't retrieve any campaigns from your Ontraport account.", 'watupronta');?></p>
		<?php if(!empty($response->code)):?>
			<p><?php _e("We got this error:", 'watupronta');?> <b><?php echo $response->message;?></b></p>
		<?php endif;?>
		<p><a href="#" onclick="jQuery('#CmonResponse').toggle();return false;"><?php _e('Raw Ontraport response (debug)', 'watupronta');?></a></p>
		<div style="display:none;" id="CmonResponse"><?php print_r($response);?></div>
	<?php endif;?>
   
   <h2><?php _e('Add New Rule', 'watupronta')?></h2>	  
	  
	 <form method="post">
	 	<div class="wrap">
	 			<?php _e('When user completes', 'watupronta')?> <select name="exam_id" onchange="wcChangeQuiz(this.value, 'wbbGradeSelector');">
	 			<option value=""><?php _e('- Select quiz -');?></option>
	 			<?php foreach($exams as $exam):?>
	 				<option value="<?php echo $exam->ID?>"><?php echo stripslashes($exam->name)?></option>
	 			<?php endforeach;?>
	 			</select> 
				
				<?php _e('achieving the following grade:', 'watupronta')?>
				<span id="wbbGradeSelector">
					<select name="grade_id">
					   <option value="0"><?php _e('- Any grade -', 'watupronta');?></option>
					   <?php foreach($exams[0]->grades as $grade):?>
					   	<option value="<?php echo $grade->ID?>"><?php echo stripslashes($grade->gtitle);?></option>
					   <?php endforeach;?>
					</select>
				</span>				
				 			
	 			<?php printf(__('subscribe them to campaign','watupronta'), '');?> 
	 			<select name="campaign_id">
	 				<option value=""><?php _e('- Select campaign -');?></option>
	 				<?php foreach($campaigns as $campaign):?>
	 					<option value="<?php echo $campaign->id?>"><?php echo stripslashes($campaign->name)?></option>
	 				<?php endforeach;?>
	 			</select>
	 			<input type="submit" name="add" value="<?php _e('Add Rule', 'watupronta')?>" class="button-primary">
	 	</div>
	 	<?php wp_nonce_field('watupronta_rule');?>
	 </form> 
	 
	 <h2><?php _e('Manage Existing Rules', 'watupronta')?></h2>
	 <?php if(count($relations)):?>
	 	<?php foreach($relations as $relation):?>
	 	<form method="post">
	 	<input type="hidden" name="id" value="<?php echo $relation->id?>">
	 	<input type="hidden" name="del" value="0">
	 	<div class="wrap">
	 			<?php _e('When user completes', 'watupronta')?> <select name="exam_id" onchange="wcChangeQuiz(this.value, 'wbbGradeSelector<?php echo $relation->id?>');">
	 			<option value=""><?php _e('- Select quiz -');?></option>
	 			<?php foreach($exams as $exam):
	 				$selected = ($exam->ID == $relation->exam_id) ? " selected" : "";?>
	 				<option value="<?php echo $exam->ID?>"<?php echo $selected?>><?php echo stripslashes($exam->name)?></option>
	 			<?php endforeach;?>
	 			</select> 
	 			
				<?php _e('achieving the following grade:', 'watupronta')?>
				<span id="wbbGradeSelector<?php echo $relation->id?>">
					<select name="grade_id">
					   <option value="0"><?php _e('- Any grade -', 'watupronta');?></option>
					   <?php foreach($relation->grades as $grade):
					   	$selected = ($grade->ID == $relation->grade_id) ? " selected" : "";?>
					   	<option value="<?php echo $grade->ID?>"<?php echo $selected?>><?php echo stripslashes($grade->gtitle);?></option>
					   <?php endforeach;?>
					</select>
				</span>			
					 			
	 			<?php _e('subscribe them to campaign','watuproget')?> 
	 			<select name="campaign_id">
	 				<option value=""><?php _e('- Select campaign -');?></option>
	 				<?php foreach($campaigns as $campaign):
	 					$selected = ($campaign->id == $relation->campaign_id) ? 'selected' : '';?>
	 					<option value="<?php echo $campaign->id?>" <?php echo $selected;?>><?php echo stripslashes($campaign->name);?></option>
	 				<?php endforeach;?>
	 			</select>
	 			<input type="submit" name="save" value="<?php _e('Save Rule', 'watupronta')?>" class="button-primary">
	 			<input type="button" value="<?php _e('Delete Rule', 'watupronta')?>" onclick="WCConfirmDelete(this.form);" class="button">
	 	</div>
	 	<?php wp_nonce_field('watupronta_rule');?>
	 </form> 
	 	<?php endforeach;?>
	 <?php else:?>
	 <p><?php _e('You have not created any rules yet.', 'watupro');?></p>	
	 <?php endif;?>
</div>

<script type="text/javascript" >
function WCConfirmDelete(frm) {
		if(confirm("<?php _e('Are you sure?', 'watupronta')?>")) {
			frm.del.value=1;
			frm.submit();
		}
}

function wcChangeQuiz(quizID, selectorID) {
	// array containing all grades by exams
	var grades = {<?php foreach($exams as $exam): echo $exam->ID.' : {';
			foreach($exam->grades as $grade):
				echo $grade->ID .' : "'.$grade->gtitle.'",';
			endforeach;
		echo '},';
	endforeach;?>};
	
	// construct the new HTML
	var newHTML = '<select name="grade_id">';
	newHTML += "<option value='0'><?php _e('- Any grade -', 'watupronta');?></option>";
	jQuery.each(grades, function(i, obj){
		if(i == quizID) {
			jQuery.each(obj, function(j, grade) {
				newHTML += "<option value=" + j + ">" + grade + "</option>\n";
			}); // end each grade
		}
	});
	newHTML += '</select>'; 
	
	jQuery('#'+selectorID).html(newHTML);
}
</script>