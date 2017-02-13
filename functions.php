<?php
	

	//Formidable - Don't Allow Completed to be Edited (IPDP)
	add_filter('frm_user_can_edit', 'maybe_prevent_user_edit_entry', 10, 2);
	function maybe_prevent_user_edit_entry( $edit, $args ){
		if ( ! $edit ) {
			return $edit;
		}
	  
		if ( $args['form']->id != 7 ) {
			return $edit;
		}
	
		if ( is_numeric( $args['entry'] ) ) {
			$entry_id = $args['entry'];
		} else {
			$entry_id = $args['entry']->id;
		}
	
		$field_value = FrmProEntriesController::get_field_value_shortcode( array( 'field_id' => 155, 'entry' => $entry_id ) );
	
		if ( $field_value == 'Completed' ) {
			$edit = false;
		}
	
		return $edit;
	}
	
	
	//Formidable - Don't Allow Approved to be Edited on Certain Fields (IPDP)
	add_filter('frm_setup_edit_fields_vars', 'frm_set_read_only', 20, 3);
	function frm_set_read_only($values, $field, $entry_id){
		if ( FrmAppHelper::is_admin() ) {
			return $values;
		}
	  	
	  	$field_value = FrmProEntriesController::get_field_value_shortcode( array( 'field_id' => 223, 'entry' => $entry_id ) );
	
		// If on front-end, make specific fields read-only
		if ( in_array( $field->id, array( 108,110,111,112,113,116,117,118,122,123,124,128,129,130,131,132,133,134,135,136,137,141,142,143,144,145,146,150,152 ) ) && ($field_value == 'Approved') ) { 
		   $values['read_only'] = 1;
		}	
		return $values;
	}
	
	//Formidable - Don't allow a user to submit another IPDP if one is Submitted (IPDP)
	if (is_user_logged_in())
	{
		add_action('frm_display_form_action', 'check_entry_count', 8, 3);
		function check_entry_count($params, $fields, $form)
		{
			remove_filter('frm_continue_to_new', '__return_false', 50);
			if($form->id == 7)
			{
				$ipdpsubmissionstatus = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 155, 'user_id' => 'current'));
				if($ipdpsubmissionstatus=="Submitted"){
					echo 'You already have a submitted IPDP. Please wait for Approval.';
					add_filter('frm_continue_to_new', '__return_false', 50);
		    	}
		  	}
		}
	}

	//Formidable - When edited on front end reset status to submitted (IPDP)
	add_filter('frm_setup_edit_fields_vars', 'frm_set_edit_val', 20, 3);
	function frm_set_edit_val( $values, $field, $entry_id ) {
	if ( $field->id == 155 ) {
		if ( FrmAppHelper::is_admin() ) {
			return $values;
		}
		
		$field_value = FrmProEntriesController::get_field_value_shortcode( array( 'field_id' => 155, 'entry' => $entry_id ) );
	
		if ( $field_value == 'Submitted' or $field_value == 'Not Approved' ) {
			$values['value'] = 'Submitted';
		}		
	}
	return $values;
	}
	
	//Formidable - When edited and marked as approved (Evidence Submission)
	add_filter('frm_after_update_entry', 'frm_set_edit_val_evidence', 10, 2);
	function frm_set_edit_val_evidence($entry_id, $form_id){
		
		if($form_id==6)
		{
			$userid=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 101, 'entry' => $entry_id));
			$approvalstatus=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 102, 'entry' => $entry_id));
			$issuecredit=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 103, 'entry' => $entry_id));
			$titleofactivity=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 94, 'entry' => $entry_id));
			$titleofactivitystrip = str_replace(' ', '-', $titleofactivity);
			$titleofactivitystrip = preg_replace('/[^A-Za-z0-9\-]/', '', $titleofactivitystrip);
			$activityhours=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 95, 'entry' => $entry_id));
			$typeofactivity=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 84, 'entry' => $entry_id));
			$dateofactivity=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 92, 'entry' => $entry_id));
			$expectednumberattending=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 90, 'entry' => $entry_id));
			$schoolyear=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 91, 'entry' => $entry_id));
			$minutes=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 95, 'entry' => $entry_id));
			$descofactivity=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 96, 'entry' => $entry_id));
			$commentsofsubmissions=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 97, 'entry' => $entry_id));
			$formattachments=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 99, 'entry' => $entry_id));
			$uploadedceupoints=FrmProEntriesController::get_field_value_shortcode(array('field_id' => 86, 'entry' => $entry_id));
			
			//Convert CEU's to Minutes
			$previousminutes=$uploadedceupoints*600;
			
			//Convert Hours to Minutes
			$activityminutes=$activityhours*60;
			
			if($approvalstatus=="Approved" && $issuecredit=="Yes" && $typeofactivity!="An uploaded (Educator Leaving a District Form) from a previous district")
			{
				mycred_add( 'HCSD_Points', $userid, $activityminutes, $titleofactivity, date( 'y' ) );	
			}

			if($approvalstatus=="Approved" && $issuecredit=="Yes" && $typeofactivity=="An uploaded (Educator Leaving a District Form) from a previous district")
			{
				mycred_add( 'HCSD_Points', $userid, $previousminutes, "Previously Earned CEU Credit", date( 'y' ) );	
			}
			
			if($approvalstatus=="Approved" && $typeofactivity=="A group of people within the district")
			{
				
				//Get Random Code
				function randomString($length = 6) {
					$str = "";
					$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
					$max = count($characters) - 1;
					for ($i = 0; $i < $length; $i++) {
						$rand = mt_rand(0, $max);
						$str .= $characters[$rand];
					}
					return $str;
				}

				$uniquecode = randomString();
				$coupon_post_id = mycred_create_new_coupon( array('code' => $uniquecode, 'value' => $activityminutes, 'global_max' => $expectednumberattending,'user_max' => 1) );
				
				//Add description to coupon code
				if ( $coupon_post_id !== NULL && ! is_wp_error( $coupon_post_id ) ) {
				        add_post_meta( $coupon_post_id, 'description', $titleofactivity, true );
				}
				
				//Create the Post
		        $my_post = array();
		        $url="Group Professional Development Activity $uniquecode";
		        $url = str_replace(' ', '-', $url);
				$my_post['post_title'] = "Group Professional Development Activity $uniquecode";
				$my_post['post_content'] = "[hide for='!logged'][two_third last='no' spacing='yes' center_content='no' hide_on_mobile='no' background_color='' background_image='' background_repeat='no-repeat' background_position='left top' hover_type='none' link='' border_position='all' border_size='0px' border_color='' border_style='' padding='' margin_top='' margin_bottom='' animation_type='' animation_direction='' animation_speed='0.1' animation_offset='' class='' id=''][title size='3' content_align='left' style_type='default' sep_color='' margin_top='' margin_bottom='' class='' id='']Professional Development Information[/title][fusion_text]<p>Activity: $titleofactivity<br>School Year: $schoolyear<br>Hours: $minutes<br>Description of Activity: $descofactivity<br>Comments (if relevant): $commentsofsubmissions<br>Attachments:".$formattachments."[/fusion_text][/two_third][one_third last='yes' spacing='yes' center_content='no' hide_on_mobile='no' background_color='' background_image='' background_repeat='no-repeat' background_position='left top' hover_type='none' link='' border_position='all' border_size='0px' border_color='' border_style='' padding='' margin_top='' margin_bottom='' animation_type='' animation_direction='' animation_speed='0.1' animation_offset='' class='' id=''][title size='3' content_align='left' style_type='default' sep_color='' margin_top='' margin_bottom='' class='' id='']Claim Credit with Coupon Code[/title][fusion_text][mycred_load_coupon button='Claim Credit'][/fusion_text][/one_third][/hide][hide for='logged']Please login to view this page.[/hide]";
				$my_post['post_status'] = "publish";
				$my_post['post_category'] = array( 26 );
				wp_insert_post( $my_post );
				
				//Send the Email		        
				$user_info = get_userdata($userid);
				$useremail = $user_info->user_email;
				$to = $useremail;
				$subject = "Activity Proposal Coupon - $titleofactivity";
				$txt = "Here is your Coupon Code for $titleofactivity on $dateofactivity (Your coupon code includes everything in large type): <br><br><b style='font-size: 48px'>$uniquecode</b><br><br><a href='https://edu.hcsdoh.net/$url/' target=_blank>https://edu.hcsdoh.net/$url/</a><br><br><b>Hosts:</b> Forward this email to participants.<br><br><b>Participants:</b> Click on the link and enter the coupon code provided in this email.<br><br>Please redeem your code by the end of the day.<br><br><b>Keep in mind it is unethical to redeem CEUs when not in attendance of the professional development session and could compromise your ODE license.</b>";
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				$headers .= 'From: HCSD LPDC Committee <noreply@hcsdoh.org>' . "\r\n" . "CC: tsmith@hcsdoh.org";
				mail($to,$subject,$txt,$headers);
				
			}
		}
	
	return $values;
	}
	
	//MyCred - If user is logged in, calculute number of CEU's earned for their latest approved IPDP
	if ( is_user_logged_in()){
		function currentceusonipdp(){
				$ipdp_start_date = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 123, 'user_id' => 'current'));
				$ipdp_end_date = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 124, 'user_id' => 'current'));
				$approvalstatus = FrmProEntriesController::get_field_value_shortcode( array( 'field_id' => 155, 'user_id' => 'current' ) );
				if($ipdp_start_date!=NULL && $approvalstatus=="Approved")
				{
					$currentuserid=get_current_user_id();
					$ipdp_start_date = strtotime($ipdp_start_date);
					$ipdp_start_date=date('Y-m-d', $ipdp_start_date);
					$ipdp_end_date = strtotime($ipdp_end_date);
					$ipdp_end_date=date('Y-m-d', $ipdp_end_date);
					$ceusonipdp=mycred_get_total_by_time( $ipdp_start_date, $ipdp_end_date, NULL, $currentuserid );
					$ceusearned=$ceusonipdp/600;
					$ceusearned=round($ceusearned,4);
					return "$ceusearned out of 18";
				}
				else
				{
					return "You do not have an approved IPDP. In order to receive credit for professional development time that will count towards license renewal, you must have an approved IPDP.";
				}
		}
		add_shortcode( 'ceu_on_ipdp', 'currentceusonipdp' );
	}
		
	//MyCred/Formidable - IPDP completion percentage
	if ( is_user_logged_in()){
		function ceupercentage(){
			$ceuammount=do_shortcode('[ceu_on_ipdp]');
			$ceupercentage=($ceuammount/18)*100;
			
			$ceupercentage=round($ceupercentage);
			return do_shortcode("[fusion_progress percentage='$ceupercentage' unit='%' filledcolor='' unfilledcolor='#f7f7f7' striped='no' animated_stripes='no' textcolor='#000' animation_offset='{{animation_offset}}' class='' id='']IPDP Completion[/fusion_progress]<br>");
		}
		add_shortcode( 'ceu_percentage', 'ceupercentage' );
	}
	
	
	//MyCred - Add coupon description to log
	add_filter( 'mycred_run_this', 'mycredpro_inject_custom_entry_for_coupons', 10, 2 );
	function mycredpro_inject_custom_entry_for_coupons( $run_this, $mycred ) {
	
		if ( $run_this['ref'] != 'coupon' ) return $run_this;
	
		$coupon_post_id = $run_this['ref_id'];
		$coupon_description = get_post_meta( $coupon_post_id, 'description', true );
		
		if ( $coupon_description != '' )
			$run_this['entry'] = $coupon_description;
	
		return $run_this;
	
	}
	
	
	//IPDP completion percentage
	if ( is_user_logged_in()){
		function eduprofile(){
			$current_user = wp_get_current_user();
			$current_license_ID = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 117, 'user_id' => 'current'));
			if($current_license_ID==""){ $current_license_ID="Transferred IPDP"; }
			return "<br>Name: ".$current_user->display_name ."<br>Email: ". $current_user->user_email . "<br>State License ID: ". $current_license_ID;
		}
		add_shortcode( 'edu_profile', 'eduprofile' );
	}
