<?php
/* 
Template Name: User Profile
*/ 

    session_start();
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    // echo get_template_directory_uri();
    
    global $wpdb;
    $frmt_form_entry_meta = $wpdb->prefix."frmt_form_entry_meta";
    $postmeta = $wpdb->prefix."postmeta";
    $form_id='';
    $entry_id='';
    $calculate_price=0;
    $current_user = wp_get_current_user();
    $extraData=array();
    $tablename = $wpdb->prefix."forminator_track";
    $entry = $wpdb->get_row("SELECT * FROM ".$tablename." where user_id='".get_current_user_id()."' order by id limit 1");
    //check current user
    if(isset($entry->form_id) && $entry->form_id){
      $form_id=$entry->form_id;
      $entry_id=$entry->entry_id;
      
    }
    else{
      die('No record found');
    }
    
    
    //query
      $form_entry_meta_list = $wpdb->get_results("SELECT * FROM ".$frmt_form_entry_meta." where entry_id='".$entry_id."'");
      $postmeta_list = $wpdb->get_results("SELECT * FROM ".$postmeta." where post_id ='".$form_id."'");
      $postmeta_list_result=array();
      $form_entry_meta_list_result=array();
      //question
      if(count($postmeta_list) > 0){
        $postmeta_list=unserialize($postmeta_list[0]->meta_value)['fields'];
        foreach($postmeta_list as $val){
          $postmeta_list_result[$val['element_id']]=$val;
       }
     }
    //answer
     if(count($form_entry_meta_list) > 0){
      $form_entry_meta_list=json_decode(json_encode($form_entry_meta_list), true);
      foreach($form_entry_meta_list as $val){
        $form_entry_meta_list_result[$val['meta_key']]=$val;
     }
    
    }
?>

<?php
//final result
    $total_calculation_value=0;


    if(count($postmeta_list_result) > 0){
      $count = 1;
      foreach($postmeta_list_result as $key=>$val){
    
        if (strpos($key, 'radio-') !== false) {
          $question=$val['field_label'];
          $calculation=str_replace('radio-','calculation-',$key);
          $calculation_value='';
          if(isset($form_entry_meta_list_result[$calculation])){
             $calculation_value=unserialize($form_entry_meta_list_result[$calculation]['meta_value'])['result'];
             $total_calculation_value=$calculation_value;
            }
          $count++;
        }
    
         //extra Data
         if (strpos($key, 'calculation-') !== false) {
          if(isset($val['field_label']) && $val['field_label']){
            $label=$val['field_label'];
            $suffix=$val['suffix'];
            $calculation_value='';
            if(isset($form_entry_meta_list_result[$key])){
               $calculation_value=unserialize($form_entry_meta_list_result[$key]['meta_value'])['result'];
              }
              $extraData[]=array(
                  "label"=>$label,
                  "suffix"=>$suffix,
                  "calculation_value"=>$calculation_value
              );
    
          }
        }
        
        
    
     }
     
    }
    

//echo do_shortcode('[RZP]')
?>
<?php  
    $Final='';
    $Travel='';
    $Travel_Analogy='';
    $Food='';
    $Food_Analogy='';
    $Utilities='';
    $Utilities_Analogy='';
    
   /*  echo '<pre>';
    print_r($form_entry_meta_list_result);
    print_r($extraData); */
    foreach($extraData as $val){
      
      if(stristr($val['label'],'Final')){
        $Final=$val;
      }
      if(trim($val['label'])=='Travel'){
        $Travel=$val;
      }
      if(trim($val['label'])=='Food'){
        $Food=$val;
      }
      if(trim($val['label'])=='Utilities'){
        $Utilities=$val;
      }
      if(trim($val['label'])=='Travel Analogy'){
        $Travel_Analogy=$val;
      }
      if(trim($val['label'])=='Food Analogy'){
        $Food_Analogy=$val;
      }
      if(trim($val['label'])=='Utilities Analogy'){
        $Utilities_Analogy=$val;
      }
    }
    
?>


<!-- Update $current_carbon_footprint in DB -->
<?php
    //$table_name='wp_z8_users';
    $tablename = $wpdb->prefix."users";
    $current_carbon_footprint=$Final['calculation_value'];
    $user_entry = $wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".get_current_user_id()." LIMIT 1");
    
    $user_registered_date=$user_entry->user_registered;
    $current_date=date("Y");
    
    $registration_year=substr($user_registered_date,0,4);
    
    // echo $registration_year; echo $current_date;
    
    $total_footprint_since_registration=0;
    if($current_date-$registration_year>=2){
        // echo "ok";
    }else{
        // echo $current_date-$registration_year;
        $total_footprint_since_registration=$user_entry->initial_carbon_footprint;
    }
    
    //User has never stored his/her carbon_footprints in db
    if($user_entry->current_carbon_footprint==NULL){
        $wpdb->update(
                        $tablename,      //table_name
                        [
                            "current_carbon_footprint"=>$Final['calculation_value'],
                            "initial_carbon_footprint"=>$Final['calculation_value']
                        ],  //data to update
                        ["ID"=>get_current_user_id()]  //Where conditions
                      );   
    }
    // Get the data from DB
    else{
        $Final['calculation_value']=$user_entry->current_carbon_footprint;
        $total_calculation_value=$user_entry->current_carbon_footprint;
        
    }
    // echo $user_entry->current_carbon_footprint;
?>

<!-- payment -->
<?php

    require('razorpay/config.php');
    require('razorpay/razorpay-php/Razorpay.php');
    
    // Create the Razorpay Order
    
    use Razorpay\Api\Api;
    
    $api = new Api($keyId, $keySecret);
    
    //
    // We create an razorpay order using orders api
    // Docs: https://docs.razorpay.com/docs/orders
    //
    if($total_calculation_value < 1 )
      $price = $total_calculation_value*10;
    else
      $price = $total_calculation_value*.50;
      
    // Store the price in session for further use 
    $_SESSION['price'] = $price;
    // echo $_SESSION['price'];
    
    $customername = (isset($current_user->user_nicename))?$current_user->user_nicename:'';
    $email = (isset($current_user->user_email))?$current_user->user_email:'';
    $_SESSION['email'] = $email;
    $contactno ='';
    $orderData = [
        //'receipt'         => 3456,
        'amount'          => 200, // 2000 rupees in paise
        'currency'        => 'INR',
        'payment_capture' => 1 // auto capture
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    
    $razorpayOrderId = $razorpayOrder['id'];
    
    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
    
    $displayAmount = $amount = $orderData['amount'];
    
    if ($displayCurrency !== 'INR')
    {
        $url = "https://api.fixer.io/latest?symbols=$displayCurrency&base=INR";
        $exchange = json_decode(file_get_contents($url), true);
    
        $displayAmount = $exchange['rates'][$displayCurrency] * $amount / 100;
    }
    
    $data = [
        "key"               => $keyId,
        "amount"            => $amount,
        "name"              => "lowsoot",
        "description"       => "",
        "image"             => "",
        "prefill"           => [
        "name"              => $customername,
        "email"             => $email,
        "contact"           => $contactno,
        ],
        "notes"             => [
        "address"           => "",
        "merchant_order_id" => "",
        ],
        "theme"             => [
        "color"             => "#F37254"
        ],
        "order_id"          => $razorpayOrderId,
    ];
    
    if ($displayCurrency !== 'INR')
    {
        $data['display_currency']  = $displayCurrency;
        $data['display_amount']    = $displayAmount;
    }
    
    $json = json_encode($data);

?>



<!-- <button id="rzp-button1">Pay Now</button> -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<form name='razorpayform' action="razorpay/verify.php" method="POST">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature"  id="razorpay_signature" >
</form>

      
 
 <!DOCTYPE html>
<html style="font-size: 16px;">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <meta name="keywords" content="​One-click solution for your static? website., ​Hosting solution with benefits., What Clients Say, ​Purchase, ​Free, ​$9/month, ​$12/month, ​Get started with the simpliest static page">
    <meta name="description" content="">
    <meta name="page_type" content="np-template-header-footer-from-plugin">
    <title>Home</title>
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/UserProfile/jquery.js" defer=""></script>
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/UserProfile/nicepage.js" defer=""></script>
    <script src="https://kit.fontawesome.com/yourcode.js" crossorigin="anonymous"></script>
    
    <meta name="generator" content="lowsoot.com">
    
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/nicepage2.css"/>
    <!--<link rel="stylesheet" href="<?php //echo get_template_directory_uri(); ?>/Home1.css"/>-->
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css-circular-prog-bar.css"/>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/Home-Responsive.css"/>
    
    
    <style>
        .no-js #loader { display: none;  }
        .js #loader { display: block; position: absolute; left: 100px; top: 0; }
        .se-pre-con {
        	position: fixed;
        	left: 0px;
        	top: 0px;
        	width: 100%;
        	height: 100%;
        	z-index: 9999;
        	background: url(https://smallenvelop.com/wp-content/uploads/2014/08/Preloader_11.gif) center no-repeat #fff;
        }
        body{
            font-family: 'Sora'!important;
            font-weight:600!important;
        }
        .progressbar-container{
            text-align:center;
        }
        progress{
            height:3rem;
            width:17rem;
        }
        
        .progress-circle-custom-styles{
            margin:26px auto;
            background-color: #c8dbc9; 
        }
        .value-bar-custom-styles{
            /*border: 0.45em solid #e39612;*/
            border: 0.45em solid #05f219;
        }
        
        .telegram-button{
            margin:0 auto;
        }
        
        .icon-style{
            margin-right:19px;
        }
        .custom-u-text-4{
            margin:120px 216px 0 297px;
        }
        
        .display-flex{
            display: flex;
            flex-direction: column-reverse;
        }
        
        
        /*Header styles*/
        .custom-header-styles{
            max-height:4.5rem;
            color:#fff;
            background-color:#2D2D2D;
        }
        
        .header-element{
            margin-bottom: 5px;
        }
        
        
        @media only screen and (max-width: 768px) {
          /* For mobile phones: */
          .progressbar-container {
            margin-top:8.4rem;
          }
          
          .telegram-button{
              margin: 4.4rem auto 0;
          }
        }
        
        @media screen and (min-width: 558px) and (min-width: 750px){
             .telegram-button{
                margin: 0 auto;
            }
        }
        
        .custom-footer{
            background-color: #3E3E3E;
        }
    </style>
    
    
    
    
    
    
    <script type="application/ld+json">{
		"@context": "http://schema.org",
		"@type": "Organization",
		"name": "",
		"logo": "images/lowsoot.png",
		"sameAs": [
				"https://facebook.com/name",
				"https://twitter.com/name",
				"https://instagram.com/name"
		]
}</script>

    <meta name="theme-color" content="#4861df">
    <meta name="twitter:site" content="@">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Home">
    <meta name="twitter:description" content="">
    <meta property="og:title" content="Home">
    <meta property="og:type" content="website">
  </head>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.js"></script>
    <script>
        //paste this code under the head tag or in a separate js file.
    	// Wait for window load
    	$(window).load(function() {
    		// Animate loader off screen
    		$(".se-pre-con").fadeOut(4000);;
    	});
    </script>
    <div class="se-pre-con"></div>
  <body data-home-page="Home.html" data-home-page-title="Home" class="u-body">
    <!--Header-->
    <header class="u-align-center-sm u-align-center-xs u-clearfix u-custom-color-3 u-header u-sticky u-sticky-46cd u-header" id="sec-ef8f"><div class="u-clearfix u-sheet u-sheet-1">
        <a href="lowsoot.com" class="u-image u-logo u-image-1" data-image-width="1350" data-image-height="631" title="lowsoot">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/newlogo1.png" class="u-logo-image u-logo-image-1">
        </a>
        <div class="u-hidden-md u-hidden-sm u-hidden-xs u-social-icons u-spacing-27 u-social-icons-1">
          <a class="u-social-url" title="facebook" target="_blank" href=""><span class="u-icon u-social-facebook u-social-icon u-text-grey-25 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-20f2"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" id="svg-20f2"><path d="m512 256c0-141.4-114.6-256-256-256s-256 114.6-256 256 114.6 256 256 256c1.5 0 3 0 4.5-.1v-199.2h-55v-64.1h55v-47.2c0-54.7 33.4-84.5 82.2-84.5 23.4 0 43.5 1.7 49.3 2.5v57.2h-33.6c-26.5 0-31.7 12.6-31.7 31.1v40.8h63.5l-8.3 64.1h-55.2v189.5c107-30.7 185.3-129.2 185.3-246.1z"></path></svg></span>
          </a>
          <a class="u-social-url" title="twitter" target="_blank" href=""><span class="u-icon u-social-icon u-social-twitter u-text-grey-25 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 24 24" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-9227"></use></svg><svg class="u-svg-content" viewBox="0 0 24 24" id="svg-9227"><path d="m12.004 5.838c-3.403 0-6.158 2.758-6.158 6.158 0 3.403 2.758 6.158 6.158 6.158 3.403 0 6.158-2.758 6.158-6.158 0-3.403-2.758-6.158-6.158-6.158zm0 10.155c-2.209 0-3.997-1.789-3.997-3.997s1.789-3.997 3.997-3.997 3.997 1.789 3.997 3.997c.001 2.208-1.788 3.997-3.997 3.997z"></path><path d="m16.948.076c-2.208-.103-7.677-.098-9.887 0-1.942.091-3.655.56-5.036 1.941-2.308 2.308-2.013 5.418-2.013 9.979 0 4.668-.26 7.706 2.013 9.979 2.317 2.316 5.472 2.013 9.979 2.013 4.624 0 6.22.003 7.855-.63 2.223-.863 3.901-2.85 4.065-6.419.104-2.209.098-7.677 0-9.887-.198-4.213-2.459-6.768-6.976-6.976zm3.495 20.372c-1.513 1.513-3.612 1.378-8.468 1.378-5 0-7.005.074-8.468-1.393-1.685-1.677-1.38-4.37-1.38-8.453 0-5.525-.567-9.504 4.978-9.788 1.274-.045 1.649-.06 4.856-.06l.045.03c5.329 0 9.51-.558 9.761 4.986.057 1.265.07 1.645.07 4.847-.001 4.942.093 6.959-1.394 8.453z"></path><circle cx="18.406" cy="5.595" r="1.439"></circle></svg></span>
          </a>
          <a class="u-social-url" title="instagram" target="_blank" href=""><span class="u-icon u-social-icon u-social-instagram u-text-grey-25 u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 24 24" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-984a"></use></svg><svg class="u-svg-content" viewBox="0 0 24 24" id="svg-984a"><path d="m23.469 5.929.03.196c-.29-1.029-1.073-1.823-2.068-2.112l-.021-.005c-1.871-.508-9.4-.508-9.4-.508s-7.51-.01-9.4.508c-1.014.294-1.798 1.088-2.083 2.096l-.005.021c-.699 3.651-.704 8.038.031 11.947l-.031-.198c.29 1.029 1.073 1.823 2.068 2.112l.021.005c1.869.509 9.4.509 9.4.509s7.509 0 9.4-.509c1.015-.294 1.799-1.088 2.084-2.096l.005-.021c.318-1.698.5-3.652.5-5.648 0-.073 0-.147-.001-.221.001-.068.001-.149.001-.23 0-1.997-.182-3.951-.531-5.846zm-13.861 9.722v-7.293l6.266 3.652z"></path></svg></span>
          </a>
        </div>
        <nav class="u-align-left u-menu u-menu-dropdown u-offcanvas u-menu-1">
          <div class="menu-collapse" style="font-size: 0.875rem; letter-spacing: 0px; font-weight: 700;">
            <a class="u-button-style u-custom-active-border-color u-custom-active-color u-custom-border u-custom-border-color u-custom-borders u-custom-hover-border-color u-custom-hover-color u-custom-left-right-menu-spacing u-custom-padding-bottom u-custom-text-active-color u-custom-text-color u-custom-text-hover-color u-custom-top-bottom-menu-spacing u-nav-link" href="#" style="padding: 6px 10px; font-size: calc(1em + 12px);">
              <svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 302 302" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-8a8f"></use></svg>
              <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="svg-8a8f" x="0px" y="0px" viewBox="0 0 302 302" style="enable-background:new 0 0 302 302;" xml:space="preserve" class="u-svg-content"><g><rect y="36" width="302" height="30"></rect><rect y="236" width="302" height="30"></rect><rect y="136" width="302" height="30"></rect>
</g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>
            </a>
          </div>
          <div class="u-custom-menu u-nav-container">
            <ul class="u-nav u-spacing-20 u-unstyled u-nav-1"><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="https://lowsoot.com" style="padding: 8px 22px;">Home</a>
              </li><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="https://lowsoot.com/about/" style="padding: 8px 22px;">About</a>
              </li><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="#" style="padding: 8px 22px;">Projects</a><div class="u-nav-popup"><ul class="u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-2"><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/project-1/">Eco Villages</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/project-4/">Rejuvenate Lakes</a>
              </li>
              <li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/mangrove-forests/">Mangrove Forest</a>
              </li>
              <li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/climate-friendly/">Climate Friendly Tech</a>
              </li></ul>
              </div>
              <li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="#" style="padding: 8px 22px;">Crowdfunding</a><div class="u-nav-popup"><ul class="u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-2"><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/project-1/">Smokeless Cookstoves</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/project-4/">First Eco Village</a>
              </li>
              <li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="/mangrove-forests/">Fund a Mangrove Tree</a>
              </li>
              </ul>
              </div>
              </li>
              </li><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="profile" style="padding: 8px 22px;border-radius: 6px; padding: 8px 28px;
    background: #fff; color:#000!important; ">Account</a><div class="u-nav-popup"><ul class="u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-3"><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="https://lowsoot.staging.tempurl.host/profile-2/#Footprint">Dashboard</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="https://lowsoot.com/login/">Logout</a>
              </li></ul>
              </div>
              </li></ul>
                        </div>
                        <div class="u-custom-menu u-nav-container-collapse">
                          <div class="u-align-center u-black u-container-style u-inner-container-layout u-opacity u-opacity-95 u-sidenav">
                            <div class="u-sidenav-overflow">
                              <div class="u-menu-close"></div>
                              <ul class="u-align-center u-nav u-popupmenu-items u-unstyled u-nav-4"><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com" style="padding: 8px 22px;">Home</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/about/" style="padding: 8px 22px;">About</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#" style="padding: 8px 22px;">Projects</a><div class="u-nav-popup"><ul class="u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-5"><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/project-1/">Large Scale Forestation</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/project-2-community-tree-plantation/">Community Plantation</a>
              </li></ul>
              </div>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="profile" style="padding: 8px 22px;">Account</a><div class="u-nav-popup"><ul class="u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-6"><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/profile#footprint">My Carbon Footprint</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#erase">Erase My Footprint</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#settings">Settings</a>
              </li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#">Logout</a>
              </li></ul>
              </div>
              </li></ul>
                </div>
              </div>
              <div class="u-black u-menu-overlay u-opacity u-opacity-70"></div>
            </div>
          </nav>
        </div><style class="u-sticky-style" data-style-id="46cd">.u-sticky-fixed.u-sticky-46cd:before, .u-body.u-sticky-fixed .u-sticky-46cd:before {
  borders: top right bottom left !important
  }</style>

  </header> 
  
    <!--Your Annual Carbon Footprint-->
    <section class="u-align-right u-clearfix u-white u-section-1" id="Footprint">
      <div class="u-clearfix u-sheet u-valign-top u-sheet-1">
        <div class="u-container-style u-expanded-width u-group u-shape-rectangle u-group-1">
          <div class="u-container-layout u-container-layout-1">
               <p class="u-align-center u-text u-text-1">Your Annual Carbon Footprint</p>
           
            
            <!-- Progess Bar  -->
            <?php
                $tablename = $wpdb->prefix."users";
                $current_user_details = $wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".get_current_user_id()." LIMIT 1");
                // echo $current_user_details->initial_carbon_footprint;
                
                $initial_carbon_footprint=(int)$current_user_details->initial_carbon_footprint;
                $current_carbon_footprint=(int)$current_user_details->current_carbon_footprint;
                // echo "i".$initial_carbon_footprint;
                // echo "c".$current_carbon_footprint;
                
                $offset_carbon_footprint=$initial_carbon_footprint-$current_carbon_footprint;
                // echo $offset_carbon_footprint;
                $current_offset_percentage=($offset_carbon_footprint/$initial_carbon_footprint)*100;
                // echo $current_offset_percentage;
                
            ?>
            <div class="progressbar-container">
                <p style="margin-top: 37px;margin-bottom: 37px; color:#53777A!important;">You have removed <?php echo round($current_offset_percentage); ?>% of your carbon footprint</p>
                
                <div class="progress-circle-custom-styles progress-circle p<?php echo round($current_offset_percentage); ?>">
                   <span><?php echo round($current_offset_percentage); ?>%</span>
                   <div class="left-half-clipper">
                      <div class="first50-bar"></div>
                      <div class="value-bar value-bar-custom-styles"></div>
                   </div>
                </div>
            </div>
            
            
            <!--! Progress Bar-->
            
             
            <h1 class="u-align-center u-text u-text-custom-color-4 u-text-2" style="margin:10px 0 0;"><?php echo $Final['calculation_value']??'' ?> KGs</h1>
           
            <p class="u-align-center u-text u-text-grey-40 u-text-3" style="margin: 65px auto 0;"><b>Your footprint is <?php echo (round($Final['calculation_value']/1600))??'' ?> times the carbon footprint of an average indian and&nbsp;<b>equal to cutting <?php echo (round($Final['calculation_value']/22))??'' ?> trees each year.</b>&nbsp;</b>
            </p>
           
            <a href="#Erase" class="u-border-1 u-border-palette-2-dark-1 u-btn u-btn-round u-button-style u-palette-2-light-1 u-radius-3 u-text-palette-2-dark-2 u-btn-1">
                Erase your&nbsp;<br>Footprint&nbsp;<span class="u-icon u-icon-1"><svg class="u-svg-content" viewBox="0 0 24 24" style="width: 1em; height: 1em;"><path d="m12 1c-6.065 0-11 4.935-11 11s4.935 11 11 11 11-4.935 11-11-4.935-11-11-11zm3.25 8.5c.414 0 .75.336.75.75s-.336.75-.75.75h-1.172c-.407 1.248-1.538 2.173-2.903 2.246l3.498 2.385c.342.233.43.7.197 1.042-.145.212-.381.327-.621.327-.146 0-.292-.042-.422-.13l-5.5-3.75c-.271-.186-.391-.526-.294-.841s.388-.529.717-.529h2.25c.593 0 1.116-.298 1.432-.75h-3.682c-.414 0-.75-.336-.75-.75s.336-.75.75-.75h3.918c-.216-.72-.878-1.25-1.668-1.25h-2.25c-.414 0-.75-.336-.75-.75s.336-.75.75-.75h2.25 4.25c.414 0 .75.336.75.75s-.336.75-.75.75h-1.52c.239.372.398.796.469 1.25z"></path></svg><img></span>
            </a>
           
            <a href="https://t.me/joinchat/Y9G67QVO2ME5Y2Fl" class="u-border-1 u-border-custom-color-6 u-btn u-btn-round u-button-style u-custom-color-5 u-radius-3 u-text-custom-color-6 u-btn-2">Join the&nbsp;<br>Community&nbsp;<span class="u-icon u-icon-2"><svg class="u-svg-content" viewBox="0 0 24 24" style="width: 1em; height: 1em;"><path d="m12 24c6.629 0 12-5.371 12-12s-5.371-12-12-12-12 5.371-12 12 5.371 12 12 12zm-6.509-12.26 11.57-4.461c.537-.194 1.006.131.832.943l.001-.001-1.97 9.281c-.146.658-.537.818-1.084.508l-3-2.211-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953z"></path></svg><img></span>
            </a>
            <a href="#footprint-breakdown" class="u-border-1 u-border-grey-80 u-btn u-btn-round u-button-style u-custom-color-4 u-radius-3 u-text-grey-80 u-btn-3">Understand your<br>Footprint&nbsp;<span class="u-icon u-icon-3"><svg class="u-svg-content" viewBox="0 0 24 24" style="width: 1em; height: 1em;"><path d="m12 0c-6.617 0-12 5.383-12 12s5.383 12 12 12 12-5.383 12-12-5.383-12-12-12zm0 19c-.552 0-1-.448-1-1s.448-1 1-1 1 .448 1 1-.448 1-1 1zm1.583-6.358c-.354.163-.583.52-.583.909v.449c0 .552-.447 1-1 1s-1-.448-1-1v-.449c0-1.167.686-2.237 1.745-2.726 1.019-.469 1.755-1.714 1.755-2.325 0-1.378-1.121-2.5-2.5-2.5s-2.5 1.122-2.5 2.5c0 .552-.447 1-1 1s-1-.448-1-1c0-2.481 2.019-4.5 4.5-4.5s4.5 2.019 4.5 4.5c0 1.351-1.172 3.337-2.917 4.142z"></path></svg><img></span>
            </a>
            
            
            <h3 class="u-text u-text-grey-40 u-text-4">Breakdown of your carbon footprint</h3>
            <div class="u-list u-list-1" id="footprint-breakdown">
              <div class="u-repeater u-repeater-1">
                <div class="u-align-center u-container-style u-grey-5 u-list-item u-radius-6 u-repeater-item u-shape-round u-list-item-1">
                  <div class="u-container-layout u-similar-container u-container-layout-2">
                    <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/assets/images/sports-car.png" alt="" data-image-width="512" data-image-height="512">
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-5">Your Travel Emissions</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-custom-color-4 u-text-default u-text-font u-text-6"><?php echo $Travel['calculation_value']??'' ?> KGs</h6>
                  </div>
                </div>
                <div class="u-align-center u-container-style u-grey-5 u-list-item u-radius-6 u-repeater-item u-shape-round u-list-item-2">
                  <div class="u-container-layout u-similar-container u-container-layout-3">
                    <img class="u-image u-image-default u-image-2" src="<?php echo get_template_directory_uri(); ?>/assets/images/restaurant.png" alt="" data-image-width="512" data-image-height="512">
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-7"> Your Food&nbsp; Emissions</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-custom-color-4 u-text-default u-text-font u-text-8"> <?php echo $Food['calculation_value']??'' ?> KGs</h6>
                  </div>
                </div>
                <div class="u-align-center u-container-style u-grey-5 u-list-item u-radius-6 u-repeater-item u-shape-round u-list-item-3">
                  <div class="u-container-layout u-similar-container u-container-layout-4">
                    <img class="u-image u-image-default u-image-3" src="<?php echo get_template_directory_uri(); ?>/assets/images/rounded-plug.png" alt="" data-image-width="512" data-image-height="512">
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-9">Your Utility Emissions</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-custom-color-4 u-text-default u-text-font u-text-10"> <?php echo $Utilities['calculation_value']??'' ?> KGs</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="u-clearfix u-layout-wrap u-layout-wrap-1">
          <div class="u-layout">
            <div class="u-layout-row">
              <div class="u-container-style u-layout-cell u-size-15 u-size-30-md u-layout-cell-1">
                <div class="u-container-layout u-container-layout-5">
                  <img class="u-image u-image-default u-image-4" src="<?php echo get_template_directory_uri(); ?>/assets/images/UN.png" alt="" data-image-width="450" data-image-height="169">
                </div>
              </div>
              <div class="u-container-style u-layout-cell u-size-15 u-size-30-md u-layout-cell-2">
                <div class="u-container-layout u-valign-bottom-xs u-container-layout-6">
                  <img class="u-image u-image-default u-image-5" src="<?php echo get_template_directory_uri(); ?>/assets/images/goldstan.png" alt="" data-image-width="450" data-image-height="169">
                </div>
              </div>
              <div class="u-container-style u-layout-cell u-size-15 u-size-30-md u-layout-cell-3">
                <div class="u-container-layout u-valign-bottom-xs u-valign-middle-lg u-valign-middle-xl u-container-layout-7">
                  <img class="u-image u-image-default u-image-6" src="<?php echo get_template_directory_uri(); ?>/assets/images/red.png" alt="" data-image-width="450" data-image-height="169">
                </div>
              </div>
              <div class="u-container-style u-layout-cell u-size-15 u-size-30-md u-layout-cell-4">
                <div class="u-container-layout u-valign-top u-container-layout-8"></div>
              </div>
            </div>
          </div>
        </div>
        <img class="u-image u-image-default u-image-7" src="<?php echo get_template_directory_uri(); ?>/assets/images/verifiedman.png" alt="" data-image-width="450" data-image-height="169">
      </div>
    <section class="u-clearfix u-grey-5 u-section-2" id="sec-55ac">
      <div class="u-clearfix u-sheet u-sheet-1">
        <div class="u-clearfix u-expanded-width u-layout-wrap u-layout-wrap-1">
          <div class="u-layout">
            <div class="u-layout-row">
              <div class="u-align-left u-container-style u-image u-layout-cell u-shading u-size-24 u-image-1" data-image-width="1276" data-image-height="1280">
                <div class="u-container-layout u-container-layout-1">
                  <h4 class="u-custom-font u-font-raleway u-text u-text-1">How is my Annual Carbon Footprint Calculated?</h4>
                </div>
              </div>
              <div class="u-align-left u-container-style u-grey-10 u-layout-cell u-shape-rectangle u-size-36 u-layout-cell-2">
                <div class="u-container-layout u-container-layout-2">
                  <p class="u-text u-text-2"> Provided with the choice of options, each answer is assigned a certain emission based on the number obtained from our carbon standards.</p>
                  <p class="u-text u-text-3">Our Calculation Standards are based on&nbsp;&nbsp;<a href="https://www.freepik.com/psd/mockup" class="u-border-2 u-border-custom-color-1 u-btn u-button-link u-button-style u-none u-text-custom-color-1 u-btn-1">These Research Journals&nbsp;</a>
                  </p>
                  <a href="#Erase" class="u-active-black u-border-none u-btn u-btn-round u-button-style u-custom-color-4 u-hover-black u-radius-3 u-btn-2">Erase NOw</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-clearfix u-grey-5 u-section-3" id="carousel_d47a">
      <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
        <div class="u-container-style u-expanded-width u-grey-5 u-group u-shape-rectangle u-group-1">
          <div class="u-container-layout u-container-layout-1"><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 409.294 409.294" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-5c4d"></use></svg><svg class="u-svg-content" viewBox="0 0 409.294 409.294" id="svg-5c4d"><path d="m233.882 29.235v175.412h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412z"></path><path d="m0 204.647h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412h-175.412z"></path></svg></span>
            <h4 class="u-custom-font u-font-playfair-display u-text u-text-1"> The question is, are we happy to suppose that our grandchildren may never be able to see an elephant except in a picture book?<br>
              <br>-&nbsp;<span style="font-weight: 700; font-size: 1.5rem;">David Attenborough</span>
            </h4><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 409.294 409.294" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-0171"></use></svg><svg class="u-svg-content" viewBox="0 0 409.294 409.294" id="svg-0171"><path d="m233.882 29.235v175.412h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412z"></path><path d="m0 204.647h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412h-175.412z"></path></svg></span>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-center u-clearfix u-section-4" id="questions">
      <div class="u-clearfix u-sheet u-valign-middle-lg u-valign-middle-md u-valign-middle-sm u-valign-middle-xl u-sheet-1">
        <h1 class="u-align-center u-custom-font u-font-titillium-web u-text u-text-custom-color-1 u-text-1">How Bad Is The Situation?</h1>
        <p class="u-align-center u-text u-text-grey-40 u-text-2"> The IPCC report says the situation is worse than critical. It's a warning to everyone to change their ways or else expect..</p>
        <div class="u-expanded-width-md u-expanded-width-xs u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-container-style u-custom-background u-custom-color-1 u-list-item u-radius-5 u-repeater-item u-shape-round u-list-item-1">
              <div class="u-container-layout u-similar-container u-container-layout-1">
                <h6 class="u-text u-text-default u-text-3"> Sea Level Rise</h6>
                <p class="u-text u-text-white u-text-4"> Global sea level rose about 8 inches (20 centimeters) in the last century</p>
              </div>
            </div>
            <div class="u-container-style u-custom-color-1 u-list-item u-radius-5 u-repeater-item u-shape-round u-list-item-2">
              <div class="u-container-layout u-similar-container u-container-layout-2">
                <h6 class="u-text u-text-default u-text-5"> Extreme Events</h6>
                <p class="u-text u-text-white u-text-6"> The number of record high temperature events in the United States has been increasing</p>
              </div>
            </div>
            <div class="u-container-style u-custom-color-1 u-list-item u-radius-5 u-repeater-item u-shape-round u-list-item-3">
              <div class="u-container-layout u-similar-container u-container-layout-3">
                <h6 class="u-text u-text-default u-text-7"> Glacial Retreat</h6>
                <p class="u-text u-text-white u-text-8"> Glaciers are retreating almost everywhere around the world — including in the Alps, Himalayas, Andes, Rockies, Alaska, and Africa</p>
              </div>
            </div>
            <div class="u-container-style u-custom-background u-custom-color-1 u-list-item u-radius-5 u-repeater-item u-shape-round u-list-item-4">
              <div class="u-container-layout u-similar-container u-container-layout-4">
                <h6 class="u-text u-text-default u-text-9"> Ocean Acidification</h6>
                <p class="u-text u-text-white u-text-10"> the acidity of surface ocean waters has increased by about 30%</p>
              </div>
            </div>
          </div>
        </div>
        <img class="u-expanded-width-md u-expanded-width-sm u-expanded-width-xs u-image u-image-round u-radius-5 u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/UserProfile/images/3278eed2f001c45bdc29add23f0d0741e375c94cc8a9f360119e5be5025dc8052bd7b27c22887053f8a7de1e746ef103609440e7bf41cf838180aa_1280.jpg" data-image-width="1280" data-image-height="829">
      </div>
    </section>
    <section class="u-align-left u-clearfix u-grey-5 u-section-5" id="carousel_763d">
      <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
        <h1 class="u-align-center u-custom-font u-font-titillium-web u-text u-text-grey-60 u-text-1">How Does this even Work?</h1>
        <h6 class="u-align-center u-custom-font u-font-montserrat u-text u-text-2"> We will make sure your carbon footprint is erased and at the same time, we'll help you track your investment</h6>
        <div class="u-expanded-width u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-align-center u-container-style u-custom-background u-list-item u-radius-5 u-repeater-item u-shape-round u-white u-list-item-1">
              <div class="u-container-layout u-similar-container u-container-layout-1"><span class="u-align-center u-icon u-icon-rectangle u-text-custom-color-1 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512.019 512.019" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-3bb7"></use></svg><svg class="u-svg-content" viewBox="0 0 512.019 512.019" id="svg-3bb7"><g><path d="m296.01 0c-78.851 0-143 64.149-143 143s64.149 143 143 143 143-64.149 143-143-64.15-143-143-143zm15.019 208.011v3.139c0 8.284-6.716 15-15 15s-15-6.716-15-15v-1.443c-6.916-1.211-13.231-3.797-21.205-9.016-6.932-4.537-8.873-13.834-4.336-20.766 4.538-6.933 13.833-8.872 20.766-4.336 7.3 4.778 9.673 5.145 19.675 5.081 9.648-.063 13.493-7.633 14.244-12.097.675-4.01-.081-9.342-7.517-11.973-11.416-4.04-24.862-9.169-34.272-16.547-20.657-16.196-14.317-53.078 12.646-62.884v-2.169c0-8.284 6.716-15 15-15s15 6.716 15 15v1.234c9.938 2.929 16.888 9.116 16.888 9.116 6.053 5.604 6.451 15.05.872 21.139-5.586 6.096-15.047 6.519-21.156.956-3.87-2.866-9.769-4.103-15.982-2.22-4.024 1.213-5.098 5.359-5.332 6.601-.486 2.587.206 4.329.576 4.619 6.106 4.789 18.294 9.229 25.769 11.874 39.526 13.983 34.704 66.558-1.636 79.692z"></path><path d="m433.58 292.15-145.65 42.9c4.61 11.28 7.08 23.56 7.08 36.27v5.27c0 37.371-38.608 62.232-72.6 46.94l-76.35-34.36c-7.55-3.4-10.92-12.28-7.52-19.83 3.4-7.56 12.28-10.93 19.83-7.53l76.35 34.36c14.112 6.358 30.29-3.861 30.29-19.58v-5.27c0-25.776-15.612-48.545-38.697-58.933l-65.973-29.687c-25.68-11.56-55.08-11.42-80.57.35l-70.97 32.3c-5.35 2.43-8.79 7.77-8.79 13.65v159.618c0 11.281 10.769 18.035 20.34 14.402l99.88-38.05 118.8 41.58c27.009 9.451 56.679 6.588 81.42-8.08l161.61-95.17c18.47-10.95 29.95-31.09 29.95-52.57 0-40.648-39.056-70.185-78.43-58.58z"></path>
</g></svg></span>
                <h5 class="u-align-center u-text u-text-custom-color-1 u-text-3"> Money Goes to Lowsoot Climate FunD</h5>
              </div>
            </div>
            <div class="u-align-center u-container-style u-list-item u-radius-5 u-repeater-item u-shape-round u-white u-list-item-2">
              <div class="u-container-layout u-similar-container u-container-layout-2"><span class="u-align-center u-icon u-icon-rectangle u-text-custom-color-1 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="-42 0 512 512.002" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-6bad"></use></svg><svg class="u-svg-content" viewBox="-42 0 512 512.002" id="svg-6bad"><path d="m210.351562 246.632812c33.882813 0 63.222657-12.152343 87.195313-36.128906 23.972656-23.972656 36.125-53.304687 36.125-87.191406 0-33.875-12.152344-63.210938-36.128906-87.191406-23.976563-23.96875-53.3125-36.121094-87.191407-36.121094-33.886718 0-63.21875 12.152344-87.191406 36.125s-36.128906 53.308594-36.128906 87.1875c0 33.886719 12.15625 63.222656 36.132812 87.195312 23.976563 23.96875 53.3125 36.125 87.1875 36.125zm0 0"></path><path d="m426.128906 393.703125c-.691406-9.976563-2.089844-20.859375-4.148437-32.351563-2.078125-11.578124-4.753907-22.523437-7.957031-32.527343-3.308594-10.339844-7.808594-20.550781-13.371094-30.335938-5.773438-10.15625-12.554688-19-20.164063-26.277343-7.957031-7.613282-17.699219-13.734376-28.964843-18.199219-11.226563-4.441407-23.667969-6.691407-36.976563-6.691407-5.226563 0-10.28125 2.144532-20.042969 8.5-6.007812 3.917969-13.035156 8.449219-20.878906 13.460938-6.707031 4.273438-15.792969 8.277344-27.015625 11.902344-10.949219 3.542968-22.066406 5.339844-33.039063 5.339844-10.972656 0-22.085937-1.796876-33.046874-5.339844-11.210938-3.621094-20.296876-7.625-26.996094-11.898438-7.769532-4.964844-14.800782-9.496094-20.898438-13.46875-9.75-6.355468-14.808594-8.5-20.035156-8.5-13.3125 0-25.75 2.253906-36.972656 6.699219-11.257813 4.457031-21.003906 10.578125-28.96875 18.199219-7.605469 7.28125-14.390625 16.121094-20.15625 26.273437-5.558594 9.785157-10.058594 19.992188-13.371094 30.339844-3.199219 10.003906-5.875 20.945313-7.953125 32.523437-2.058594 11.476563-3.457031 22.363282-4.148437 32.363282-.679688 9.796875-1.023438 19.964844-1.023438 30.234375 0 26.726562 8.496094 48.363281 25.25 64.320312 16.546875 15.746094 38.441406 23.734375 65.066406 23.734375h246.53125c26.625 0 48.511719-7.984375 65.0625-23.734375 16.757813-15.945312 25.253906-37.585937 25.253906-64.324219-.003906-10.316406-.351562-20.492187-1.035156-30.242187zm0 0"></path></svg></span>
                <h5 class="u-align-center u-text u-text-custom-color-1 u-text-4"> Your contributionS Are Calculated &amp; Stored</h5>
              </div>
            </div>
            <div class="u-align-center u-container-style u-list-item u-radius-5 u-repeater-item u-shape-round u-white u-list-item-3">
              <div class="u-container-layout u-similar-container u-container-layout-3"><span class="u-align-center u-icon u-icon-rectangle u-text-custom-color-1 u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 487.23 487.23" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-d4d2"></use></svg><svg class="u-svg-content" viewBox="0 0 487.23 487.23" x="0px" y="0px" id="svg-d4d2" style="enable-background:new 0 0 487.23 487.23;"><g><g><path d="M55.323,203.641c15.664,0,29.813-9.405,35.872-23.854c25.017-59.604,83.842-101.61,152.42-101.61    c37.797,0,72.449,12.955,100.23,34.442l-21.775,3.371c-7.438,1.153-13.224,7.054-14.232,14.512    c-1.01,7.454,3.008,14.686,9.867,17.768l119.746,53.872c5.249,2.357,11.33,1.904,16.168-1.205    c4.83-3.114,7.764-8.458,7.796-14.208l0.621-131.943c0.042-7.506-4.851-14.144-12.024-16.332    c-7.185-2.188-14.947,0.589-19.104,6.837l-16.505,24.805C370.398,26.778,310.1,0,243.615,0C142.806,0,56.133,61.562,19.167,149.06    c-5.134,12.128-3.84,26.015,3.429,36.987C29.865,197.023,42.152,203.641,55.323,203.641z"></path><path d="M464.635,301.184c-7.27-10.977-19.558-17.594-32.728-17.594c-15.664,0-29.813,9.405-35.872,23.854    c-25.018,59.604-83.843,101.61-152.42,101.61c-37.798,0-72.45-12.955-100.232-34.442l21.776-3.369    c7.437-1.153,13.223-7.055,14.233-14.514c1.009-7.453-3.008-14.686-9.867-17.768L49.779,285.089    c-5.25-2.356-11.33-1.905-16.169,1.205c-4.829,3.114-7.764,8.458-7.795,14.207l-0.622,131.943    c-0.042,7.506,4.85,14.144,12.024,16.332c7.185,2.188,14.948-0.59,19.104-6.839l16.505-24.805    c44.004,43.32,104.303,70.098,170.788,70.098c100.811,0,187.481-61.561,224.446-149.059    C473.197,326.043,471.903,312.157,464.635,301.184z"></path>
</g>
</g></svg></span>
                <h5 class="u-align-center u-text u-text-custom-color-1 u-text-5">Regular Monthly Updates on your Contribution</h5>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-center u-black u-clearfix u-section-6" id="carousel_496d">
      <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
        <h2 class="u-align-left u-text u-text-1">Faq</h2>
        <p class="u-align-left u-text u-text-2"> Get answers to your questions about Lowsoot's initiative&nbsp;</p>
        <div class="u-accordion u-expanded-width-lg u-expanded-width-md u-expanded-width-sm u-expanded-width-xs u-faq u-spacing-10 u-accordion-1">
          <div class="u-accordion-item">
            <a class="active u-accordion-link u-active-white u-button-style u-hover-white u-white u-accordion-link-1" id="link-accordion-f600" aria-controls="accordion-f600" aria-selected="true">
              <span class="u-accordion-link-text"><b>What happens when I pay to erase my carbon footprint?</b>
              </span><span class="u-accordion-link-icon u-icon u-text-grey-50 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 448 448" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-83bd"></use></svg><svg class="u-svg-content" viewBox="0 0 448 448" id="svg-83bd" style=""><path d="m272 184c-4.417969 0-8-3.582031-8-8v-176h-80v176c0 4.417969-3.582031 8-8 8h-176v80h176c4.417969 0 8 3.582031 8 8v176h80v-176c0-4.417969 3.582031-8 8-8h176v-80zm0 0"></path></svg></span>
            </a>
            <div class="u-accordion-active u-accordion-pane u-container-style u-white u-accordion-pane-1" id="accordion-f600" aria-labelledby="link-accordion-f600">
              <div class="u-container-layout u-container-layout-1">
                <div class="fr-view u-clearfix u-rich-text u-text">
                  <p>As a paid subscriber, you help to finance programmes that reduce carbon emissions. We send out a "impact update" email once a month to let you know how far your money has gone. Visit our projects page to learn more.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="u-accordion-item">
            <a class="u-accordion-link u-active-white u-button-style u-hover-white u-white u-accordion-link-2" id="link-accordion-72f4" aria-controls="accordion-72f4" aria-selected="false">
              <span class="u-accordion-link-text"><b>What more can I do to aid in the resolution of the climate crisis without paying?</b>
              </span><span class="u-accordion-link-icon u-icon u-text-grey-50 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 448 448" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-d2ec"></use></svg><svg class="u-svg-content" viewBox="0 0 448 448" id="svg-d2ec" style=""><path d="m272 184c-4.417969 0-8-3.582031-8-8v-176h-80v176c0 4.417969-3.582031 8-8 8h-176v80h176c4.417969 0 8 3.582031 8 8v176h80v-176c0-4.417969 3.582031-8 8-8h176v-80zm0 0"></path></svg></span>
            </a>
            <div class="u-accordion-pane u-container-style u-white u-accordion-pane-2" id="accordion-72f4" aria-labelledby="link-accordion-72f4">
              <div class="u-container-layout u-container-layout-2">
                <div class="fr-view u-clearfix u-rich-text u-text">
                  <p>The first thing you should do is figure out how to decrease and offset your carbon footprint. You can also help others learn about climate change. The majority of people are unaware of the gravity of the situation. Vote for leaders that care about climate change.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="u-accordion-item">
            <a class="u-accordion-link u-active-white u-button-style u-hover-white u-white u-accordion-link-3" id="link-accordion-854e" aria-controls="accordion-854e" aria-selected="false">
              <span class="u-accordion-link-text"><b>How does your business model work?</b>
              </span><span class="u-accordion-link-icon u-icon u-text-grey-50 u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 448 448" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-fa38"></use></svg><svg class="u-svg-content" viewBox="0 0 448 448" id="svg-fa38" style=""><path d="m272 184c-4.417969 0-8-3.582031-8-8v-176h-80v176c0 4.417969-3.582031 8-8 8h-176v80h176c4.417969 0 8 3.582031 8 8v176h80v-176c0-4.417969 3.582031-8 8-8h176v-80zm0 0"></path></svg></span>
            </a>
            <div class="u-accordion-pane u-container-style u-white u-accordion-pane-3" id="accordion-854e" aria-labelledby="link-accordion-854e">
              <div class="u-container-layout u-container-layout-3">
                <div class="fr-view u-clearfix u-rich-text u-text">
                  <p>Lowsoot charges a 20% transaction fee to cover transaction costs (2.9 3% of every purchase must go to Razorpay, our payment processor) and development costs to grow our impact in the community. For example, our fee enables us to hire the world's top talent and put them to work combating climate change.</p>
                  <p>Eventually, we hope to profit from selling services that help make other corporations more sustainable, but for now, we have to take a 20% fee on all offsets in order to operate the business.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <?php
        if(!$_SESSION['user_paid']){
        
    ?>
        <section class="u-clearfix u-grey-10 u-section-7" id="Erase">
      <div class="u-clearfix u-sheet u-sheet-1">
        <h1 class="u-align-center-lg u-align-center-md u-align-center-xl u-align-center-xs u-align-left-sm u-custom-font u-font-titillium-web u-text u-text-grey-80 u-text-1">Erase Your Carbon Footprint</h1>
        <div class="u-clearfix u-layout-wrap u-layout-wrap-1">
          <div class="u-layout" style="max-height:5rem">
            <div class="u-layout-row">
              <div class="u-align-center-lg u-align-center-md u-align-center-xl u-align-center-xs u-align-left-sm u-container-style u-layout-cell u-size-60 u-layout-cell-1">
                <div class="u-container-layout u-container-layout-1">
                  <p class="u-align-center-lg u-align-center-md u-align-center-xl u-text u-text-2">Erasing your Carbon Footprint helps the earth by reversing your contribution to climate change. You can erase your carbon footprint by following these steps.&nbsp;</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="u-clearfix u-gutter-30 u-layout-wrap u-layout-wrap-2">
          <div class="u-gutter-0 u-layout">
            <div class="u-layout-row" style="min-height: auto;">
              <div class="u-align-left u-container-style u-custom-color-4 u-layout-cell u-left-cell u-radius-15 u-shape-round u-size-20 u-layout-cell-2">
             <?php
             
                  $date_array=getdate();
                  $display_count_manipulator=$date_array['year']+$date_array['mday']+$date_array['wday'];
                  
                  $tablename = $wpdb->prefix."users";
                  $entry_count = $wpdb->get_var("SELECT COUNT(*) FROM ".$tablename); 
                  
                  $first_random_id=rand(10,$entry_count/2);
                  $second_random_id=rand(10,$entry_count);
             
                  $user1=$wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".$first_random_id);
                  $user2=$wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".$second_random_id);
           
            ?>
                <div class="u-container-layout u-valign-top-lg u-valign-top-md u-valign-top-sm u-container-layout-2">
                  <h4 class="u-text u-text-body-alt-color u-text-3">Erase 100% of your annual carbon emissions</h4>
                  <h1 class="u-custom-font u-font-roboto u-text u-text-default u-text-white u-text-4"> ₹ <?php echo round($price)??''; ?></h1>
                  <a href="https://lowsoot.staging.tempurl.host/erase-all/" class="u-align-center-md u-align-center-sm u-align-center-xs u-btn u-btn-round u-button-style u-custom-font u-font-montserrat u-radius-8 u-text-custom-color-1 u-white u-btn-1">Erase Now</a>
                  <img class="u-image u-image-circle u-image-1" src="https://source.unsplash.com/random/<?php echo $first_random_id; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <img class="u-image u-image-circle u-image-2" src="https://source.unsplash.com/random/<?php echo $second_random_id; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <img class="u-image u-image-circle u-image-3" src="https://source.unsplash.com/random/<?php echo $first_random_id+1; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <p class="u-heading-font u-text u-text-5"><?php echo $user1->user_nicename.",".$user2->user_nicename; ?> &amp; <?php echo round($display_count_manipulator/10); ?> others have used this method</p>
                </div>
              </div>
              <div class="u-align-left u-container-style u-layout-cell u-radius-15 u-size-20 u-white u-layout-cell-3">
            <?php
                  $first_random_id=rand(10,$entry_count/2);
                  $second_random_id=rand(10,$entry_count);
             
                  $user1=$wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".$first_random_id);
                  $user2=$wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".$second_random_id);
           
            ?>
                <div class="u-container-layout u-container-layout-3">
                  <h4 class="u-text u-text-grey-80 u-text-6"> Erase 30% of your annual carbon emissions</h4>
                  <h1 class="u-custom-font u-font-roboto u-text u-text-custom-color-4 u-text-default u-text-7"> ₹ <?php echo round($price * (30/100))??''; ?></h1>
                  <a href="https://lowsoot.staging.tempurl.host/erasethirty/" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-color-4 u-custom-font u-font-montserrat u-radius-8 u-text-white u-btn-2">Erase Now</a>
                  <img class="u-image u-image-circle u-image-4" src="https://source.unsplash.com/random/<?php echo $first_random_id+1; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <img class="u-image u-image-circle u-image-5" src="https://source.unsplash.com/random/<?php echo $first_random_id+2; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <img class="u-image u-image-circle u-image-6" src="https://source.unsplash.com/random/<?php echo $first_random_id+3; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <p class="u-heading-font u-text u-text-8"><?php echo $user1->user_nicename.",".$user2->user_nicename; ?> &amp; <?php echo round($display_count_manipulator/5); ?> others have used this method</p>
                </div>
              </div>
              <div class="u-align-center-xs u-align-left-lg u-align-left-md u-align-left-sm u-align-left-xl u-container-style u-layout-cell u-radius-15 u-right-cell u-size-20 u-white u-layout-cell-4">
              <?php
                  $first_random_id=rand(10,$entry_count/2);
                  $second_random_id=rand(10,$entry_count);
             
                  $user1=$wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".$first_random_id);
                  $user2=$wpdb->get_row("SELECT * FROM ".$tablename." WHERE ID=".$second_random_id);
           
            ?>
                <div class="u-container-layout u-container-layout-4">
                  <h4 class="u-text u-text-grey-70 u-text-9"> Erase 10% of your annual carbon emissions</h4>
                  <h1 class="u-custom-font u-font-roboto u-text u-text-custom-color-4 u-text-default u-text-10"> ₹ <?php echo round($price * (10/100))??''; ?></h1>
                  <a href="https://lowsoot.staging.tempurl.host/eraseten/" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-color-4 u-custom-font u-font-montserrat u-radius-8 u-text-white u-btn-3">Erase Now</a>
                  <img class="u-image u-image-circle u-image-7" src="https://source.unsplash.com/random/<?php echo $second_random_id+1; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <img class="u-image u-image-circle u-image-8" src="https://source.unsplash.com/random/<?php echo $second_random_id+2; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <img class="u-image u-image-circle u-image-9" src="https://source.unsplash.com/random/<?php echo $second_random_id+3; ?>" alt="" data-image-width="1280" data-image-height="853">
                  <p class="u-heading-font u-text u-text-11"><?php echo $user1->user_nicename.",".$user2->user_nicename; ?> &amp; <?php echo round($display_count_manipulator/14); ?> others have used this method</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <?php
        }
    ?>
    
    <section class="u-clearfix u-section-8" id="sec-00be" style="display:none!important;">
      <div class="u-clearfix u-sheet u-sheet-1">
        <h2 class="u-align-center u-custom-font u-font-raleway u-text u-text-palette-1-base u-text-1">
          <span class="u-text-grey-60">You can also Erase your Carbon Footprin​t Without our Help By Doing the following</span>
        </h2>
        
            </div>
          </div><div class="u-expanded-width u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-align-center-xs u-align-left-lg u-align-left-md u-align-left-sm u-align-left-xl u-container-style u-list-item u-radius-50 u-repeater-item u-shape-round u-white u-list-item-1">
              <div class="u-container-layout u-similar-container u-container-layout-1">
                <div alt="" class="u-align-center-md u-align-center-sm u-align-center-xs u-image u-image-circle u-image-1" data-image-width="1280" data-image-height="785"></div>
                <h3 class="u-custom-font u-font-raleway u-text u-text-grey-70 u-text-2">Refer a Friend</h3>
                <p class="u-text u-text-3">Spread the word about our initiative to a friend, help them calculate their Carbon Footprint with Lowsoot</p>
                <a href="https://lowsoot.com/refer" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-color-4 u-hover-palette-1-light-1 u-radius-6 u-btn-1">Refer a Friend</a>
              </div>
            </div>
            <div class="u-align-center-xs u-align-left-lg u-align-left-md u-align-left-sm u-align-left-xl u-container-style u-custom-background u-list-item u-repeater-item u-shape-rectangle">
              <div class="u-container-layout u-similar-container u-container-layout-2">
                <div alt="" class="u-align-center-md u-align-center-sm u-align-center-xs u-image u-image-circle u-image-2" data-image-width="1280" data-image-height="920"></div>
                <h3 class="u-align-center-md u-align-center-sm u-custom-font u-font-raleway u-text u-text-grey-70 u-text-4">Carbon Offset Guide</h3>
                <p class="u-text u-text-5">We have prepared an easy guide for you to follow and help reverse the climate change without any investment</p>
                <a href="https://lowsoot.com/guide" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-color-4 u-hover-palette-1-light-1 u-radius-6 u-btn-2">View the Guide</a>
              </div>
        </div>
        <div class="u-expanded-width u-list u-list-2">
          <div class="u-repeater u-repeater-2">
            <div class="u-align-center-xs u-container-style u-custom-background u-list-item u-repeater-item">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-3">
                <ul class="u-custom-item u-custom-list u-text u-text-palette-2-base u-text-6">
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                    <div>Plant a Tree</div>
                  </li>
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Switch to 5 Star Appliances<br>
                  </li>
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Switch to a Vegetarian Diet
                  </li>
                
                </ul>
              </div>
            </div>
            <div class="u-align-center-xs u-container-style u-custom-background u-list-item u-repeater-item">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-4">
                <ul class="u-custom-item u-custom-list u-text u-text-palette-2-base u-text-7">
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                    <div>Choose Public Transport</div>
                  </li>
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                    <div>Install a Solar Panel</div>
                  </li>
                   <li class="display-flex" style="justify-content:center;">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path>
                      </svg>
                    </div>
                    <div>Transition to Electric Vehicles</div>
                  </li>
                </ul>
              </div>
            </div>
            <div class="u-align-center-xs u-container-style u-custom-background u-list-item u-repeater-item">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-5">
                <ul class="u-custom-item u-custom-list u-text u-text-palette-2-base u-text-8">
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path>
                      </svg>
                    </div>
                    <div>Sign up for a Green Policy</div>
                  </li>
                  <li class="display-flex" style="justify-content:center;">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                    <div>Segregate Dry &amp; Wet Waste</div>
                  </li>
                  <li class="display-flex">
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                    <div>Switch to Electric Stoves</div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="u-expanded-width u-list u-list-3">
          <div class="u-repeater u-repeater-3">
            <div class="u-align-center u-container-style u-custom-color-4 u-list-item u-radius-16 u-repeater-item u-shape-round u-list-item-6">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-6"><span class="u-icon u-icon-circle u-text-white u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 438.483 438.483" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-468b"></use></svg><svg class="u-svg-content" viewBox="0 0 438.483 438.483" x="0px" y="0px" id="svg-468b" style="enable-background:new 0 0 438.483 438.483;"><g><g><path d="M431.168,230.762c-23.552-75.776-98.304-127.488-187.904-129.024V13.162c0-4.096-3.584-7.68-7.68-7.68    c-1.536,0-3.072,0.512-4.608,1.536L3.136,171.882c-3.584,2.56-4.096,7.168-1.536,10.752c0.512,0.512,1.024,1.024,1.536,1.536    l227.84,163.84c3.584,2.56,8.192,1.536,10.752-1.536c1.024-1.536,1.536-3.072,1.536-4.608v-88.064    c55.296,0,101.888,26.112,118.272,65.536c13.824,33.792,2.56,70.144-30.208,100.352c-3.072,3.072-3.584,7.68-0.512,10.752    c1.536,1.536,3.584,2.56,5.632,2.56h6.144c1.536,0,3.072-0.512,4.096-1.536C421.952,381.802,454.208,304.49,431.168,230.762z"></path>
</g>
</g></svg>
            
            
          </span>
                <h4 class="u-text u-text-default u-text-9">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-white u-btn-3" href="https://lowsoot.com">Back To Home</a>
                </h4>
              </div>
            </div>
            <div class="u-align-center u-container-style u-custom-color-4 u-list-item u-radius-16 u-repeater-item u-shape-round u-list-item-7">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-7"><span class="u-icon u-icon-circle u-text-white u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 423.055 423.055" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-66d0"></use></svg><svg class="u-svg-content" viewBox="0 0 423.055 423.055" x="0px" y="0px" id="svg-66d0" style="enable-background:new 0 0 423.055 423.055;"><g><g><path d="M362.021,10.869c-6.431-2.963-14.009-1.81-19.269,2.93l-27.755,24.575c-0.755,0.672-1.894,0.668-2.645-0.008L274.588,4.59    c-6.83-6.12-17.17-6.12-24,0l-37.73,33.745c-0.759,0.678-1.906,0.678-2.665,0L172.459,4.59c-6.83-6.119-17.17-6.119-24,0    L110.69,38.366c-0.756,0.676-1.898,0.679-2.658,0.007l-27.78-24.574c-7.37-6.554-18.658-5.893-25.212,1.477    c-2.939,3.305-4.547,7.583-4.513,12.005v368.494c-0.066,9.878,7.888,17.939,17.766,18.005c4.425,0.03,8.703-1.582,12.009-4.523    l27.755-24.575c0.755-0.672,1.894-0.668,2.645,0.008l37.764,33.776c6.83,6.12,17.17,6.12,24,0l37.734-33.745    c0.759-0.678,1.906-0.678,2.665,0l37.734,33.744c6.831,6.117,17.17,6.117,24,0l37.771-33.776c0.756-0.676,1.898-0.679,2.658-0.007    l27.78,24.574c7.373,6.551,18.66,5.885,25.211-1.488c2.934-3.302,4.54-7.575,4.508-11.993V27.281    C372.621,20.202,368.489,13.747,362.021,10.869z M116.734,143.528h99.586c4.418,0,8,3.582,8,8s-3.582,8-8,8h-99.586    c-4.418,0-8-3.582-8-8S112.316,143.528,116.734,143.528z M306.32,279.528H116.734c-4.418,0-8-3.582-8-8s3.582-8,8-8H306.32    c4.418,0,8,3.582,8,8S310.738,279.528,306.32,279.528z M306.32,219.528H116.734c-4.418,0-8-3.582-8-8s3.582-8,8-8H306.32    c4.418,0,8,3.582,8,8S310.738,219.528,306.32,219.528z"></path>
</g>
</g></svg>
            
            
          </span>
                <h4 class="u-text u-text-default u-text-10">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-white u-btn-4" href="https://lowsoot.com/receipts">My Receipts</a>
                </h4>
              </div>
            </div>
            <div class="u-align-center u-container-style u-custom-color-4 u-list-item u-radius-16 u-repeater-item u-shape-round u-list-item-8">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-8"><span class="u-icon u-icon-circle u-text-white u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-0c76"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" id="svg-0c76"><g><path d="m467 165.999h-272c-24.814 0-45 20.186-45 45v150c0 24.814 20.186 45 45 45h235.789l55.605 55.605c4.33 4.33 10.82 5.559 16.348 3.252 5.61-2.314 9.258-7.793 9.258-13.857v-240c0-24.814-20.186-45-45-45zm-75 165h-122c-8.291 0-15-6.709-15-15s6.709-15 15-15h122c8.291 0 15 6.709 15 15s-6.709 15-15 15zm30-60h-182c-8.291 0-15-6.709-15-15s6.709-15 15-15h182c8.291 0 15 6.709 15 15s-6.709 15-15 15z"></path><path d="m9.258 344.856c5.528 2.307 12.017 1.078 16.348-3.252l55.605-55.605h38.789v-75c0-41.353 33.647-75 75-75h167v-45c0-24.853-20.147-45-45-45h-272c-24.853 0-45 20.147-45 45v240c0 6.064 3.647 11.543 9.258 13.857z"></path>
</g></svg>
            
            
          </span>
                <h4 class="u-text u-text-default u-text-11">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-white u-btn-5" href="https://lowsoot.com/get-support">Get Support</a>
                </h4>
              </div>
            </div>
            <div class="u-align-center u-container-style u-custom-color-4 u-list-item u-radius-16 u-repeater-item u-shape-round u-list-item-9">
              <div class="u-container-layout u-similar-container u-valign-top u-container-layout-9"><span class="u-icon u-icon-circle u-text-white u-icon-4"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 122.775 122.776" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-2752"></use></svg><svg class="u-svg-content" viewBox="0 0 122.775 122.776" x="0px" y="0px" id="svg-2752" style="enable-background:new 0 0 122.775 122.776;"><g><path d="M86,28.074v-20.7c0-3.3-2.699-6-6-6H6c-3.3,0-6,2.7-6,6v3.9v78.2v2.701c0,2.199,1.3,4.299,3.2,5.299l45.6,23.601   c2,1,4.4-0.399,4.4-2.7v-23H80c3.301,0,6-2.699,6-6v-32.8H74v23.8c0,1.7-1.3,3-3,3H53.3v-30.8v-19.5v-0.6c0-2.2-1.3-4.3-3.2-5.3   l-26.9-13.8H71c1.7,0,3,1.3,3,3v11.8h12V28.074z"></path><path d="M101.4,18.273l19.5,19.5c2.5,2.5,2.5,6.2,0,8.7l-19.5,19.5c-2.5,2.5-6.301,2.601-8.801,0.101   c-2.399-2.399-2.1-6.4,0.201-8.8l8.799-8.7H67.5c-1.699,0-3.4-0.7-4.5-2c-2.8-3-2.1-8.3,1.5-10.3c0.9-0.5,2-0.8,3-0.8h34.1   c0,0-8.699-8.7-8.799-8.7c-2.301-2.3-2.601-6.4-0.201-8.7C95,15.674,98.9,15.773,101.4,18.273z"></path>
</g></svg>
            
            
          </span>
                <h4 class="u-text u-text-default u-text-12">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-white u-btn-6" href="https://lowsoot.com/wp-login.php?action=logout&amp;_wpnonce=c1588dbf32">Logout</a>
                </h4>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <footer class="u-align-center u-clearfix u-custom-color-3 u-footer u-footer" id="sec-c677"><div class="u-clearfix u-sheet u-sheet-1">
        <p class="u-custom-font u-font-montserrat u-small-text u-text u-text-variant u-text-1"> Every passing moment is an opportunity to turn it all around</p>
        <p class="u-custom-font u-font-montserrat u-small-text u-text u-text-variant u-text-white u-text-2"> Follow us and spread the word</p>
        <div class="u-social-icons u-spacing-27 u-social-icons-1">
          <a class="u-social-url" title="facebook" target="_blank" href="https://facebook.com/name"><span class="u-icon u-social-facebook u-social-icon u-text-white"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 26 26" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-30f5"></use></svg><svg class="u-svg-content" viewBox="0 0 26 26" x="0px" y="0px" id="svg-30f5" style="enable-background:new 0 0 26 26;"><g><path style="fill:currentColor;" d="M21.125,0H4.875C2.182,0,0,2.182,0,4.875v16.25C0,23.818,2.182,26,4.875,26h16.25   C23.818,26,26,23.818,26,21.125V4.875C26,2.182,23.818,0,21.125,0z M20.464,14.002h-2.433v9.004h-4.063v-9.004h-1.576v-3.033h1.576   V9.037C13.969,6.504,15.021,5,18.006,5h3.025v3.022h-1.757c-1.162,0-1.238,0.433-1.238,1.243l-0.005,1.703h2.764L20.464,14.002z"></path>
</g></svg></span>
          </a>
          <a class="u-social-url" title="twitter" target="_blank" href="https://twitter.com/name"><span class="u-icon u-social-icon u-social-twitter u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 97.75 97.75" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-2714"></use></svg><svg class="u-svg-content" viewBox="0 0 97.75 97.75" x="0px" y="0px" id="svg-2714" style="enable-background:new 0 0 97.75 97.75;"><g><path d="M48.875,0C21.882,0,0,21.882,0,48.875S21.882,97.75,48.875,97.75S97.75,75.868,97.75,48.875S75.868,0,48.875,0z    M78.43,35.841c0.023,0.577,0.035,1.155,0.035,1.736c0,20.878-15.887,42.473-42.473,42.473c-8.127,0-16.04-2.319-22.883-6.708   c-0.143-0.091-0.202-0.268-0.145-0.427c0.057-0.158,0.218-0.256,0.383-0.237c1.148,0.137,2.322,0.205,3.487,0.205   c6.323,0,12.309-1.955,17.372-5.664c-6.069-0.512-11.285-4.619-13.161-10.478c-0.039-0.122-0.011-0.255,0.073-0.351   c0.085-0.096,0.215-0.138,0.339-0.115c1.682,0.319,3.392,0.34,5.04,0.072c-6.259-1.945-10.658-7.808-10.658-14.483l0.002-0.194   c0.003-0.127,0.072-0.243,0.182-0.306c0.109-0.064,0.245-0.065,0.355-0.003c1.632,0.906,3.438,1.488,5.291,1.711   c-3.597-2.867-5.709-7.213-5.709-11.862c0-2.682,0.71-5.318,2.054-7.623c0.06-0.103,0.166-0.169,0.284-0.178   c0.119-0.012,0.234,0.04,0.309,0.132c7.362,9.03,18.191,14.59,29.771,15.305c-0.193-0.972-0.291-1.974-0.291-2.985   c0-8.361,6.802-15.162,15.162-15.162c4.11,0,8.082,1.689,10.929,4.641c3.209-0.654,6.266-1.834,9.09-3.508   c0.129-0.077,0.291-0.065,0.41,0.028c0.116,0.094,0.164,0.25,0.118,0.394c-0.957,2.993-2.823,5.604-5.33,7.489   c2.361-0.411,4.652-1.105,6.831-2.072c0.146-0.067,0.319-0.025,0.424,0.098c0.104,0.124,0.113,0.301,0.023,0.435   C83.759,31.175,81.299,33.744,78.43,35.841z"></path>
</g></svg></span>
          </a>
          <a class="u-social-url" title="instagram" target="_blank" href="https://instagram.com/name"><span class="u-icon u-social-icon u-social-instagram u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-ddd1"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" id="svg-ddd1"><path d="m305 256c0 27.0625-21.9375 49-49 49s-49-21.9375-49-49 21.9375-49 49-49 49 21.9375 49 49zm0 0"></path><path d="m370.59375 169.304688c-2.355469-6.382813-6.113281-12.160157-10.996094-16.902344-4.742187-4.882813-10.515625-8.640625-16.902344-10.996094-5.179687-2.011719-12.960937-4.40625-27.292968-5.058594-15.503906-.707031-20.152344-.859375-59.402344-.859375-39.253906 0-43.902344.148438-59.402344.855469-14.332031.65625-22.117187 3.050781-27.292968 5.0625-6.386719 2.355469-12.164063 6.113281-16.902344 10.996094-4.882813 4.742187-8.640625 10.515625-11 16.902344-2.011719 5.179687-4.40625 12.964843-5.058594 27.296874-.707031 15.5-.859375 20.148438-.859375 59.402344 0 39.25.152344 43.898438.859375 59.402344.652344 14.332031 3.046875 22.113281 5.058594 27.292969 2.359375 6.386719 6.113281 12.160156 10.996094 16.902343 4.742187 4.882813 10.515624 8.640626 16.902343 10.996094 5.179688 2.015625 12.964844 4.410156 27.296875 5.0625 15.5.707032 20.144532.855469 59.398438.855469 39.257812 0 43.90625-.148437 59.402344-.855469 14.332031-.652344 22.117187-3.046875 27.296874-5.0625 12.820313-4.945312 22.953126-15.078125 27.898438-27.898437 2.011719-5.179688 4.40625-12.960938 5.0625-27.292969.707031-15.503906.855469-20.152344.855469-59.402344 0-39.253906-.148438-43.902344-.855469-59.402344-.652344-14.332031-3.046875-22.117187-5.0625-27.296874zm-114.59375 162.179687c-41.691406 0-75.488281-33.792969-75.488281-75.484375s33.796875-75.484375 75.488281-75.484375c41.6875 0 75.484375 33.792969 75.484375 75.484375s-33.796875 75.484375-75.484375 75.484375zm78.46875-136.3125c-9.742188 0-17.640625-7.898437-17.640625-17.640625s7.898437-17.640625 17.640625-17.640625 17.640625 7.898437 17.640625 17.640625c-.003906 9.742188-7.898437 17.640625-17.640625 17.640625zm0 0"></path><path d="m256 0c-141.363281 0-256 114.636719-256 256s114.636719 256 256 256 256-114.636719 256-256-114.636719-256-256-256zm146.113281 316.605469c-.710937 15.648437-3.199219 26.332031-6.832031 35.683593-7.636719 19.746094-23.246094 35.355469-42.992188 42.992188-9.347656 3.632812-20.035156 6.117188-35.679687 6.832031-15.675781.714844-20.683594.886719-60.605469.886719-39.925781 0-44.929687-.171875-60.609375-.886719-15.644531-.714843-26.332031-3.199219-35.679687-6.832031-9.8125-3.691406-18.695313-9.476562-26.039063-16.957031-7.476562-7.339844-13.261719-16.226563-16.953125-26.035157-3.632812-9.347656-6.121094-20.035156-6.832031-35.679687-.722656-15.679687-.890625-20.6875-.890625-60.609375s.167969-44.929688.886719-60.605469c.710937-15.648437 3.195312-26.332031 6.828125-35.683593 3.691406-9.808594 9.480468-18.695313 16.960937-26.035157 7.339844-7.480469 16.226563-13.265625 26.035157-16.957031 9.351562-3.632812 20.035156-6.117188 35.683593-6.832031 15.675781-.714844 20.683594-.886719 60.605469-.886719s44.929688.171875 60.605469.890625c15.648437.710937 26.332031 3.195313 35.683593 6.824219 9.808594 3.691406 18.695313 9.480468 26.039063 16.960937 7.476563 7.34375 13.265625 16.226563 16.953125 26.035157 3.636719 9.351562 6.121094 20.035156 6.835938 35.683593.714843 15.675781.882812 20.683594.882812 60.605469s-.167969 44.929688-.886719 60.605469zm0 0"></path></svg></span>
          </a>
        </div>
        <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/assets/images/textlogowhite.png" alt="" data-image-width="2000" data-image-height="483">
      </div></footer>
  </body>
</html>