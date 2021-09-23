<?php
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
//$_SESSION['price'] = $price;
$customername = (isset($current_user->user_nicename))?$current_user->user_nicename:'';
$email = (isset($current_user->user_email))?$current_user->user_email:'';
//$_SESSION['email'] = $email;
$contactno ='';
$orderData = [
    //'receipt'         => 3456,
    'amount'          => $price * 50, // 2000 rupees in paise
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
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/nicepage.css" media="screen">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/Home.css" media="screen">
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/jquery.js" defer=""></script>
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/nicepage.js" defer=""></script>
    <meta name="generator" content="Nicepage 3.22.0, nicepage.com">
    <link id="u-theme-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i|Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i">
    <link id="u-page-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Fjalla+One:400">
    
    
    
    
    
    
    
    
    <script type="application/ld+json">{
		"@context": "http://schema.org",
		"@type": "Organization",
		"name": "",
		"logo": "<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/images/lowsoot.png",
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
    <!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '953352402128458');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=953352402128458&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
  </head>
  <body data-home-page="Home.html" data-home-page-title="Home" class="u-body"><header class="u-align-center-sm u-align-center-xs u-clearfix u-custom-color-1 u-header u-header" id="sec-ef8f"><div class="u-clearfix u-sheet u-valign-middle-lg u-sheet-1">
        <a href="<?php echo get_home_url(); ?>" class="u-image u-logo u-image-1" data-image-width="968" data-image-height="1028">
          <img src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/images/lowsoot.png" class="u-logo-image u-logo-image-1">
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
            <a class="u-button-style u-custom-active-color u-custom-text-active-color u-custom-text-color u-nav-link" href="#" style="padding: 11px 4px; font-size: calc(1em + 22px);">
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
</li><li class="u-nav-item"><a class="u-border-active-palette-1-base u-border-hover-palette-1-base u-button-style u-nav-link u-text-active-white u-text-body-alt-color u-text-hover-grey-90" href="https://lowsoot.com/calculator" style="padding: 10px 46px;">Calculator</a>
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
</li><li class="u-nav-item"><a class="u-button-style u-nav-link" href="https://lowsoot.com/calculator" style="padding: 10px 46px;">Calculator</a>
</li></ul>
              </div>
            </div>
            <div class="u-black u-menu-overlay u-opacity u-opacity-70"></div>
          </div>
        </nav>
      </div></header> 
    <section class="u-align-center u-clearfix u-grey-5 u-section-1" id="sec-56a8">
      <div class="u-clearfix u-sheet u-sheet-1" id="pay">
        <div class="u-align-center-md u-align-center-sm u-align-center-xs u-align-left-lg u-align-left-xl u-container-style u-expanded-width-xs u-group u-shape-rectangle u-group-1">
          <div class="u-container-layout u-container-layout-1">
            <p class="u-text u-text-1">Your Annual Carbon Footprint</p>
            <h1 class="u-text u-text-custom-color-1 u-text-2"><?php echo $Final['calculation_value']??'' ?> <?php echo $Final['suffix']??'' ?></h1>
            <p class="u-text u-text-3">Your individual carbon emissions can&nbsp;<br>be erased by investing in<br>&nbsp;lowsoot's projects.
            </p>
            <p class="u-text u-text-4">Based on your emissions&nbsp;<br>you can pay <br>&nbsp;
            </p>
            <p class="u-text u-text-palette-2-base u-text-5">INR <?php echo $price??''; ?></p>
            
        
            
            <button id="rzp-button1" class="u-active-palette-2-dark-3 u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-font u-font-montserrat u-hover-palette-2-dark-3 u-palette-2-base u-radius-8 u-text-active-white u-text-body-alt-color u-text-hover-white u-btn-1">Erase Now1</button>
            
            <p class="u-custom-font u-font-montserrat u-text u-text-6">
              <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-palette-1-base u-btn-2" href="#FAQ">Have questions?&nbsp;<br>Visit our FAQ section to know more
              </a>
            </p>
          </div>
        </div>
        <div class="u-clearfix u-expanded-width-md u-expanded-width-sm u-expanded-width-xs u-gutter-18 u-layout-wrap u-layout-wrap-1">
          <div class="u-gutter-0 u-layout">
            <div class="u-layout-row">
              <div class="u-size-30">
                <div class="u-layout-col">
                  <div class="u-align-center u-container-style u-layout-cell u-radius-10 u-size-40 u-white u-layout-cell-1">
                    <div class="u-container-layout u-container-layout-2"><span class="u-custom-color-1 u-icon u-icon-circle u-opacity u-opacity-55 u-spacing-18 u-text-white u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 511.995 511.995" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-9e87"></use></svg><svg class="u-svg-content" viewBox="0 0 511.995 511.995" id="svg-9e87"><g><path d="m509.413 170.169c-3.907-10.734-11.761-19.306-22.114-24.133-37.48-17.478-79.522-19.312-118.383-5.169l-40.603 14.778-115.882-57.353c-3.673-1.817-7.933-2.054-11.784-.652l-69.188 25.183c-5.225 1.902-8.969 6.537-9.729 12.045s1.589 10.984 6.104 14.23l73.43 52.791-102.049 37.142-37.867-30.704c-4.093-3.318-9.627-4.247-14.578-2.444l-36.9 13.431c-3.738 1.361-6.783 4.151-8.464 7.757s-1.861 7.73-.5 11.469l23.504 64.576c7.648 21.014 23.022 37.792 43.29 47.242 20.268 9.452 43.002 10.444 64.016 2.796l89.902-32.722-19.386 76.126c-1.372 5.388.349 11.093 4.471 14.823 2.804 2.537 6.406 3.879 10.067 3.878 1.721 0 3.457-.296 5.128-.904l69.189-25.183c3.852-1.402 6.962-4.319 8.608-8.074l46.456-105.993 137.703-50.119c22.159-8.067 33.625-32.657 25.559-54.817zm-341.023-28.863 36.486-13.279 84.465 41.804-53.958 19.639zm305.202 55.488-143.734 52.314c-3.852 1.402-6.962 4.319-8.608 8.074l-46.456 105.993-36.487 13.28 19.386-76.126c1.372-5.388-.349-11.093-4.471-14.823-2.804-2.537-6.406-3.878-10.067-3.878-1.722 0-3.458.297-5.128.904l-116.572 42.429c-13.484 4.908-28.072 4.271-41.077-1.793s-22.87-16.83-27.777-30.313l-18.374-50.481 14.824-5.396 37.867 30.704c4.092 3.317 9.626 4.245 14.578 2.444l277.682-101.068c31.331-11.404 65.227-9.924 95.444 4.167 3.09 1.441 5.435 4 6.602 7.205 2.406 6.616-1.017 13.957-7.632 16.364z"></path>
</g></svg></span>
                      <h3 class="u-text u-text-default u-text-grey-60 u-text-7">Travel&nbsp;<br>Emissions
                      </h3>
                      <h1 class="u-text u-text-custom-color-1 u-text-8"><?php echo $Travel['calculation_value']??'' ?> <?php echo $Travel['suffix']??'' ?></h1>
                      
                    </div>
                  </div>
                  <div class="u-align-center u-container-style u-layout-cell u-radius-10 u-size-20 u-white u-layout-cell-2">
                    <div class="u-container-layout u-container-layout-3"><span class="u-custom-color-1 u-icon u-icon-circle u-opacity u-opacity-55 u-spacing-18 u-text-white u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 64 64" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-98d4"></use></svg><svg class="u-svg-content" viewBox="0 0 64 64" id="svg-98d4"><g id="Electric_Plug-2"><path d="M44.85,27.47A1,1,0,0,0,44,27H33V12a1,1,0,0,0-1.89-.45l-12,24A1,1,0,0,0,20,37H31V52a1,1,0,0,0,.77.97A.908.908,0,0,0,32,53a1,1,0,0,0,.89-.55l12-24A1.007,1.007,0,0,0,44.85,27.47ZM33,47.76V36a1,1,0,0,0-1-1H21.62L31,16.24V28a1,1,0,0,0,1,1H42.38Z"></path><path d="M56.41,14.58a4.988,4.988,0,0,0-.37-6.62L53.21,5.13a1.024,1.024,0,0,0-1.41,0l-.71.71L48.26,3.01,46.85,4.42l2.83,2.83L48.26,8.67,45.43,5.84,44.02,7.25l2.83,2.83-.71.71a1,1,0,0,0,0,1.41l2.83,2.83a4.936,4.936,0,0,0,5.9.84A27.678,27.678,0,0,1,60,32,28.025,28.025,0,0,1,21.38,57.92l-.76,1.85A30.025,30.025,0,0,0,62,32,29.669,29.669,0,0,0,56.41,14.58Zm-1.78-.96a3.03,3.03,0,0,1-4.25,0l-2.12-2.13,4.25-4.24,2.12,2.12A3.012,3.012,0,0,1,54.63,13.62Z"></path><path d="M32,2A30.037,30.037,0,0,0,2,32,29.669,29.669,0,0,0,7.59,49.42a4.988,4.988,0,0,0,.37,6.62l2.83,2.83a.967.967,0,0,0,.7.29.99.99,0,0,0,.71-.29l.71-.71,2.83,2.83,1.41-1.41-2.83-2.83,1.42-1.42,2.83,2.83,1.41-1.41-2.83-2.83.71-.71a1,1,0,0,0,0-1.41l-2.83-2.83a4.987,4.987,0,0,0-5.9-.84A27.678,27.678,0,0,1,4,32,28.031,28.031,0,0,1,32,4,27.733,27.733,0,0,1,42.62,6.09l.76-1.86A29.93,29.93,0,0,0,32,2ZM9.37,50.38a3.03,3.03,0,0,1,4.25,0l2.12,2.13-4.25,4.24L9.37,54.63A3.012,3.012,0,0,1,9.37,50.38Z"></path>
</g></svg></span>
                      <h3 class="u-text u-text-default u-text-grey-60 u-text-11">Utility<br>Emissions
                      </h3>
                      <h1 class="u-text u-text-custom-color-1 u-text-12"><?php echo $Utilities['calculation_value']??'' ?> <?php echo $Utilities['suffix']??'' ?></h1>
                      
                    </div>
                  </div>
                </div>
              </div>
              <div class="u-size-30">
                <div class="u-layout-col">
                  <div class="u-align-center u-container-style u-layout-cell u-radius-10 u-size-20 u-white u-layout-cell-3">
                    <div class="u-container-layout u-container-layout-4"><span class="u-custom-color-1 u-icon u-icon-circle u-opacity u-opacity-55 u-spacing-18 u-text-white u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-1c62"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" x="0px" y="0px" id="svg-1c62" style="enable-background:new 0 0 512 512;"><g><g><path d="M383.071,148.928c-3.905-3.904-10.237-3.904-14.143,0l-20,20c-3.905,3.905-3.905,10.237,0,14.143    c3.907,3.906,10.238,3.905,14.143,0l20-20C386.976,159.166,386.976,152.834,383.071,148.928z"></path>
</g>
</g><g><g><path d="M143.071,148.928c-3.905-3.904-10.237-3.904-14.143,0l-20,20c-3.905,3.905-3.905,10.237,0,14.143    c3.907,3.906,10.238,3.905,14.143,0l20-20C146.976,159.166,146.976,152.834,143.071,148.928z"></path>
</g>
</g><g><g><path d="M333.071,108.928c-3.905-3.904-10.237-3.904-14.143,0l-20,20c-3.905,3.905-3.905,10.237,0,14.143    c3.907,3.906,10.238,3.905,14.143,0l20-20C336.976,119.166,336.976,112.834,333.071,108.928z"></path>
</g>
</g><g><g><path d="M213.071,108.928c-3.905-3.904-10.237-3.904-14.143,0l-20,20c-3.905,3.905-3.905,10.237,0,14.143    c3.907,3.906,10.238,3.905,14.143,0l20-20C216.976,119.166,216.976,112.834,213.071,108.928z"></path>
</g>
</g><g><g><path d="M263.071,148.928c-3.905-3.904-10.237-3.904-14.143,0l-20,20c-3.905,3.905-3.905,10.237,0,14.143    c3.907,3.906,10.238,3.905,14.143,0l20-20C266.976,159.166,266.976,152.834,263.071,148.928z"></path>
</g>
</g><g><g><path d="M512,356c0-16.542-13.458-30-30-30h-10v-32.23c0.412-0.358,0.832-0.701,1.237-1.07c6.491-5.917,20.049-9.7,28.889-9.812    c5.522-0.069,9.943-4.603,9.873-10.125c-0.069-5.479-4.532-9.874-9.997-9.874c-0.042,0-0.086,0-0.128,0.001    c-8.581,0.108-19.982,2.386-29.874,7.063V243.74c12.176-4.983,20.271-17.519,18.533-31.435C478.737,117.496,397.686,46,302,46h-92    c-95.686,0-176.737,71.496-188.533,166.301c-1.733,13.875,6.307,26.437,18.533,31.44v25.771    c-9.929-4.498-21.357-6.748-30.131-6.622c-5.522,0.072-9.94,4.608-9.868,10.131c0.072,5.478,4.535,9.868,9.997,9.868    c0.044,0,0.089,0,0.134-0.001c8.942-0.137,22.822,3.599,29.592,9.774c0.09,0.083,0.186,0.161,0.277,0.243V326H30    c-16.574,0-30,13.424-30,30c0,14.865,10.87,27.233,25.078,29.589c-2.337,6.169-2.505,12.909-0.338,19.313    C37.134,441.446,71.397,466,110,466h292c38.603,0,72.866-24.554,85.263-61.104c2.165-6.398,1.997-13.135-0.339-19.303    C500.864,383.301,512,371.17,512,356z M41.313,214.774C51.867,129.959,124.386,66,210,66h92    c85.614,0,158.133,63.959,168.687,148.779c0.722,5.779-3.787,10.811-9.356,11.184c-0.066,0.004-0.132,0.01-0.198,0.016    c-0.171,0.015-0.342,0.021-0.513,0.021H51.38C45.49,226,40.56,220.814,41.313,214.774z M413.238,277.92    c-21.975-20.039-51.502-20.039-73.477,0c-14.349,13.084-32.175,13.084-46.523,0c-21.975-20.039-51.502-20.039-73.477,0    c-14.349,13.084-32.175,13.084-46.523,0c-21.975-20.038-51.502-20.04-73.479,0.002C87.598,289.02,73.029,290.781,60,282.975V246    h392v37.555C439.241,290.654,425.047,288.689,413.238,277.92z M452,305.243V326H60v-21.057c5.407,1.871,10.956,2.807,16.5,2.806    c12.905,0,25.776-5.048,36.739-15.051c14.348-13.084,32.175-13.084,46.523,0c21.975,20.041,51.503,20.04,73.477,0    c14.349-13.084,32.176-13.084,46.523,0c21.975,20.041,51.503,20.04,73.477,0c14.348-13.085,32.175-13.083,46.523,0    C415.059,306.65,434.265,310.938,452,305.243z M468.32,398.479C458.68,426.902,432.028,446,402,446H110    c-30.028,0-56.68-19.098-66.317-47.515C41.608,392.355,46.901,386,53.46,386h405.08C465.123,386,470.382,392.385,468.32,398.479z     M482,366H30c-5.514,0-10-4.486-10-10c0-5.521,4.479-10,10-10h452c5.514,0,10,4.486,10,10C492,361.521,487.521,366,482,366z"></path>
</g>
</g></svg></span>
                      <h3 class="u-text u-text-default u-text-grey-70 u-text-15">Food&nbsp;<br>Emissions
                      </h3>
                      <h1 class="u-text u-text-custom-color-1 u-text-16"><?php echo $Food['calculation_value']??'' ?> <?php echo $Food['suffix']??'' ?></h1>
                     
                    </div>
                  </div>
                  <div class="u-container-style u-layout-cell u-radius-10 u-size-40 u-white u-layout-cell-4">
                    <div class="u-container-layout u-valign-top u-container-layout-5"><span class="u-custom-color-1 u-icon u-icon-circle u-opacity u-opacity-55 u-spacing-18 u-text-white u-icon-4"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-fad9"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" x="0px" y="0px" id="svg-fad9" style="enable-background:new 0 0 512 512;"><g><g><path d="M363.911,185.257c-3.305-3.331-7.04-6.071-11.074-8.184c-6.321-25.707-25.875-45.946-50.597-53.669    C313.316,110.33,320.015,93.437,320.015,75c0-41.355-33.645-75-75-75c-41.355,0-75,33.645-75,75    c0,18.421,6.688,35.301,17.747,48.371c-30.524,9.503-52.747,38.02-52.747,71.629v156h36.748l20,161h106.504l20-161h36.748v-93.05    l8.896-8.966C381.343,231.415,381.343,202.827,363.911,185.257z M245.015,30c24.813,0,45,20.187,45,45s-20.187,45-45,45    s-45-20.187-45-45S220.202,30,245.015,30z M271.763,482h-53.496l-16.273-131h86.042L271.763,482z M325.015,321h-160V195    c0-24.813,20.187-45,45-45h70c16.949,0,32.021,9.541,39.675,23.767c-3.008,0.867-5.917,2.031-8.675,3.512    c-6.422-3.45-13.627-5.28-21.115-5.28c-0.004,0-0.011,0-0.015,0c-12.006,0.004-23.286,4.712-31.766,13.257    c-17.432,17.569-17.432,46.157,0,63.727l52.896,53.313l14-14.111V321z M342.614,227.854l-31.599,31.849l-31.599-31.849    c-5.873-5.918-5.873-15.548-0.001-21.467c2.807-2.828,6.528-4.386,10.479-4.387c0.002,0,0.003,0,0.005,0    c3.949,0,7.669,1.555,10.475,4.379l10.641,10.71l10.642-10.711c2.805-2.824,6.524-4.378,10.474-4.378c0.001,0,0.003,0,0.005,0    c3.951,0.001,7.673,1.559,10.479,4.387C348.487,212.306,348.487,221.936,342.614,227.854z"></path>
</g>
</g></svg></span>
                      <a href="#" class="u-border-none u-btn u-btn-round u-button-style u-custom-color-1 u-radius-50 u-btn-6">Free</a>
                      <h3 class="u-align-center u-text u-text-default u-text-grey-60 u-text-19">Pledge Now</h3>
                      <ul class="u-align-left u-text u-text-palette-5-dark-2 u-text-20">
                        <li>Plant 10 Trees anually</li>
                        <li>Reduce 2 Flights in an year</li>
                        <li>Switch to Electric Vehicle</li>
                        <li>Reduce meat by 20 KGs anually</li>
                      </ul>
                      <a href="javascript:void(0)"  id="rzp-button2" class="u-active-none u-align-center u-border-2 u-border-active-palette-1-light-1 u-border-hover-palette-1-light-1 u-border-palette-2-base u-btn u-button-style u-hover-none u-none u-text-body-color u-btn-7">I cant do this</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-clearfix u-hidden-xs u-section-2" id="carousel_0368">
      <div class="u-container-style u-group u-shape-rectangle u-group-1">
        <div class="u-container-layout u-valign-top u-container-layout-1">
          <h1 class="u-custom-font u-font-montserrat u-text u-text-1">Our Projects</h1>
          <p class="u-text u-text-2">Erase your Carbon Footprint By Funding one of our Green Projects</p>
        </div>
      </div>
      <div class="u-custom-color-1 u-expanded-width-xs u-hidden-xs u-shape u-shape-rectangle u-shape-1"></div>
      <div class="u-hidden-xs u-list u-list-1">
        <div class="u-repeater u-repeater-1">
          <div class="u-container-style u-list-item u-repeater-item">
            <div id="projects" class="u-container-layout u-similar-container u-valign-bottom-lg u-valign-bottom-md u-valign-bottom-sm u-valign-bottom-xl u-container-layout-2">
              <img alt="" class="u-expanded-width-lg u-expanded-width-md u-expanded-width-xl u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/images/worker-building-roof-house_23-2148748850.jpg">
              <h3 class="u-custom-font u-text u-text-white u-text-3"> Large Scale Afforestation</h3>
              <p class="u-text u-text-white u-text-4">A roadmap for the regeneration of India’s forest cover by safest and trackable investment plans to become carbon neutral.</p>
              <a href="#pay" class="u-border-none u-btn u-button-style u-palette-2-dark-1 u-btn-1"> Erase Now</a>
            </div>
          </div>
          <div class="u-container-style u-list-item u-repeater-item">
            <div class="u-container-layout u-similar-container u-valign-bottom-lg u-valign-bottom-md u-valign-bottom-sm u-valign-bottom-xl u-container-layout-3">
              <img alt="" class="u-expanded-width-lg u-expanded-width-md u-expanded-width-xl u-image u-image-default u-image-2" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/images/man-working-roof-with-drill_23-21487487741.jpg">
              <h3 class="u-custom-font u-text u-text-white u-text-5"> Community Tree Plantation</h3>
              <p class="u-text u-text-white u-text-6">Engage with landowners, communities, businesses and other stakeholders to improve their local environment</p>
              <a href="#pay" id="rzp-button2" class="u-border-none u-btn u-button-style u-palette-2-dark-1 u-btn-2">Erase Now</a>
            </div>
          </div>
        </div>
      </div>
      <div class="u-list u-list-2">
        <div class="u-repeater u-repeater-2">
          <div class="u-align-left u-container-style u-list-item u-repeater-item u-shape-rectangle">
            <div class="u-container-layout u-similar-container u-container-layout-4">
              <h4 class="u-text u-text-7">Afforestation</h4>
              <p class="u-text u-text-8">Forest restoration and conservation measures</p>
              <a href="https://lowsoot.com/project-1/" class="u-border-1 u-border-black u-border-hover-palette-2-base u-btn u-btn-rectangle u-button-style u-none u-text-body-color u-text-hover-palette-2-base u-btn-3">learn more</a>
            </div>
          </div>
          <div class="u-align-left u-container-style u-list-item u-repeater-item u-shape-rectangle u-video-cover">
            <div class="u-container-layout u-similar-container u-container-layout-5">
              <h4 class="u-text u-text-9">Community Plantation</h4>
              <p class="u-text u-text-10"> Trees remove and store carbon from the atmosphere</p>
              <a href="https://lowsoot.com/project-2-community-tree-plantation/" class="u-border-1 u-border-black u-border-hover-palette-2-base u-btn u-btn-rectangle u-button-style u-none u-text-body-color u-text-hover-palette-2-base u-btn-4">learn more</a>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-center u-clearfix u-hidden-lg u-hidden-md u-hidden-sm u-hidden-xl u-palette-5-dark-3 u-section-3" id="carousel_aff6">
      <div class="u-clearfix u-sheet u-valign-middle-lg u-valign-middle-md u-valign-middle-sm u-valign-middle-xl u-sheet-1">
        <h1 class="u-text u-text-body-alt-color u-text-default u-text-1"> Our Projects</h1>
        <p class="u-text u-text-default u-text-2"> Erase your Carbon Footprint By Funding one of our Green Projects</p>
        <div class="u-expanded-width u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-align-center u-container-style u-list-item u-radius-15 u-repeater-item u-shape-round u-white u-list-item-1">
              <div class="u-container-layout u-similar-container u-valign-bottom-xs u-container-layout-1">
                <div class="u-border-6 u-border-white u-image u-image-circle u-image-1" alt="" data-image-width="416" data-image-height="626"></div>
                <h5 class="u-custom-font u-font-raleway u-text u-text-default u-text-palette-2-base u-text-3"> Large Scale Afforestation</h5>
                <p class="u-text u-text-4"> A roadmap for the regeneration of India’s forest cover by safest and trackable investment plans to become carbon neutral.</p>
                <a href="https://lowsoot.com/project-1/" class="u-active-palette-5-dark-2 u-border-none u-btn u-btn-round u-button-style u-grey-15 u-hover-palette-5-dark-2 u-radius-50 u-text-active-white u-text-body-color u-text-hover-white u-btn-1">Learn More</a>
              </div>
            </div>
            <div class="u-align-center u-container-style u-list-item u-radius-15 u-repeater-item u-shape-round u-white u-list-item-2">
              <div class="u-container-layout u-similar-container u-valign-bottom-xs u-container-layout-2">
                <div class="u-border-6 u-border-white u-image u-image-circle u-image-2" alt="" data-image-width="416" data-image-height="626"></div>
                <h5 class="u-custom-font u-font-raleway u-text u-text-default u-text-palette-2-base u-text-5"> Community Tree Plantation</h5>
                <p class="u-text u-text-6"> Engage with landowners, communities, businesses and other stakeholders to improve their local environment</p>
                <a href="https://lowsoot.com/project-2-community-tree-plantation/" class="u-active-palette-5-dark-2 u-border-none u-btn u-btn-round u-button-style u-grey-15 u-hover-palette-5-dark-2 u-radius-50 u-text-active-white u-text-body-color u-text-hover-white u-btn-2"> Learn more</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-left u-clearfix u-grey-5 u-section-4" id="carousel_763d">
      <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
        <h2 class="u-custom-font u-font-montserrat u-text u-text-1"><b>Lowsoot allows you to erase your emissions by contributing to world-class climate programmes.</b>
        </h2>
        <div class="u-border-16 u-border-custom-color-1 u-line u-line-horizontal u-line-1"></div>
        <div class="u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-align-left u-container-style u-custom-background u-list-item u-repeater-item u-white u-list-item-1">
              <div class="u-container-layout u-similar-container u-container-layout-1"><span class="u-align-left u-border-2 u-border-custom-color-1 u-icon u-icon-circle u-spacing-10 u-text-custom-color-1 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 128 128" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-14eb"></use></svg><svg class="u-svg-content" viewBox="0 0 128 128" id="svg-14eb"><path d="m116.5 74.206a3.281 3.281 0 0 0 -3.052-2.054h-1.7a16.89 16.89 0 0 0 -8.221 2.143l-12.415 6.942h-3.541a9.8 9.8 0 0 0 -9.63-8.036h-22.361a1.754 1.754 0 0 0 -1.338.621l-6.775 8.03a1.738 1.738 0 0 0 -1.321-.615h-33.146a1.75 1.75 0 0 0 0 3.5h21.38v25.081h-21.38a1.75 1.75 0 0 0 0 3.5h33.146a1.75 1.75 0 0 0 1.75-1.75v-8.392l35.688-1.638c.035 0 .069 0 .1-.008a25.208 25.208 0 0 0 14.907-7l17.157-16.729a3.282 3.282 0 0 0 .752-3.595zm-72.1 35.612h-6.52v-25.081h6.52zm51.751-17.789a21.69 21.69 0 0 1 -12.779 6.015l-35.472 1.629v-12.9l8.5-10.071h21.541a6.3 6.3 0 0 1 6.14 4.95l-17.733 4.353a1.75 1.75 0 0 0 .415 3.449 1.728 1.728 0 0 0 .419-.051l19.006-4.666h5.38a1.749 1.749 0 0 0 .854-.222l12.814-7.165a13.37 13.37 0 0 1 6.513-1.7h1.2z"></path><path d="m82.316 66.554a25.936 25.936 0 1 0 -25.936-25.936 25.966 25.966 0 0 0 25.936 25.936zm0-48.372a22.436 22.436 0 1 1 -22.436 22.436 22.462 22.462 0 0 1 22.436-22.436z"></path><path d="m77.72 46.979a1.75 1.75 0 0 0 -3.5 0 8.122 8.122 0 0 0 6.343 7.912v2.942a1.75 1.75 0 1 0 3.5 0v-2.933a8.106 8.106 0 0 0 0-15.841v-9.071a4.614 4.614 0 0 1 2.88 4.268 1.75 1.75 0 0 0 3.5 0 8.122 8.122 0 0 0 -6.38-7.921v-2.935a1.75 1.75 0 0 0 -3.5 0v2.943a8.105 8.105 0 0 0 0 15.823v9.068a4.618 4.618 0 0 1 -2.843-4.255zm9.223 0a4.613 4.613 0 0 1 -2.88 4.268v-8.535a4.612 4.612 0 0 1 2.88 4.267zm-9.223-12.723a4.615 4.615 0 0 1 2.843-4.256v8.513a4.619 4.619 0 0 1 -2.843-4.257z"></path></svg></span>
                <h5 class="u-text u-text-default-lg u-text-default-md u-text-default-xl u-text-2"> Lowsoot Climate FunD</h5>
                <p class="u-text u-text-3"> CO2 emissions can be erased through Lowsoot Climate Fund, which invests in high-quality, well-established climate initiatives.</p>
                <div class="u-custom-color-1 u-expanded-height u-shape u-shape-rectangle u-shape-1"></div>
              </div>
            </div>
            <div class="u-align-left u-container-style u-list-item u-repeater-item u-white u-list-item-2">
              <div class="u-container-layout u-similar-container u-container-layout-2"><span class="u-align-left u-border-2 u-border-custom-color-1 u-icon u-icon-circle u-spacing-10 u-text-custom-color-1 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-e641"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" x="0px" y="0px" id="svg-e641" style="enable-background:new 0 0 512 512;"><g><g><path d="M421.054,414.843c-4.142,0-7.5,3.358-7.5,7.5v70.514c0,2.283-1.858,4.141-4.141,4.141h-40.317V349.301    c0-4.142-3.358-7.5-7.5-7.5c-4.142,0-7.5,3.358-7.5,7.5v147.698h-81.185l23.543-25.9c2.572-2.83,3.785-6.861,3.244-10.787    c-0.01-0.076-0.022-0.152-0.035-0.228L277.24,327.617l6.041-9.094c3.34,2.372,5.913,4.656,10.738,4.656    c4.908,0,9.497-2.747,11.755-7.269v-0.001l23.65-47.4l53.876,20.865c1.949,0.836,30.252,13.582,30.252,47.238v50.73    c-0.001,4.141,3.357,7.5,7.5,7.5c4.142,0,7.5-3.358,7.5-7.5v-50.73c0-44.344-37.969-60.463-39.585-61.128    c-0.047-0.02-0.095-0.039-0.143-0.057l-89.668-34.726v-21.03c14.242-11.076,24.117-27.495,26.596-46.227    c7.101-0.5,13.69-3.152,19.071-7.779c7.027-6.043,11.059-14.838,11.059-24.126c0-7.708-2.781-15.068-7.737-20.803V92.953    C348.144,41.699,306.446,0,255.192,0c-51.254,0-92.952,41.699-92.952,92.953v28.511c-5.009,5.677-7.733,12.665-7.733,20.074    c0,9.291,4.03,18.085,11.059,24.129c5.377,4.625,11.962,7.274,19.061,7.775c2.499,19.083,12.662,36.114,28.117,47.339v19.92    l-89.571,34.725c-0.047,0.018-0.094,0.037-0.141,0.056c-1.617,0.665-39.585,16.784-39.585,61.128v156.245    c0,10.555,8.587,19.142,19.142,19.142h71.457c4.142,0,7.5-3.358,7.5-7.5c0-4.142-3.358-7.5-7.5-7.5h-16.137V349.301    c0-4.142-3.358-7.5-7.5-7.5c-4.142,0-7.5,3.358-7.5,7.5v147.698h-40.319c-2.283,0-4.141-1.858-4.141-4.141V336.611    c0-33.769,28.493-46.486,30.243-47.234l53.834-20.87l23.652,47.402c2.263,4.533,6.858,7.27,11.756,7.27    c4.801,0,7.349-2.249,10.738-4.656l6.041,9.094l-22.421,132.468c-0.013,0.075-0.024,0.15-0.035,0.226    c-0.542,3.924,0.671,7.957,3.244,10.789l23.543,25.9h-29.995c-4.142,0-7.5,3.358-7.5,7.5s3.358,7.5,7.5,7.5h200.365    c10.555,0,19.142-8.588,19.142-19.142v-70.514C428.554,418.201,425.196,414.843,421.054,414.843z M315.375,263.069l-22.049,44.19    c-0.548-0.389-12.233-8.691-26.517-18.834c6.198-7.651-1.053,1.299,27.235-33.617L315.375,263.069z M271.043,309.833l-5.718,8.607    h-18.703l-5.718-8.607l15.07-10.703L271.043,309.833z M227.743,243.121v-14.036c9.112,3.673,18.85,5.376,28.36,5.376    c9.833,0,19.476-2.096,28.052-5.846v14.567l-28.181,34.785L227.743,243.121z M340.881,141.539    c-0.001,4.913-2.129,9.562-5.839,12.753c-2.453,2.11-5.416,3.459-8.661,3.987v-33.477    C335.001,126.202,340.881,133.352,340.881,141.539z M184.007,158.279c-8.718-1.415-14.5-8.623-14.5-16.741    c0-8.018,6.647-14.544,14.5-16.359V158.279z M184.41,109.896c-2.389,0.274-5.127,0.921-7.168,1.615V92.953    c0-42.983,34.968-77.952,77.951-77.952c42.983,0,77.951,34.969,77.951,77.952v18.043c-2.18-0.663-4.441-1.101-6.762-1.307    c0-7.237,0.063-5.841-23.612-31.294c-4.354-4.678-11.556-5.658-17.037-2.077c-26.13,17.069-58.005,25.644-87.415,23.532    C191.867,99.367,185.991,103.616,184.41,109.896z M199.008,164.184v-46.792v-2.465c32.375,1.896,66.318-7.722,93.739-25.283    c10.858,11.658,16.738,17.773,18.634,20.099c0,5.884,0,47.705,0,54.44c0,30.447-24.826,55.276-55.277,55.276    C221.91,219.46,199.008,192.934,199.008,164.184z M218.623,307.259l-22.049-44.19l21.293-8.247l27.241,33.625    C231.255,298.284,219.88,306.366,218.623,307.259z M227.228,461.702l21.709-128.263h14.071l21.709,128.263l-28.744,31.623    L227.228,461.702z"></path>
</g>
</g></svg></span>
                <h5 class="u-text u-text-default-lg u-text-default-md u-text-default-xl u-text-4"> Your contribution</h5>
                <p class="u-text u-text-5"> Whatever you contribute, helps to generate green employment, restore local ecosystems, and preserve native land.</p>
                <div class="u-custom-color-1 u-expanded-height u-shape u-shape-rectangle u-shape-2"></div>
              </div>
            </div>
            <div class="u-align-left u-container-style u-list-item u-repeater-item u-white u-list-item-3">
              <div class="u-container-layout u-similar-container u-container-layout-3"><span class="u-align-left u-border-2 u-border-custom-color-1 u-icon u-icon-circle u-spacing-10 u-text-custom-color-1 u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 54 54" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-cbb1"></use></svg><svg class="u-svg-content" viewBox="0 0 54 54" x="0px" y="0px" id="svg-cbb1" style="enable-background:new 0 0 54 54;"><g><path d="M51.22,21h-5.052c-0.812,0-1.481-0.447-1.792-1.197s-0.153-1.54,0.42-2.114l3.572-3.571
		c0.525-0.525,0.814-1.224,0.814-1.966c0-0.743-0.289-1.441-0.814-1.967l-4.553-4.553c-1.05-1.05-2.881-1.052-3.933,0l-3.571,3.571
		c-0.574,0.573-1.366,0.733-2.114,0.421C33.447,9.313,33,8.644,33,7.832V2.78C33,1.247,31.753,0,30.22,0H23.78
		C22.247,0,21,1.247,21,2.78v5.052c0,0.812-0.447,1.481-1.197,1.792c-0.748,0.313-1.54,0.152-2.114-0.421l-3.571-3.571
		c-1.052-1.052-2.883-1.05-3.933,0l-4.553,4.553c-0.525,0.525-0.814,1.224-0.814,1.967c0,0.742,0.289,1.44,0.814,1.966l3.572,3.571
		c0.573,0.574,0.73,1.364,0.42,2.114S8.644,21,7.832,21H2.78C1.247,21,0,22.247,0,23.78v6.439C0,31.753,1.247,33,2.78,33h5.052
		c0.812,0,1.481,0.447,1.792,1.197s0.153,1.54-0.42,2.114l-3.572,3.571c-0.525,0.525-0.814,1.224-0.814,1.966
		c0,0.743,0.289,1.441,0.814,1.967l4.553,4.553c1.051,1.051,2.881,1.053,3.933,0l3.571-3.572c0.574-0.573,1.363-0.731,2.114-0.42
		c0.75,0.311,1.197,0.98,1.197,1.792v5.052c0,1.533,1.247,2.78,2.78,2.78h6.439c1.533,0,2.78-1.247,2.78-2.78v-5.052
		c0-0.812,0.447-1.481,1.197-1.792c0.751-0.312,1.54-0.153,2.114,0.42l3.571,3.572c1.052,1.052,2.883,1.05,3.933,0l4.553-4.553
		c0.525-0.525,0.814-1.224,0.814-1.967c0-0.742-0.289-1.44-0.814-1.966l-3.572-3.571c-0.573-0.574-0.73-1.364-0.42-2.114
		S45.356,33,46.168,33h5.052c1.533,0,2.78-1.247,2.78-2.78V23.78C54,22.247,52.753,21,51.22,21z M52,30.22
		C52,30.65,51.65,31,51.22,31h-5.052c-1.624,0-3.019,0.932-3.64,2.432c-0.622,1.5-0.295,3.146,0.854,4.294l3.572,3.571
		c0.305,0.305,0.305,0.8,0,1.104l-4.553,4.553c-0.304,0.304-0.799,0.306-1.104,0l-3.571-3.572c-1.149-1.149-2.794-1.474-4.294-0.854
		c-1.5,0.621-2.432,2.016-2.432,3.64v5.052C31,51.65,30.65,52,30.22,52H23.78C23.35,52,23,51.65,23,51.22v-5.052
		c0-1.624-0.932-3.019-2.432-3.64c-0.503-0.209-1.021-0.311-1.533-0.311c-1.014,0-1.997,0.4-2.761,1.164l-3.571,3.572
		c-0.306,0.306-0.801,0.304-1.104,0l-4.553-4.553c-0.305-0.305-0.305-0.8,0-1.104l3.572-3.571c1.148-1.148,1.476-2.794,0.854-4.294
		C10.851,31.932,9.456,31,7.832,31H2.78C2.35,31,2,30.65,2,30.22V23.78C2,23.35,2.35,23,2.78,23h5.052
		c1.624,0,3.019-0.932,3.64-2.432c0.622-1.5,0.295-3.146-0.854-4.294l-3.572-3.571c-0.305-0.305-0.305-0.8,0-1.104l4.553-4.553
		c0.304-0.305,0.799-0.305,1.104,0l3.571,3.571c1.147,1.147,2.792,1.476,4.294,0.854C22.068,10.851,23,9.456,23,7.832V2.78
		C23,2.35,23.35,2,23.78,2h6.439C30.65,2,31,2.35,31,2.78v5.052c0,1.624,0.932,3.019,2.432,3.64
		c1.502,0.622,3.146,0.294,4.294-0.854l3.571-3.571c0.306-0.305,0.801-0.305,1.104,0l4.553,4.553c0.305,0.305,0.305,0.8,0,1.104
		l-3.572,3.571c-1.148,1.148-1.476,2.794-0.854,4.294c0.621,1.5,2.016,2.432,3.64,2.432h5.052C51.65,23,52,23.35,52,23.78V30.22z"></path><path d="M27,18c-4.963,0-9,4.037-9,9s4.037,9,9,9s9-4.037,9-9S31.963,18,27,18z M27,34c-3.859,0-7-3.141-7-7s3.141-7,7-7
		s7,3.141,7,7S30.859,34,27,34z"></path>
</g></svg></span>
                <h5 class="u-text u-text-default-lg u-text-default-md u-text-default-xl u-text-6">Regular Updates</h5>
                <p class="u-text u-text-7"> Each month, you'll receive brief updates on your impact every month.</p>
                <div class="u-custom-color-1 u-expanded-height u-shape u-shape-rectangle u-shape-3"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-left u-black u-clearfix u-section-5" id="carousel_2515">
      <div class="u-clearfix u-sheet u-sheet-1">
        <div class="u-clearfix u-expanded-width u-gutter-0 u-layout-wrap u-layout-wrap-1">
          <div class="u-layout" style="">
            <div class="u-layout-row" style="">
              <div class="u-container-style u-layout-cell u-size-32-md u-size-32-sm u-size-32-xs u-size-35-lg u-size-35-xl u-layout-cell-1">
                <div class="u-container-layout u-valign-top u-container-layout-1">
                  <h2 class="u-text u-text-default u-text-1"><b>Learn more about what your membership supports</b>
                  </h2>
                  <h6 class="u-text u-text-default u-text-2"><b>Lowsoot's guarantees</b>
                  </h6>
                  <p class="u-text u-text-3"> Each project's impact is guaranteed by us. We'll let you know if a project doesn't offset as much as expected, and we'll support another initiative to make up the difference.<br>
                    <br>
                    <span style="font-weight: 700;">All Projects Are Trackable</span>&nbsp;<br>These projects' carbon offset amounts are based on peer-reviewed science.There is a clear and direct relationship between what we measure for a project—e.g. the diameter of a tree trunk—and how much carbon is being offset.<br>
                    <br><b></b><b>No Double Counting</b>
                    <br> We partner with projects that either have public ledgers that reveal who gets credit for which carbon offset removed, we are the project's sole buyer of carbon removed.<br>
                  </p>
                  <a href="#projects" class="u-active-white u-border-none u-btn u-button-style u-color-scheme-summer-time u-color-style-multicolor-1 u-custom-color-1 u-hover-white u-btn-1">Our Projects</a>
                </div>
              </div>
              <div class="u-container-style u-layout-cell u-size-25-lg u-size-25-xl u-size-28-md u-size-28-sm u-size-28-xs u-white u-layout-cell-2">
                <div class="u-container-layout u-container-layout-2">
                  <h2 class="u-text u-text-custom-color-1 u-text-4"><b>Here’s what you get..</b>
                  </h2>
                  <ul class="u-custom-list u-text u-text-custom-color-1 u-text-5">
                    <li style="margin-left: 1.6em;">
                      <div class="u-list-icon u-text-custom-color-1">
                        <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-26d7" style="font-size: 1.2em; margin: -1.2em;"><path d="m369.164062 174.769531c7.8125 7.8125 7.8125 20.476563 0 28.285157l-134.171874 134.175781c-7.8125 7.808593-20.472657 7.808593-28.285157 0l-63.871093-63.875c-7.8125-7.808594-7.8125-20.472657 0-28.28125 7.808593-7.8125 20.472656-7.8125 28.28125 0l49.730468 49.730469 120.03125-120.035157c7.8125-7.808593 20.476563-7.808593 28.285156 0zm142.835938 81.230469c0 141.503906-114.515625 256-256 256-141.503906 0-256-114.515625-256-256 0-141.503906 114.515625-256 256-256 141.503906 0 256 114.515625 256 256zm-40 0c0-119.394531-96.621094-216-216-216-119.394531 0-216 96.621094-216 216 0 119.394531 96.621094 216 216 216 119.394531 0 216-96.621094 216-216zm0 0"></path></svg>
                      </div><b>Offsets on a recurring basis through world-class projects&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</b>
                    </li>
                    <li style="margin-left: 1.6em;">
                      <div class="u-list-icon u-text-custom-color-1">
                        <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-26d7" style="font-size: 1.2em; margin: -1.2em;"><path d="m369.164062 174.769531c7.8125 7.8125 7.8125 20.476563 0 28.285157l-134.171874 134.175781c-7.8125 7.808593-20.472657 7.808593-28.285157 0l-63.871093-63.875c-7.8125-7.808594-7.8125-20.472657 0-28.28125 7.808593-7.8125 20.472656-7.8125 28.28125 0l49.730468 49.730469 120.03125-120.035157c7.8125-7.808593 20.476563-7.808593 28.285156 0zm142.835938 81.230469c0 141.503906-114.515625 256-256 256-141.503906 0-256-114.515625-256-256 0-141.503906 114.515625-256 256-256 141.503906 0 256 114.515625 256 256zm-40 0c0-119.394531-96.621094-216-216-216-119.394531 0-216 96.621094-216 216 0 119.394531 96.621094 216 216 216 119.394531 0 216-96.621094 216-216zm0 0"></path></svg>
                      </div><b>Personal dashboard to keep track of your performance.&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</b>
                    </li>
                    <li style="margin-left: 1.6em;">
                      <div class="u-list-icon u-text-custom-color-1">
                        <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-26d7" style="font-size: 1.2em; margin: -1.2em;"><path d="m369.164062 174.769531c7.8125 7.8125 7.8125 20.476563 0 28.285157l-134.171874 134.175781c-7.8125 7.808593-20.472657 7.808593-28.285157 0l-63.871093-63.875c-7.8125-7.808594-7.8125-20.472657 0-28.28125 7.808593-7.8125 20.472656-7.8125 28.28125 0l49.730468 49.730469 120.03125-120.035157c7.8125-7.808593 20.476563-7.808593 28.285156 0zm142.835938 81.230469c0 141.503906-114.515625 256-256 256-141.503906 0-256-114.515625-256-256 0-141.503906 114.515625-256 256-256 141.503906 0 256 114.515625 256 256zm-40 0c0-119.394531-96.621094-216-216-216-119.394531 0-216 96.621094-216 216 0 119.394531 96.621094 216 216 216 119.394531 0 216-96.621094 216-216zm0 0"></path></svg>
                      </div><b>Updates on your initiatives on a monthly basis that demonstrate your impact.&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp;</b>
                    </li>
                    <li style="margin-left: 1.6em;">
                      <div class="u-list-icon u-text-custom-color-1">
                        <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-26d7" style="font-size: 1.2em; margin: -1.2em;"><path d="m369.164062 174.769531c7.8125 7.8125 7.8125 20.476563 0 28.285157l-134.171874 134.175781c-7.8125 7.808593-20.472657 7.808593-28.285157 0l-63.871093-63.875c-7.8125-7.808594-7.8125-20.472657 0-28.28125 7.808593-7.8125 20.472656-7.8125 28.28125 0l49.730468 49.730469 120.03125-120.035157c7.8125-7.808593 20.476563-7.808593 28.285156 0zm142.835938 81.230469c0 141.503906-114.515625 256-256 256-141.503906 0-256-114.515625-256-256 0-141.503906 114.515625-256 256-256 141.503906 0 256 114.515625 256 256zm-40 0c0-119.394531-96.621094-216-216-216-119.394531 0-216 96.621094-216 216 0 119.394531 96.621094 216 216 216 119.394531 0 216-96.621094 216-216zm0 0"></path></svg>
                      </div><b>Guaranteed impact</b>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-left u-clearfix u-grey-5 u-section-6" id="carousel_abc8">
      <div class="u-custom-color-1 u-expanded-width u-shape u-shape-rectangle u-shape-1"></div>
      <h1 class="u-text u-text-body-alt-color u-text-default-lg u-text-default-md u-text-default-xl u-text-1"> What do our users say?<br>
      </h1>
      <div class="u-list u-list-1">
        <div class="u-repeater u-repeater-1">
          <div class="u-align-left u-container-style u-list-item u-radius-20 u-repeater-item u-shape-round u-white u-list-item-1">
            <div class="u-container-layout u-similar-container u-valign-top u-container-layout-1">
              <p class="u-text u-text-grey-50 u-text-2"> "It's a Great Initiative for contribution towards helping in reduction of Carbon di oxide. It's much needed initiative which we need."</p>
              <div alt="" class="u-image u-image-circle u-image-1" data-image-width="1200" data-image-height="1006"></div>
              <h5 class="u-text u-text-default u-text-grey-50 u-text-3">Maitri Bheda</h5>
              <h5 class="u-text u-text-body-color u-text-default u-text-4">Founder, Plants Academy</h5>
            </div>
          </div>
          <div class="u-align-left u-container-style u-list-item u-radius-20 u-repeater-item u-shape-round u-white">
            <div class="u-container-layout u-similar-container u-valign-top u-container-layout-2">
              <p class="u-text u-text-grey-50 u-text-5"> "Amazing cause. Detailed regular progress updates.&nbsp;<br>
                <br>Easy way to become carbon neutral."
              </p>
              <div alt="" class="u-image u-image-circle u-image-2" data-image-width="640" data-image-height="360"></div>
              <h5 class="u-text u-text-default u-text-grey-50 u-text-6">Jaiwardhan Saraf</h5>
              <h5 class="u-text u-text-body-color u-text-default u-text-7">Founder, E Food Project</h5>
            </div>
          </div>
          <div class="u-align-left u-container-style u-list-item u-radius-20 u-repeater-item u-shape-round u-white">
            <div class="u-container-layout u-similar-container u-valign-top u-container-layout-3">
              <p class="u-text u-text-grey-50 u-text-8"> "Insane initiative to make a difference.&nbsp;<br>
                <br>Super easy to calculate and offset the carbon footprint."
              </p>
              <div alt="" class="u-image u-image-circle u-image-3" data-image-width="320" data-image-height="320"></div>
              <h5 class="u-text u-text-default u-text-grey-50 u-text-9">Anubhav Kaushik</h5>
              <h5 class="u-text u-text-body-color u-text-default u-text-10">Founder, Industry For All&nbsp;</h5>
            </div>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-left u-black u-clearfix u-section-7" id="FAQ">
      <div class="u-clearfix u-sheet u-valign-middle-xs u-valign-top-lg u-valign-top-md u-valign-top-sm u-sheet-1">
        <img src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/images/white-sedan-sport-car-driving-bridge_114579-4003.jpg" alt="" class="u-expanded-width-md u-expanded-width-sm u-expanded-width-xs u-image u-image-default u-image-1" data-image-width="3987" data-image-height="5981">
        <h2 class="u-text u-text-1">faq</h2>
        <p class="u-text u-text-2">Get answers to your questions about lowsoot</p>
        <div class="u-accordion u-expanded-width-md u-expanded-width-sm u-expanded-width-xs u-faq u-spacing-20 u-accordion-1">
          <div class="u-accordion-item">
            <a class="active u-accordion-link u-border-1 u-border-active-palette-5-dark-2 u-border-hover-palette-5-dark-2 u-border-no-left u-border-no-right u-border-no-top u-border-palette-5-dark-2 u-button-style u-text-body-alt-color u-accordion-link-1" id="link-accordion-6327" aria-controls="accordion-6327" aria-selected="true">
              <span class="u-accordion-link-text"><b>What happens when I sign up?</b>
              </span><span class="u-accordion-link-icon u-icon u-text-black u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 16 16" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-2c6d"></use></svg><svg class="u-svg-content" viewBox="0 0 16 16" x="0px" y="0px" id="svg-2c6d" style=""><path d="M8,10.7L1.6,5.3c-0.4-0.4-1-0.4-1.3,0c-0.4,0.4-0.4,0.9,0,1.3l7.2,6.1c0.1,0.1,0.4,0.2,0.6,0.2s0.4-0.1,0.6-0.2l7.1-6
	c0.4-0.4,0.4-0.9,0-1.3c-0.4-0.4-1-0.4-1.3,0L8,10.7z"></path></svg></span>
            </a>
            <div class="u-accordion-active u-accordion-pane u-container-style u-accordion-pane-1" id="accordion-6327" aria-labelledby="link-accordion-6327">
              <div class="u-container-layout u-container-layout-1">
                <div class="fr-view u-clearfix u-rich-text u-text">
                  <p>As a subscriber, you help to finance programmes that reduce carbon emissions. We send out a "impact update" email once a month to let you know how far your money has gone. Visit our projects page to learn more.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="u-accordion-item">
            <a class="u-accordion-link u-border-1 u-border-active-palette-5-dark-2 u-border-hover-palette-5-dark-2 u-border-no-left u-border-no-right u-border-no-top u-border-palette-5-dark-2 u-button-style u-text-body-alt-color u-accordion-link-2" id="link-accordion-4aab" aria-controls="accordion-4aab" aria-selected="false">
              <span class="u-accordion-link-text"><b>What more can I do to aid in the resolution of the climate crisis?</b>
              </span><span class="u-accordion-link-icon u-icon u-text-black u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 16 16" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-937a"></use></svg><svg class="u-svg-content" viewBox="0 0 16 16" x="0px" y="0px" id="svg-937a" style=""><path d="M8,10.7L1.6,5.3c-0.4-0.4-1-0.4-1.3,0c-0.4,0.4-0.4,0.9,0,1.3l7.2,6.1c0.1,0.1,0.4,0.2,0.6,0.2s0.4-0.1,0.6-0.2l7.1-6
	c0.4-0.4,0.4-0.9,0-1.3c-0.4-0.4-1-0.4-1.3,0L8,10.7z"></path></svg></span>
            </a>
            <div class="u-accordion-pane u-container-style u-accordion-pane-2" id="accordion-4aab" aria-labelledby="link-accordion-4aab">
              <div class="u-container-layout u-container-layout-2">
                <div class="fr-view u-clearfix u-rich-text u-text">
                  <p>The first thing you should do is figure out how to decrease and offset your carbon footprint. You can also help others learn about climate change. The majority of people are unaware of the gravity of the situation. Vote for leaders that care about climate change.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="u-accordion-item">
            <a class="u-accordion-link u-border-1 u-border-active-palette-5-dark-2 u-border-hover-palette-5-dark-2 u-border-no-left u-border-no-right u-border-no-top u-border-palette-5-dark-2 u-button-style u-text-body-alt-color u-accordion-link-3" id="link-accordion-eb1f" aria-controls="accordion-eb1f" aria-selected="false">
              <span class="u-accordion-link-text"><b>How does your business model work?</b>
              </span><span class="u-accordion-link-icon u-icon u-text-black u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 16 16" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-4086"></use></svg><svg class="u-svg-content" viewBox="0 0 16 16" x="0px" y="0px" id="svg-4086" style=""><path d="M8,10.7L1.6,5.3c-0.4-0.4-1-0.4-1.3,0c-0.4,0.4-0.4,0.9,0,1.3l7.2,6.1c0.1,0.1,0.4,0.2,0.6,0.2s0.4-0.1,0.6-0.2l7.1-6
	c0.4-0.4,0.4-0.9,0-1.3c-0.4-0.4-1-0.4-1.3,0L8,10.7z"></path></svg></span>
            </a>
            <div class="u-accordion-pane u-container-style u-accordion-pane-3" id="accordion-eb1f" aria-labelledby="link-accordion-eb1f">
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
        <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/Site/images/textlogowhite.png" alt="" data-image-width="2000" data-image-height="483">
      </div></footer>

    
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
    rzp.open();
    e.preventDefault();
}
document.getElementById('rzp-button2').onclick = function(e){
    rzp.open();
    e.preventDefault();
}
</script>
  </body>
</html>