
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
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/nicepage1.css"/>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/Home1.css"/>
    <!--<link rel="stylesheet" href="<?php //echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Paypage/nicepage.css" media="screen">-->
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
  <body class="u-body">
     <header class="u-align-center-sm u-align-center-xs u-clearfix u-custom-color-3 u-header u-sticky u-sticky-46cd u-header" id="sec-ef8f"><div class="u-clearfix u-sheet u-valign-middle-md u-valign-middle-sm u-valign-middle-xs u-sheet-1">
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
</li><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="#" style="padding: 8px 22px;">Projects</a><div class="u-nav-popup"><ul class="u-border-2 u-border-custom-color-4 u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-2"><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="project-1/">Eco Villages</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="project-4/">Rejuvenate Lakes</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="mangrove-forests/">Mangrove Forests</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="climate-friendly/">Climate Friendly Tech</a>
</li></ul>
</div>
</li><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="#" style="padding: 8px 22px;">Campaigns</a><div class="u-nav-popup"><ul class="u-border-2 u-border-custom-color-4 u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-3"><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="smokeless-cookstoves/">India's First Eco Village</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="smokeless-cookstoves/">Smokeless Cookstoves</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="fund-these-eco-village/">Plant a Mangrove</a>
</li></ul>
</div>
</li><li class="u-nav-item"><a class="u-border-1 u-border-active-palette-1-base u-border-hover-palette-1-light-1 u-border-no-left u-border-no-right u-border-no-top u-button-style u-nav-link u-text-active-grey-15 u-text-grey-15 u-text-hover-custom-color-4" href="profile" style="padding: 8px 22px;">Account</a><div class="u-nav-popup"><ul class="u-border-2 u-border-custom-color-4 u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-4"><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="profile/">Profile</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4 u-white" href="wp-login.php?action=logout">Logout</a>
</li></ul>
</div>
</li></ul>
          </div>
          <div class="u-custom-menu u-nav-container-collapse">
            <div class="u-align-center u-black u-container-style u-inner-container-layout u-opacity u-opacity-95 u-sidenav">
              <div class="u-sidenav-overflow">
                <div class="u-menu-close"></div>
                <ul class="u-align-center u-nav u-popupmenu-items u-unstyled u-nav-5"><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com" style="padding: 8px 22px;">Home</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/about/" style="padding: 8px 22px;">About</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#" style="padding: 8px 22px;">Projects</a><div class="u-nav-popup"><ul class="u-border-2 u-border-custom-color-4 u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-6"><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="project-1/">Eco Villages</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="project-4/">Rejuvenate Lakes</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="mangrove-forests/">Mangrove Forests</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="climate-friendly/">Climate Friendly Tech</a>
</li></ul>
</div>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="#" style="padding: 8px 22px;">Campaigns</a><div class="u-nav-popup"><ul class="u-border-2 u-border-custom-color-4 u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-7"><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="smokeless-cookstoves/">India's First Eco Village</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="smokeless-cookstoves/">Smokeless Cookstoves</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="fund-these-eco-village/">Plant a Mangrove</a>
</li></ul>
</div>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="profile" style="padding: 8px 22px;">Account</a><div class="u-nav-popup"><ul class="u-border-2 u-border-custom-color-4 u-h-spacing-48 u-nav u-unstyled u-v-spacing-26 u-nav-8"><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="profile/">Profile</a>
</li><li class="u-nav-item"><a class="u-button-style u-nav-link u-text-hover-custom-color-4" href="wp-login.php?action=logout">Logout</a>
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
        <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/assets/images/textlogowhite.png" alt="lowsoot icon" data-image-width="2000" data-image-height="483">
      </div></footer>
      </body>

    
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