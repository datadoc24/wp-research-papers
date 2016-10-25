<?php



function ST4_get_question_category($post_ID){
    $categoryID = get_post_meta( $post_ID, '_risk_assessment_cid', true );
    $rsk_options_arr = get_option( 'risk_assessment_options' );
	$categoryName = $rsk_options_arr['category_'.$categoryID.'_name'];
	return $categoryName;
}

#comma-separated list of tag names (all taxonomies)
function ST4_get_question_terms($post_ID){
    
    $taxonomy_names = get_post_taxonomies($post_ID);
    
    $output='';
    
    foreach ($taxonomy_names as $tn){
        $tax_label = get_taxonomy($tn)->labels->name;
        $output.= get_the_term_list( $post_ID, $tn, $tax_label.': ', ', ','<br />' );
    }
    
    return $output;
}

function ST4_columns_riskquestions_head($defaults){
    unset($defaults['date']);
    $defaults['ra_question_terms'] = 'Question Categories';
    return $defaults;
}

function ST4_columns_riskquestions_content($column_name, $post_ID) {
    if ($column_name == 'ra_question_terms') {
        $termlist = ST4_get_question_terms($post_ID);
        if ($termlist) {
            echo $termlist;
        }
    }
}

function sortable_category_column($columns){
    $columns['ra_question_terms'] = 'Question Categories';
    return $columns;
}


function riskas_add_user_results_column($columns) {
    $columns['riskas_user_results'] = 'Assessment Completed';
    return $columns;
}
 

function riskas_show_user_results_column_content($value, $column_name, $user_id) {
    global $wpdb;
    global $ra_db_tablename;

	if ( 'riskas_user_results' == $column_name )
    {
		$assessment_date = $wpdb->get_var("SELECT max(time) FROM ".$wpdb->prefix.$ra_db_tablename." where userid = $user_id");
        //select latest date of risk results for this user
        return "<a href='?page=risk-results-detail&user_id=$user_id'>".$assessment_date."</a>";
    }
    return $value;
}





function select_answer($qid, $ans1,$ans2,$ans3,$ans4)
{
    $output="<select name='selanswer$qid' id='selanswer$qid' required>
    <option value=''>(select the most fitting answer)</option>
    <option value='$ans1'>Strongly agree</option>
    <option value='$ans2'>Somewhat agree</option>
    <option value='$ans3'>Somewhat disagree</option>
    <option value='$ans4'>Strongly disagree</option>
    </select>
    
    <script type='text/javascript'>
    
    jQuery('#selanswer$qid').change(function(event) {
        
        jQuery('#answertext$qid').html(jQuery('#selanswer$qid').val());
    }); 
    
    </script>
    ";
    return $output;
}

//todo:convert this to handle an array of possible answers
function select_button($qid, $answers, $selected)
{
    for($n=count($answers);$n>0;$n--){
        $checkedstring=null; if ($n==$selected) $checkedstring = "checked='checked'";
        $output .= "<strong>$n</strong> <input type='radio' name='selanswer$qid' id='".$n."btn$qid' value=$n $checkedstring>&nbsp;&nbsp;&nbsp;&nbsp;";
    }
    
    $output.="<script type='text/javascript'>";
    
    for($n=count($answers);$n>0;$n--){
        $output.="jQuery('#".$n."btn$qid').change(function(event) {
            jQuery('#answertext_id$qid').val('".$answers[$n-1]."');
            });";
    }
    
    $output.="</script>";
    
    return $output;
}

function process_risk_assessment()
{
    global $wpdb;
    global $ra_db_tablename;
    
    $nonce = $_POST['_ranonce'];
    
    if ( ! wp_verify_nonce( $nonce, 'risk-assessment-nonce' ) ) {
        // This nonce is not valid.
        echo "<span style='color:red;'>Invalid submitter</span>";
        die(); 
    }
    
    $userid = get_current_user_id();
    $success = TRUE;
    $tax = sanitize_text_field($_POST['assessment_taxonomy']);
    $extra_notes = sanitize_text_field($_POST['riskas_user_personal_notes_'.$tax.'_field']);
    
    //add the personal notes as a user meta field
    update_user_meta( $userid, 'riskas_personal_notes_'.$tax, $extra_notes);
    
    //iterate through post array looking for things from the risk assessment form
    foreach ($_POST as $key => $val){
        if (strpos($key,'selanswer')!==FALSE){
            
            $qid = substr($key,9);
            
            if($wpdb->replace($wpdb->prefix . $ra_db_tablename,array(
                'userid'=>$userid,
                'assessment'=>sanitize_text_field($_POST['assessment_label']),
                'questionid'=>$qid,
                'usernotes'=>sanitize_text_field($_POST['answertext_nm'.$qid]),
                'answercode'=>$val,
                'time'=>date('Y-m-d H:i:s')
            )) === FALSE) {
                $success = FALSE;
            }
        }
    }
    
    
    if($success===FALSE){
        echo "<span style='color:red;'>Error saving changes</span>";
    }
    else {
        echo "<span style='color:#24890d;'>All changes saved</span>";
    }
    die();
}
   
function ra_enqueue_fe_scripts(){
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_script('jquery');
    wp_enqueue_style('jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
}

//other taxonomies are related to the specific plugin application so they are defined in the functions.php file of the website's theme
function ra_multi_assessment_taxonomy(){
    
    $assessments_labels = array(
        'name'=>'Risk assessments',
        'singular_name'=>'Risk assessment',
        'search_items'=>'Search risk assessment',
        'all_items'=>'All risk assessments',
        'edit_item'=>'Edit risk assessment',
        'update_item'=>'Update risk assessment',
        'add_new_item'=>'Add new risk assessment',
        'new_item_name'=>'New risk assessment',
        'menu_name'=>'Risk assessments'
    );
    
    
    register_taxonomy ('risk_assessment', 'risk-questions', array('hierarchical'=>true, 'query_var'=>true, 'rewrite'=>true, 'labels'=>$assessments_labels));
    
    
}

function get_raforms_js(){
    
    $js = '<div id="riskassessment_save_2" style="margin-bottom:15px;"><input type=submit value="Save"></div>
    <script type="text/javascript">
        
        jQuery(document).ready(function() {
            jQuery(".JQDate").datepicker({dateFormat : "dd-mm-yy"});
        });
    
        jQuery("#risk_assessment_form").submit(ajaxSubmit);
        
       

        function ajaxSubmit(){
            jQuery("#riskassessment_save_1").html("<span style=\'color:blue;\'>Saving...</span>");
            jQuery("#riskassessment_save_2").html("<span style=\'color:blue;\'>Saving...</span>");
            var risk_assessment_form = jQuery(this).serialize();
            
            jQuery.ajax({
                type:"POST",
                url: "/wp-admin/admin-ajax.php",
                data: risk_assessment_form,
                success:function(data){
                    jQuery("#riskassessment_save_2").html(data);
                    jQuery("#riskassessment_save_1").html(data);
                }
        });

         jQuery("#risk_assessment_form").change(function(){
            jQuery("#riskassessment_save_1").html("<input type=submit value=\'Save\'>");
            jQuery("#riskassessment_save_2").html("<input type=submit value=\'Save\'>");
            
        });
        
        return false;
        }
    
    
    </script>';
    
    return $js;
    
}



//build the plugin user results page
function risk_assessment_results_page() {
	?>
    <div class="wrap">
    <h2><?php _e( 'Risk Assessment User Results', 'risk-plugin' ) ?></h2>
    <p>Click on a Risk Assessment user to see their results and action plan</p>    
    <?php
    
    $users = get_users( array( 'role__in' => array( 'risk_assessment_user' ) ) );
    // Array of stdClass objects.
    foreach ( $users as $user ) {
        echo '<div><a href="?page=risk-results-detail&user_id='.$user->id.'">' . esc_html( $user->first_name ) .' '. esc_html( $user->last_name ).'</a></div>';
    
    }

}

//build the plugin user results page
function risk_assessment_results_detail_page() {
    $user = get_userdata( intval($_GET['user_id'])) ;
    $taxlist = get_object_taxonomies( 'risk-questions','objects');
    
	?>
    <div class="wrap">
    <h2><?php _e( 'Risk Assessment User Results Detail for ', 'risk-plugin' ); echo (esc_html( $user->first_name ) .' '. esc_html( $user->last_name )); ?></h2>
    <p>Indivdual user results and action plan</p>
    
    <?php
    foreach ($taxlist as $taxobj){
        $taxname = $taxobj->name;
        
        echo '<div style="font-weight:bold;border-bottom:1px solid black;">'.__('User notes for ', 'risk-plugin').$taxobj->label.'</div><div>'.get_user_meta($user->ID, 'riskas_personal_notes_'.$taxname, true).'</div>';
    }
    
    

}

?>