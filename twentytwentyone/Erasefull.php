
<?php  
    session_start();
    $url= $_SERVER[REQUEST_URI];
    // echo $url;
    
    if(strpos($url,'payment')){
        require('razorpay/verify.php');
    } 
    
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
      $answer=$form_entry_meta_list_result[$key]['meta_value'];
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
        $answer=$form_entry_meta_list_result[$key]['meta_value'];
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
    // if($total_calculation_value < 1 )
    //   $price = $total_calculation_value*10;
    // else
    //   $price = $total_calculation_value*.50;
  
    $price=$_SESSION['price'];
    $customername = (isset($current_user->user_nicename))?$current_user->user_nicename:'';
    $email = (isset($current_user->user_email))?$current_user->user_email:'';
    //$_SESSION['email'] = $email;
   
    $contactno ='';
    $orderData = [
        //'receipt'         => 3456,
        'amount'          => ((int)$price) * 100, // 2000 rupees in paise
        'currency'        => 'INR',
        'payment_capture' => 1 // auto capture
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    
    $razorpayOrderId = $razorpayOrder['id'];
    
    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
    $_SESSION['carbon_percentage_reduced']=100;
    
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
        "name"              => "Lowsoot Climate Solutions Pvt. Ltd.",
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



<!--<button id="rzp-button1">Pay Now</button> -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<form name='razorpayform' action="?payment=payment" method="POST">
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
    <title>Copy of Home</title>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/nicepage.css" media="screen">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/Copy-of-Home.css" media="screen">
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/jquery.js" defer=""></script>
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/nicepage.js" defer=""></script>
    <meta name="generator" content="Nicepage 3.22.0, nicepage.com">
    <link id="u-theme-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i|Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i">
    <link id="u-page-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Titillium+Web:200,200i,300,300i,400,400i,600,600i,700,700i,900|Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i">
    
    
    
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
    <meta name="twitter:title" content="Copy of Home">
    <meta name="twitter:description" content="">
    <meta property="og:title" content="Copy of Home">
    <meta property="og:type" content="website">
  </head>
  <body class="u-body"><header class="u-align-center-sm u-align-center-xs u-clearfix u-custom-color-1 u-header u-header" id="sec-ef8f"><div class="u-clearfix u-sheet u-valign-middle-lg u-sheet-1">
        <a href="https://lowsoot.com" class="u-image u-logo u-image-1" data-image-width="968" data-image-height="1028">
          <img src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/images/lowsoot.png" class="u-logo-image u-logo-image-1">
        </a>
        <div class="u-hidden-md u-hidden-sm u-hidden-xs u-social-icons u-spacing-21 u-social-icons-1">
          <a class="u-social-url" title="facebook" target="_blank" href=""><span class="u-icon u-social-facebook u-social-icon u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-bae3"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" id="svg-bae3"><path d="m512 256c0-141.4-114.6-256-256-256s-256 114.6-256 256 114.6 256 256 256c1.5 0 3 0 4.5-.1v-199.2h-55v-64.1h55v-47.2c0-54.7 33.4-84.5 82.2-84.5 23.4 0 43.5 1.7 49.3 2.5v57.2h-33.6c-26.5 0-31.7 12.6-31.7 31.1v40.8h63.5l-8.3 64.1h-55.2v189.5c107-30.7 185.3-129.2 185.3-246.1z"></path></svg></span>
          </a>
          <a class="u-social-url" title="twitter" target="_blank" href=""><span class="u-icon u-social-icon u-social-twitter u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 511 511.9" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-3569"></use></svg><svg class="u-svg-content" viewBox="0 0 511 511.9" id="svg-3569"><path d="m510.949219 150.5c-1.199219-27.199219-5.597657-45.898438-11.898438-62.101562-6.5-17.199219-16.5-32.597657-29.601562-45.398438-12.800781-13-28.300781-23.101562-45.300781-29.5-16.296876-6.300781-34.898438-10.699219-62.097657-11.898438-27.402343-1.300781-36.101562-1.601562-105.601562-1.601562s-78.199219.300781-105.5 1.5c-27.199219 1.199219-45.898438 5.601562-62.097657 11.898438-17.203124 6.5-32.601562 16.5-45.402343 29.601562-13 12.800781-23.097657 28.300781-29.5 45.300781-6.300781 16.300781-10.699219 34.898438-11.898438 62.097657-1.300781 27.402343-1.601562 36.101562-1.601562 105.601562s.300781 78.199219 1.5 105.5c1.199219 27.199219 5.601562 45.898438 11.902343 62.101562 6.5 17.199219 16.597657 32.597657 29.597657 45.398438 12.800781 13 28.300781 23.101562 45.300781 29.5 16.300781 6.300781 34.898438 10.699219 62.101562 11.898438 27.296876 1.203124 36 1.5 105.5 1.5s78.199219-.296876 105.5-1.5c27.199219-1.199219 45.898438-5.597657 62.097657-11.898438 34.402343-13.300781 61.601562-40.5 74.902343-74.898438 6.296876-16.300781 10.699219-34.902343 11.898438-62.101562 1.199219-27.300781 1.5-36 1.5-105.5s-.101562-78.199219-1.300781-105.5zm-46.097657 209c-1.101562 25-5.300781 38.5-8.800781 47.5-8.601562 22.300781-26.300781 40-48.601562 48.601562-9 3.5-22.597657 7.699219-47.5 8.796876-27 1.203124-35.097657 1.5-103.398438 1.5s-76.5-.296876-103.402343-1.5c-25-1.097657-38.5-5.296876-47.5-8.796876-11.097657-4.101562-21.199219-10.601562-29.398438-19.101562-8.5-8.300781-15-18.300781-19.101562-29.398438-3.5-9-7.699219-22.601562-8.796876-47.5-1.203124-27-1.5-35.101562-1.5-103.402343s.296876-76.5 1.5-103.398438c1.097657-25 5.296876-38.5 8.796876-47.5 4.101562-11.101562 10.601562-21.199219 19.203124-29.402343 8.296876-8.5 18.296876-15 29.398438-19.097657 9-3.5 22.601562-7.699219 47.5-8.800781 27-1.199219 35.101562-1.5 103.398438-1.5 68.402343 0 76.5.300781 103.402343 1.5 25 1.101562 38.5 5.300781 47.5 8.800781 11.097657 4.097657 21.199219 10.597657 29.398438 19.097657 8.5 8.300781 15 18.300781 19.101562 29.402343 3.5 9 7.699219 22.597657 8.800781 47.5 1.199219 27 1.5 35.097657 1.5 103.398438s-.300781 76.300781-1.5 103.300781zm0 0"></path><path d="m256.449219 124.5c-72.597657 0-131.5 58.898438-131.5 131.5s58.902343 131.5 131.5 131.5c72.601562 0 131.5-58.898438 131.5-131.5s-58.898438-131.5-131.5-131.5zm0 216.800781c-47.097657 0-85.300781-38.199219-85.300781-85.300781s38.203124-85.300781 85.300781-85.300781c47.101562 0 85.300781 38.199219 85.300781 85.300781s-38.199219 85.300781-85.300781 85.300781zm0 0"></path><path d="m423.851562 119.300781c0 16.953125-13.746093 30.699219-30.703124 30.699219-16.953126 0-30.699219-13.746094-30.699219-30.699219 0-16.957031 13.746093-30.699219 30.699219-30.699219 16.957031 0 30.703124 13.742188 30.703124 30.699219zm0 0"></path></svg></span>
          </a>
          <a class="u-social-url" title="instagram" target="_blank" href=""><span class="u-icon u-social-icon u-social-instagram u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 310 310" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-4fee"></use></svg><svg class="u-svg-content" viewBox="0 0 310 310" x="0px" y="0px" id="svg-4fee" style="enable-background:new 0 0 310 310;"><g id="XMLID_822_"><path id="XMLID_823_" d="M297.917,64.645c-11.19-13.302-31.85-18.728-71.306-18.728H83.386c-40.359,0-61.369,5.776-72.517,19.938   C0,79.663,0,100.008,0,128.166v53.669c0,54.551,12.896,82.248,83.386,82.248h143.226c34.216,0,53.176-4.788,65.442-16.527   C304.633,235.518,310,215.863,310,181.835v-53.669C310,98.471,309.159,78.006,297.917,64.645z M199.021,162.41l-65.038,33.991   c-1.454,0.76-3.044,1.137-4.632,1.137c-1.798,0-3.592-0.484-5.181-1.446c-2.992-1.813-4.819-5.056-4.819-8.554v-67.764   c0-3.492,1.822-6.732,4.808-8.546c2.987-1.814,6.702-1.938,9.801-0.328l65.038,33.772c3.309,1.718,5.387,5.134,5.392,8.861   C204.394,157.263,202.325,160.684,199.021,162.41z"></path>
</g></svg></span>
          </a>
        </div>
        <nav class="u-align-left u-menu u-menu-dropdown u-offcanvas u-menu-1">
          <div class="menu-collapse" style="font-size: 1rem;">
            <a class="u-button-style u-custom-active-color u-custom-text-active-color u-custom-text-color u-nav-link" href="#" style="padding: 6px 10px; font-size: calc(1em + 12px);">
              <svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 302 302" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-8a8f"></use></svg>
              <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="svg-8a8f" x="0px" y="0px" viewBox="0 0 302 302" style="enable-background:new 0 0 302 302;" xml:space="preserve" class="u-svg-content"><g><rect y="36" width="302" height="30"></rect><rect y="236" width="302" height="30"></rect><rect y="136" width="302" height="30"></rect>
</g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg>
            </a>
          </div>
          <div class="u-custom-menu u-nav-container">
            <ul class="u-nav u-spacing-2 u-unstyled u-nav-1"><li class="u-nav-item"><a class="u-border-active-palette-1-base u-border-hover-palette-1-base u-button-style u-nav-link u-text-active-white u-text-body-alt-color u-text-hover-grey-90" href="https://lowsoot.com" style="padding: 10px 46px;">Home</a>
</li><li class="u-nav-item"><a class="u-border-active-palette-1-base u-border-hover-palette-1-base u-button-style u-nav-link u-text-active-white u-text-body-alt-color u-text-hover-grey-90" href="https://lowsoot.com/about/" style="padding: 10px 46px;">About</a>
</li><li class="u-nav-item"><a class="u-border-active-palette-1-base u-border-hover-palette-1-base u-button-style u-nav-link u-text-active-white u-text-body-alt-color u-text-hover-grey-90" href="#" style="padding: 10px 46px;">Projects</a><div class="u-nav-popup"><ul class="u-h-spacing-20 u-nav u-unstyled u-v-spacing-10 u-nav-2"><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="https://lowsoot.com/project-1/">Large Scale Forestation</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-white" href="https://lowsoot.com/project-2-community-tree-plantation/">Community Plantation</a>
</li></ul>
</div>
</li><li class="u-nav-item"><a class="u-border-active-palette-1-base u-border-hover-palette-1-base u-button-style u-nav-link u-text-active-white u-text-body-alt-color u-text-hover-grey-90" href="https://lowsoot.com/profile" style="padding: 10px 46px;">Account</a>
</li></ul>
          </div>
          <div class="u-custom-menu u-nav-container-collapse">
            <div class="u-align-center u-black u-container-style u-inner-container-layout u-opacity u-opacity-95 u-sidenav">
              <div class="u-sidenav-overflow">
                <div class="u-menu-close"></div>
                <ul class="u-align-center u-nav u-popupmenu-items u-unstyled u-nav-3"><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com" style="padding: 10px 46px;">Home</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/about/" style="padding: 10px 46px;">About</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#" style="padding: 10px 46px;">Projects</a><div class="u-nav-popup"><ul class="u-h-spacing-20 u-nav u-unstyled u-v-spacing-10 u-nav-4"><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/project-1/">Large Scale Forestation</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/project-2-community-tree-plantation/">Community Plantation</a>
</li></ul>
</div>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/profile" style="padding: 10px 46px;">Account</a>
</li></ul>
              </div>
            </div>
            <div class="u-black u-menu-overlay u-opacity u-opacity-70"></div>
          </div>
        </nav>
      </div></header> 
    <section class="u-align-center u-clearfix u-grey-5 u-section-1" id="sec-56a8">
      <div class="u-clearfix u-sheet u-valign-top-lg u-valign-top-md u-valign-top-sm u-valign-top-xl u-sheet-1">
        <div class="u-container-style u-expanded-width u-group u-shape-rectangle u-group-1">
          <div class="u-container-layout u-container-layout-1">
            <p class="u-align-center u-text u-text-1">You are about to erase 100% of your annual footprint for</p>
            <h1 class="u-align-center u-text u-text-palette-2-base u-text-2">₹ <?php echo round($price); ?> </h1>
            <p class="u-align-center u-text u-text-custom-color-1 u-text-3">This amount will be directly invested into Lowsoot's Climate Change Fund and invested in projects that remove CO2 from the environment</p>
            <button id="rzp-button1" class="u-active-palette-2-dark-3 u-align-center u-border-none u-btn u-btn-round u-button-style u-custom-color-1 u-custom-font u-font-montserrat u-hover-palette-2-dark-3 u-radius-8 u-text-active-white u-text-body-alt-color u-text-hover-white u-btn-1">Erase my&nbsp;Footprint</button>
            <h3 class="u-align-center u-text u-text-grey-40 u-text-4">How can I know what happened to my investment?</h3>
            <div class="u-list u-list-1">
              <div class="u-repeater u-repeater-1">
                <div class="u-align-center u-container-style u-custom-color-1 u-list-item u-radius-6 u-repeater-item u-shape-round u-list-item-1">
                  <div class="u-container-layout u-similar-container u-container-layout-2"><span class="u-icon u-icon-circle u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-7105"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" x="0px" y="0px" id="svg-7105" style="enable-background:new 0 0 512 512;"><g><g><g><path d="M10.688,95.156C80.958,154.667,204.26,259.365,240.5,292.01c4.865,4.406,10.083,6.646,15.5,6.646     c5.406,0,10.615-2.219,15.469-6.604c36.271-32.677,159.573-137.385,229.844-196.896c4.375-3.698,5.042-10.198,1.5-14.719     C494.625,69.99,482.417,64,469.333,64H42.667c-13.083,0-25.292,5.99-33.479,16.438C5.646,84.958,6.313,91.458,10.688,95.156z"></path><path d="M505.813,127.406c-3.781-1.76-8.229-1.146-11.375,1.542C416.51,195.01,317.052,279.688,285.76,307.885     c-17.563,15.854-41.938,15.854-59.542-0.021c-33.354-30.052-145.042-125-208.656-178.917c-3.167-2.688-7.625-3.281-11.375-1.542     C2.417,129.156,0,132.927,0,137.083v268.25C0,428.865,19.135,448,42.667,448h426.667C492.865,448,512,428.865,512,405.333     v-268.25C512,132.927,509.583,129.146,505.813,127.406z"></path>
</g>
</g>
</g></svg></span>
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-5">Regular&nbsp;<br>Emails
                    </h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-default u-text-font u-text-6">We will send you monthly updates via the registered email&nbsp;</h6>
                  </div>
                </div>
                <div class="u-align-center u-container-style u-custom-color-1 u-list-item u-radius-6 u-repeater-item u-shape-round u-list-item-2">
                  <div class="u-container-layout u-similar-container u-container-layout-3"><span class="u-icon u-icon-circle u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 46.2 46.2" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-92d1"></use></svg><svg class="u-svg-content" viewBox="0 0 46.2 46.2" x="0px" y="0px" id="svg-92d1" style="enable-background:new 0 0 46.2 46.2;"><g><path d="M46.006,23.975c0,5.168-4.206,9.375-9.375,9.375c-2.485,0-4.5-2.016-4.5-4.5V19.1c0-1.832,1.099-3.401,2.671-4.104   C33.414,9.825,28.701,6,23.1,6s-10.314,3.825-11.701,8.996c1.571,0.702,2.67,2.271,2.67,4.104v9.75   c0,2.104-1.451,3.858-3.404,4.351c1.73,3.207,4.666,5.67,8.194,6.782c0.665-1.697,2.31-2.908,4.241-2.908   c2.516,0,4.563,2.047,4.563,4.562S25.616,46.2,23.1,46.2c-2.398,0-4.35-1.867-4.527-4.222c-4.479-1.313-8.146-4.521-10.088-8.695   c-4.658-0.54-8.291-4.506-8.291-9.309c0-3.574,2.012-6.686,4.963-8.267C6.344,6.855,13.927,0,23.1,0   c9.172,0,16.756,6.855,17.943,15.708C43.995,17.288,46.006,20.4,46.006,23.975z"></path>
</g></svg></span>
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-7">Dedicated Helpline</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-default u-text-font u-text-8">You can call us anytime and get an update of your payment</h6>
                  </div>
                </div>
                <div class="u-align-center u-container-style u-custom-color-1 u-list-item u-radius-6 u-repeater-item u-shape-round u-list-item-3">
                  <div class="u-container-layout u-similar-container u-container-layout-4"><span class="u-icon u-icon-circle u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="-66 -21 682 682.66669" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-4ae0"></use></svg><svg class="u-svg-content" viewBox="-66 -21 682 682.66669" id="svg-4ae0"><path d="m422.554688 128.417969h113.699218l-119.667968-117.535157v111.574219c0 3.285157 2.675781 5.960938 5.96875 5.960938zm0 0"></path><path d="m422.554688 165.917969c-23.964844 0-43.464844-19.496094-43.464844-43.460938v-122.457031h-311.796875c-38.578125 0-69.960938 31.382812-69.960938 69.960938v500.078124c0 38.578126 31.382813 69.960938 69.960938 69.960938h410.078125c38.582031 0 69.960937-31.382812 69.960937-69.960938v-404.121093zm-42.859376 319.082031h-241.503906c-10.355468 0-18.75-8.394531-18.75-18.75s8.394532-18.75 18.75-18.75h241.503906c10.359376 0 18.75 8.394531 18.75 18.75s-8.390624 18.75-18.75 18.75zm-260.253906-93.75c0-10.355469 8.394532-18.75 18.75-18.75h215.507813c10.359375 0 18.75 8.394531 18.75 18.75s-8.398438 18.75-18.75 18.75h-215.507813c-10.355468 0-18.75-8.394531-18.75-18.75zm283.054688-56.25h-264.304688c-10.355468 0-18.75-8.394531-18.75-18.75s8.394532-18.75 18.75-18.75h264.304688c10.355468 0 18.75 8.394531 18.75 18.75s-8.394532 18.75-18.75 18.75zm0 0"></path></svg></span>
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-9">Personalized<br>Reports
                    </h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-default u-text-font u-text-10">You can request access to our reports on how your investment has helped in reversing climate change</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-clearfix u-grey-10 u-section-2" id="Erase" >
      <div class="u-clearfix u-sheet u-sheet-1" style="min-height: 300px!important;">
        <h1 class="u-align-center-lg u-align-center-md u-align-center-xl u-align-center-xs u-align-left-sm u-custom-font u-font-titillium-web u-text u-text-1" style="color:#4861df!important;"><a href="https://lowsoot.com/user-profile">Go Back to Main Page</a></h1>
        
      </div>
    </section>
    
    
    <footer class="u-align-center u-clearfix u-custom-color-1 u-footer u-footer" id="sec-c677"><div class="u-clearfix u-sheet u-sheet-1">
        <p class="u-custom-font u-font-montserrat u-small-text u-text u-text-variant u-text-1"> Every passing moment is an opportunity to turn it all around</p>
        <p class="u-custom-font u-font-montserrat u-small-text u-text u-text-body-color u-text-variant u-text-2"> Follow us and spread the word</p>
        <div class="u-social-icons u-spacing-27 u-social-icons-1">
          <a class="u-social-url" title="facebook" target="_blank" href="https://facebook.com/name"><span class="u-icon u-social-facebook u-social-icon u-text-black"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 112 112" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-688c"></use></svg><svg class="u-svg-content" viewBox="0 0 112 112" x="0" y="0" id="svg-688c"><circle fill="currentColor" cx="56.1" cy="56.1" r="55"></circle><path fill="#FFFFFF" d="M73.5,31.6h-9.1c-1.4,0-3.6,0.8-3.6,3.9v8.5h12.6L72,58.3H60.8v40.8H43.9V58.3h-8V43.9h8v-9.2
c0-6.7,3.1-17,17-17h12.5v13.9H73.5z"></path></svg></span>
          </a>
          <a class="u-social-url" title="twitter" target="_blank" href="https://twitter.com/name"><span class="u-icon u-social-icon u-social-twitter u-text-black"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 112 112" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-1bdc"></use></svg><svg class="u-svg-content" viewBox="0 0 112 112" x="0" y="0" id="svg-1bdc"><circle fill="currentColor" class="st0" cx="56.1" cy="56.1" r="55"></circle><path fill="#FFFFFF" d="M83.8,47.3c0,0.6,0,1.2,0,1.7c0,17.7-13.5,38.2-38.2,38.2C38,87.2,31,85,25,81.2c1,0.1,2.1,0.2,3.2,0.2
c6.3,0,12.1-2.1,16.7-5.7c-5.9-0.1-10.8-4-12.5-9.3c0.8,0.2,1.7,0.2,2.5,0.2c1.2,0,2.4-0.2,3.5-0.5c-6.1-1.2-10.8-6.7-10.8-13.1
c0-0.1,0-0.1,0-0.2c1.8,1,3.9,1.6,6.1,1.7c-3.6-2.4-6-6.5-6-11.2c0-2.5,0.7-4.8,1.8-6.7c6.6,8.1,16.5,13.5,27.6,14
c-0.2-1-0.3-2-0.3-3.1c0-7.4,6-13.4,13.4-13.4c3.9,0,7.3,1.6,9.8,4.2c3.1-0.6,5.9-1.7,8.5-3.3c-1,3.1-3.1,5.8-5.9,7.4
c2.7-0.3,5.3-1,7.7-2.1C88.7,43,86.4,45.4,83.8,47.3z"></path></svg></span>
          </a>
          <a class="u-social-url" title="instagram" target="_blank" href="https://instagram.com/name"><span class="u-icon u-social-icon u-social-instagram u-text-black"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 112 112" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-1b6d"></use></svg><svg class="u-svg-content" viewBox="0 0 112 112" x="0" y="0" id="svg-1b6d"><circle fill="currentColor" cx="56.1" cy="56.1" r="55"></circle><path fill="#FFFFFF" d="M55.9,38.2c-9.9,0-17.9,8-17.9,17.9C38,66,46,74,55.9,74c9.9,0,17.9-8,17.9-17.9C73.8,46.2,65.8,38.2,55.9,38.2
z M55.9,66.4c-5.7,0-10.3-4.6-10.3-10.3c-0.1-5.7,4.6-10.3,10.3-10.3c5.7,0,10.3,4.6,10.3,10.3C66.2,61.8,61.6,66.4,55.9,66.4z"></path><path fill="#FFFFFF" d="M74.3,33.5c-2.3,0-4.2,1.9-4.2,4.2s1.9,4.2,4.2,4.2s4.2-1.9,4.2-4.2S76.6,33.5,74.3,33.5z"></path><path fill="#FFFFFF" d="M73.1,21.3H38.6c-9.7,0-17.5,7.9-17.5,17.5v34.5c0,9.7,7.9,17.6,17.5,17.6h34.5c9.7,0,17.5-7.9,17.5-17.5V38.8
C90.6,29.1,82.7,21.3,73.1,21.3z M83,73.3c0,5.5-4.5,9.9-9.9,9.9H38.6c-5.5,0-9.9-4.5-9.9-9.9V38.8c0-5.5,4.5-9.9,9.9-9.9h34.5
c5.5,0,9.9,4.5,9.9,9.9V73.3z"></path></svg></span>
          </a>
        </div>
        <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/images/textlogowhite.png" alt="" data-image-width="2000" data-image-height="483">
      </div></footer></body>

    
    <!--Checkout Code taken from razorpay documentation-->
    <script>
        // Checkout details as a json
        var options = <?php echo $json?>;
        
        /**
         * The entire list of Checkout fields is available at
         * https://docs.razorpay.com/docs/checkout-form#checkout-fields
         */
        options.handler = function (response){
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
        
            document.razorpayform.submit();
        };
        
        // Boolean whether to show image inside a white frame. (default: true)
        options.theme.image_padding = false;
        
        options.modal = {
            ondismiss: function() {
                console.log("This code runs when the popup is closed");
            },
            // Boolean indicating whether pressing escape key 
            // should close the checkout form. (default: true)
            escape: true,
            // Boolean indicating whether clicking translucent blank
            // space outside checkout form should close the form. (default: false)
            backdropclose: false
        };
        
        var rzp = new Razorpay(options);
        
        document.getElementById('rzp-button1').onclick = function(e){
            // console.log('rap.open')
            rzp.open();
            e.preventDefault();
        }
    </script>

  
</html>