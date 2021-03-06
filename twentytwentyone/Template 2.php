
<?php  
/* 
Template Name: Erase
*/ 

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
      <style>.u-section-1 {
  background-image: linear-gradient(0deg, rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url("images/bbf7da2b57fe140ed4cfaa0a1a6798d058f9a183c1a1b7d8bf233cfe0e960adf58afa81bb635247283867c6a2173e3f49e42c7c318009412c52212_1280.jpg");
  background-position: 50% 50%;
}

.u-section-1 .u-sheet-1 {
  min-height: 671px;
}

.u-section-1 .u-text-1 {
  font-weight: 400;
  letter-spacing: normal;
  text-transform: none;
  margin: 60px auto 0;
}

.u-section-1 .u-text-2 {
  width: 774px;
  margin: 30px auto 0;
}

.u-section-1 .u-list-1 {
  grid-template-rows: repeat(1, auto);
  height: auto;
  margin: 40px auto 0 0;
}

.u-section-1 .u-repeater-1 {
  grid-template-columns: repeat(4, calc(25% - 23.25px));
  min-height: 217px;
  grid-gap: 31px;
}

.u-section-1 .u-list-item-1 {
  background-image: none;
}

.u-section-1 .u-container-layout-1 {
  padding: 30px;
}

.u-section-1 .u-icon-1 {
  height: 59px;
  width: 59px;
  background-image: none;
  margin: 0 auto;
}

.u-section-1 .u-text-3 {
  margin: 20px auto 0;
}

.u-section-1 .u-btn-1 {
  background-image: none;
  padding: 0;
}

.u-section-1 .u-list-item-2 {
  background-image: none;
}

.u-section-1 .u-container-layout-2 {
  padding: 30px;
}

.u-section-1 .u-icon-2 {
  height: 59px;
  width: 59px;
  background-image: none;
  margin: 0 auto;
}

.u-section-1 .u-text-4 {
  margin: 20px auto 0;
}

.u-section-1 .u-btn-2 {
  background-image: none;
  padding: 0;
}

.u-section-1 .u-list-item-3 {
  background-image: none;
}

.u-section-1 .u-container-layout-3 {
  padding: 30px;
}

.u-section-1 .u-icon-3 {
  height: 59px;
  width: 59px;
  background-image: none;
  margin: 0 auto;
}

.u-section-1 .u-text-5 {
  margin: 20px auto 0;
}

.u-section-1 .u-btn-3 {
  padding: 0;
}

.u-section-1 .u-list-item-4 {
  background-image: none;
}

.u-section-1 .u-container-layout-4 {
  padding: 30px;
}

.u-section-1 .u-icon-4 {
  height: 59px;
  width: 59px;
  background-image: none;
  color: rgb(243, 116, 33) !important;
  margin: 0 auto;
}

.u-section-1 .u-text-6 {
  margin: 20px auto 0;
}

.u-section-1 .u-btn-4 {
  background-image: none;
  padding: 0;
}

.u-section-1 .u-btn-5 {
  border-style: none;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.875rem;
  letter-spacing: 1px;
  background-image: none;
  margin: 40px auto 60px;
  padding: 12px 46px 12px 45px;
}

@media (max-width: 1199px) {
  .u-section-1 .u-sheet-1 {
    min-height: 553px;
  }

  .u-section-1 .u-list-1 {
    margin-right: initial;
    margin-left: initial;
  }

  .u-section-1 .u-repeater-1 {
    min-height: 179px;
  }
}

@media (max-width: 991px) {
  .u-section-1 .u-sheet-1 {
    min-height: 424px;
  }

  .u-section-1 .u-text-2 {
    width: 720px;
  }

  .u-section-1 .u-repeater-1 {
    grid-template-columns: repeat(2, calc(50% - 15.5px));
    min-height: 548px;
  }
}

@media (max-width: 767px) {
  .u-section-1 .u-sheet-1 {
    min-height: 868px;
  }

  .u-section-1 .u-text-1 {
    margin-top: 31px;
  }

  .u-section-1 .u-text-2 {
    width: 540px;
  }

  .u-section-1 .u-repeater-1 {
    grid-template-columns: calc(50% - 15.5px) calc(50% - 15.5px);
    min-height: 491px;
    grid-gap: 31px 31px;
  }

  .u-section-1 .u-container-layout-1 {
    padding-left: 20px;
    padding-right: 20px;
  }

  .u-section-1 .u-container-layout-2 {
    padding-left: 20px;
    padding-right: 20px;
  }

  .u-section-1 .u-container-layout-3 {
    padding-left: 20px;
    padding-right: 20px;
  }

  .u-section-1 .u-container-layout-4 {
    padding-left: 20px;
    padding-right: 20px;
  }

  .u-section-1 .u-btn-5 {
    margin-bottom: 31px;
  }
}

@media (max-width: 575px) {
  .u-section-1 .u-sheet-1 {
    min-height: 605px;
  }

  .u-section-1 .u-text-1 {
    margin-top: 60px;
  }

  .u-section-1 .u-text-2 {
    width: 340px;
  }

  .u-section-1 .u-repeater-1 {
    grid-template-columns: 100%;
  }

  .u-section-1 .u-container-layout-1 {
    padding-left: 30px;
    padding-right: 30px;
  }

  .u-section-1 .u-container-layout-2 {
    padding-left: 30px;
    padding-right: 30px;
  }

  .u-section-1 .u-container-layout-3 {
    padding-left: 30px;
    padding-right: 30px;
  }

  .u-section-1 .u-container-layout-4 {
    padding-left: 30px;
    padding-right: 30px;
  }

  .u-section-1 .u-btn-5 {
    margin-bottom: 60px;
  }
}</style>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <meta name="keywords" content="???One-click solution for your static? website., ???Hosting solution with benefits., What Clients Say, ???Purchase, ???Free, ???$9/month, ???$12/month, ???Get started with the simpliest static page">
    <meta name="description" content="">
    <meta name="page_type" content="np-template-header-footer-from-plugin">
    <title>Home</title>
    <link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/nicepage.css" media="screen">
   
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/Home.css" media="screen">
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/jquery.js" defer=""></script>
    <script class="u-script" type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/nicepage.js" defer=""></script>
    <meta name="generator" content="Nicepage 3.22.0, nicepage.com">
    <link id="u-theme-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i|Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i">
    <link id="u-page-google-font" rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Raleway:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Playfair+Display:400,400i,500,500i,600,600i,700,700i,800,800i,900,900i|Titillium+Web:200,200i,300,300i,400,400i,600,600i,700,700i,900|Roboto:100,100i,300,300i,400,400i,500,500i,700,700i,900,900i">
    
    
    
    
    
    
    <script type="application/ld+json">{
		"@context": "http://schema.org",
		"@type": "Organization",
		"name": "",
		"logo": "<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/lowsoot.png",
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
  <body data-home-page="Home.html" data-home-page-title="Home" class="u-body"><header class="u-align-center-sm u-align-center-xs u-clearfix u-custom-color-1 u-header u-header" id="sec-ef8f"><div class="u-clearfix u-sheet u-valign-middle-lg u-sheet-1">
        <a href="https://nicepage.com" class="u-image u-logo u-image-1" data-image-width="968" data-image-height="1028">
          <img src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/lowsoot.png" class="u-logo-image u-logo-image-1">
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
      <div class="u-clearfix u-sheet u-valign-top-xs u-sheet-1">
        <div class="u-container-style u-expanded-width u-group u-shape-rectangle u-group-1">
          <div class="u-container-layout u-container-layout-1">
            <p class="u-align-center u-text u-text-1">Your Annual Carbon Footprint</p>
            <h1 class="u-align-center u-text u-text-custom-color-1 u-text-2"><?php echo $Final['calculation_value']??'' ?> KGs </h1>
            <p class="u-align-center u-text u-text-custom-color-1 u-text-3"><b>Your footprint is 12 times the carbon footprint of an average indian and&nbsp;<b>equal to cutting 52 trees each year.</b>&nbsp;</b>
            </p>
            <a href="#questions" class="u-active-palette-2-dark-3 u-align-center u-border-none u-btn u-btn-round u-button-style u-custom-font u-font-montserrat u-grey-50 u-hover-palette-2-dark-3 u-radius-8 u-text-active-white u-text-body-alt-color u-text-hover-white u-btn-1">Have&nbsp;Questions?</a>
            <a href="#Erase" class="u-active-palette-2-dark-3 u-align-center u-border-none u-btn u-btn-round u-button-style u-custom-color-1 u-custom-font u-font-montserrat u-hover-palette-2-dark-3 u-radius-8 u-text-active-white u-text-body-alt-color u-text-hover-white u-btn-2">Erase my&nbsp;Footprint</a>
            <h3 class="u-text u-text-grey-40 u-text-4">Breakdown of your carbon footprint</h3>
            <div class="u-list u-list-1">
              <div class="u-repeater u-repeater-1">
                <div class="u-align-center u-container-style u-list-item u-radius-6 u-repeater-item u-shape-round u-white u-list-item-1">
                  <div class="u-container-layout u-similar-container u-container-layout-2">
                    <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/Tire.jpg" alt="" data-image-width="250" data-image-height="250">
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-5">Your Travel Emissions</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-default u-text-font u-text-6"><?php echo $Travel['calculation_value']??'' ?> KGs</h6>
                  </div>
                </div>
                <div class="u-align-center u-container-style u-list-item u-radius-6 u-repeater-item u-shape-round u-white u-list-item-2">
                  <div class="u-container-layout u-similar-container u-container-layout-3">
                    <img class="u-image u-image-default u-image-2" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/food.jpg" alt="" data-image-width="250" data-image-height="250">
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-7"> Your Food&nbsp; Emissions</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-default u-text-font u-text-8"> <?php echo $Food['calculation_value']??'' ?> KGs</h6>
                  </div>
                </div>
                <div class="u-align-center u-container-style u-list-item u-radius-6 u-repeater-item u-shape-round u-white u-list-item-3">
                  <div class="u-container-layout u-similar-container u-container-layout-4">
                    <img class="u-image u-image-default u-image-3" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/garbage.jpg" alt="" data-image-width="250" data-image-height="250">
                    <h5 class="u-custom-font u-font-montserrat u-text u-text-9">Your Utility Emissions</h5>
                    <h6 class="u-align-center u-custom-font u-text u-text-default u-text-font u-text-10"> <?php echo $Utilities['calculation_value']??'' ?> KGs</h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="u-clearfix u-layout-wrap u-layout-wrap-1" style="
    height:600px;
">
          <div class="u-layout">
            <div class="u-layout-row">
              <div class="u-align-left u-container-style u-image u-layout-cell u-shading u-size-24 u-image-4" data-image-width="1276" data-image-height="1280">
                <div class="u-container-layout u-container-layout-5">
                  <h4 class="u-custom-font u-font-raleway u-text u-text-11">How is my Annual Carbon Footprint Calculated?</h4>
                </div>
              </div>
              <div class="u-align-left u-container-style u-layout-cell u-palette-2-dark-1 u-radius-5 u-shape-round u-size-36 u-layout-cell-2">
                <div class="u-container-layout u-container-layout-6">
                  <p class="u-text u-text-12"> Provided with the choice of options, each answer is assigned a certain emission based on the number obtained from our carbon standards.</p>
                  <p class="u-text u-text-13">Our Calculation Standards are based on&nbsp;&nbsp;<a href="https://www.journals.elsevier.com/renewable-and-sustainable-energy-reviews" class="u-border-2 u-border-custom-color-1 u-btn u-button-link u-button-style u-none u-text-grey-15 u-btn-3">These Research Journals&nbsp;</a>
                  </p>
                  <a href="#Erase" class="u-active-black u-black u-border-none u-btn u-button-style u-hover-black u-btn-4">Erase NOw</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="u-container-style u-expanded-width u-group u-shape-rectangle u-group-2">
          <div class="u-container-layout u-container-layout-7"><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 409.294 409.294" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-5c4d"></use></svg><svg class="u-svg-content" viewBox="0 0 409.294 409.294" id="svg-5c4d"><path d="m233.882 29.235v175.412h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412z"></path><path d="m0 204.647h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412h-175.412z"></path></svg></span>
            <h4 class="u-custom-font u-font-playfair-display u-text u-text-14"> The question is, are we happy to suppose that our grandchildren may never be able to see an elephant except in a picture book?<br>
              <br>-&nbsp;<span style="font-weight: 700; font-size: 1.5rem;">David Attenborough</span>
            </h4><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 409.294 409.294" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-0171"></use></svg><svg class="u-svg-content" viewBox="0 0 409.294 409.294" id="svg-0171"><path d="m233.882 29.235v175.412h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412z"></path><path d="m0 204.647h116.941c0 64.48-52.461 116.941-116.941 116.941v58.471c96.728 0 175.412-78.684 175.412-175.412v-175.412h-175.412z"></path></svg></span>
          </div>
        </div>
      </div>
    </section>
    <section class="u-align-center u-clearfix u-section-2" id="questions">
      <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
        <h1 class="u-align-center u-custom-font u-font-titillium-web u-text u-text-custom-color-1 u-text-1">How Bad Is The Situation?</h1>
        <p class="u-align-center u-text u-text-grey-40 u-text-2"> The IPCC report says the situation is worse than critical. It's a warning to everyone to change their ways or else expect..</p>
        <div class="u-expanded-width-md u-list u-list-1">
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
                <p class="u-text u-text-white u-text-8"> Glaciers are retreating almost everywhere around the world ??? including in the Alps, Himalayas, Andes, Rockies, Alaska, and Africa</p>
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
        <img class="u-expanded-width-md u-expanded-width-sm u-expanded-width-xs u-image u-image-round u-radius-5 u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/3278eed2f001c45bdc29add23f0d0741e375c94cc8a9f360119e5be5025dc8052bd7b27c22887053f8a7de1e746ef103609440e7bf41cf838180aa_1280.jpg" data-image-width="1280" data-image-height="829">
      </div>
    </section>
    <section class="u-align-left u-clearfix u-grey-5 u-section-3" id="carousel_763d">
      <div class="u-clearfix u-sheet u-sheet-1">
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
    <section class="u-align-center u-black u-clearfix u-section-4" id="carousel_496d">
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
    <section class="u-clearfix u-grey-10 u-section-5" id="Erase">
      <div class="u-clearfix u-sheet u-sheet-1">
        <h1 class="u-align-center-lg u-align-center-md u-align-center-xl u-align-center-xs u-align-left-sm u-custom-font u-font-titillium-web u-text u-text-1">Erase Your Carbon Footprint</h1>
        <div class="u-clearfix u-expanded-width u-layout-wrap u-layout-wrap-1" style="
    height: 250px;
">
          <div class="u-layout">
            <div class="u-layout-row">
              <div class="u-align-center-lg u-align-center-md u-align-center-xl u-align-center-xs u-align-left-sm u-container-style u-layout-cell u-size-60 u-layout-cell-1">
                <div class="u-container-layout u-valign-top u-container-layout-1">
                  <p class="u-align-center-lg u-align-center-md u-align-center-xl u-text u-text-default u-text-2">Erasing your Carbon Footprint helps the earth by reversing your contribution to climate change. You can erase your carbon footprint by following these steps.&nbsp;</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-align-center-xs u-container-style u-custom-background u-list-item u-repeater-item">
              <div class="u-container-layout u-similar-container u-container-layout-2">
                <ul class="u-custom-item u-custom-list u-text u-text-palette-2-base u-text-3">
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                  </li>
                  <li>Plant a Tree</li>
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Switch to 5 Star Appliances<br>
                  </li>
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Switch to a Vegetarian Diet
                  </li>
                </ul>
              </div>
            </div>
            <div class="u-align-center-xs u-container-style u-custom-background u-list-item u-repeater-item">
              <div class="u-container-layout u-similar-container u-container-layout-3">
                <ul class="u-custom-item u-custom-list u-text u-text-palette-2-dark-1 u-text-4">
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                  </li>
                  <li>Choose Public Transport</li>
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Transition to Electric Vehicles
                  </li>
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Install a Solar Panel&nbsp;
                  </li>
                </ul>
              </div>
            </div>
            <div class="u-align-center-xs u-container-style u-custom-background u-list-item u-repeater-item">
              <div class="u-container-layout u-similar-container u-container-layout-4">
                <ul class="u-custom-item u-custom-list u-text u-text-palette-2-dark-1 u-text-5">
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>
                  </li>
                  <li>Sign up for a Green Policy</li>
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Segregate Dry &amp; Wet Waste
                  </li>
                  <li>
                    <div class="u-list-icon u-text-palette-2-base">
                      <svg class="u-svg-content" viewBox="0 0 512 512" id="svg-18ed"><path d="m433.1 67.1-231.8 231.9c-6.2 6.2-16.4 6.2-22.6 0l-99.8-99.8-78.9 78.8 150.5 150.5c10.5 10.5 24.6 16.3 39.4 16.3 14.8 0 29-5.9 39.4-16.3l282.7-282.5z" fill="currentColor"></path></svg>
                    </div>Switch to Electric Stoves
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <h2 class="u-align-center u-custom-font u-font-raleway u-text u-text-default u-text-palette-1-base u-text-6">
          <span class="u-text-grey-60">Erase 10% of your Carbon Footprin???t By Doing the following</span>
        </h2>
        <div class="u-expanded-width-lg u-expanded-width-xl u-list u-list-2">
          <div class="u-repeater u-repeater-2">
            <div class="u-align-center-xs u-align-left-lg u-align-left-md u-align-left-sm u-align-left-xl u-container-style u-list-item u-radius-50 u-repeater-item u-shape-round u-white u-list-item-4">
              <div class="u-container-layout u-similar-container u-container-layout-5">
                <div alt="" class="u-align-center-md u-align-center-sm u-align-center-xs u-image u-image-circle u-image-1" data-image-width="1280" data-image-height="785"></div>
                <h3 class="u-custom-font u-font-raleway u-text u-text-custom-color-1 u-text-7">Refer a Friend</h3>
                <p class="u-text u-text-8">Spread the word about our initiative to a friend, help them calculate their Carbon Footprint with Lowsoot</p>
                <a href="https://nicepage.com/k/competition-website-templates" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-color-1 u-hover-palette-1-light-1 u-radius-6 u-btn-1">Refer a Friend</a>
              </div>
            </div>
            <div class="u-align-center-xs u-align-left-lg u-align-left-md u-align-left-sm u-align-left-xl u-container-style u-custom-background u-list-item u-repeater-item u-shape-rectangle">
              <div class="u-container-layout u-similar-container u-container-layout-6">
                <div alt="" class="u-align-center-md u-align-center-sm u-align-center-xs u-image u-image-circle u-image-2" data-image-width="1280" data-image-height="920"></div>
                <h3 class="u-align-center-md u-align-center-sm u-custom-font u-font-raleway u-text u-text-custom-color-1 u-text-9">Carbon Offset Guide</h3>
                <p class="u-align-center-md u-align-center-sm u-text u-text-10">We have prepared an easy guide for you to follow and help reverse the climate change without any investment</p>
                <a href="https://nicepage.com/k/competition-website-templates" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-color-1 u-hover-palette-1-light-1 u-radius-6 u-btn-2">Sign The Petition</a>
              </div>
            </div>
          </div>
        </div>
        <h1 class="u-align-center u-text u-text-11">
          <font color="#666666">You can also Erase your Carbon Footrpint By Investing in Carbon Removal Projects.&nbsp;</font>
        </h1>
        <div class="u-clearfix u-expanded-width u-gutter-30 u-layout-wrap u-layout-wrap-2">
          <div class="u-gutter-0 u-layout">
            <div class="u-layout-row">
              <div class="u-align-left u-container-style u-layout-cell u-left-cell u-palette-2-base u-radius-15 u-shape-round u-size-20 u-layout-cell-2">
                <div class="u-container-layout u-valign-top-lg u-valign-top-md u-valign-top-sm u-valign-top-xs u-container-layout-7">
                  <h4 class="u-text u-text-body-alt-color u-text-default-lg u-text-default-md u-text-default-sm u-text-default-xl u-text-12">Erase 100% of your annual carbon emissions</h4>
                  <h1 class="u-custom-font u-font-roboto u-text u-text-default u-text-13"> ??? <?php echo $price??''; ?></h1>
                  <a href="https://lowsoot.com/erase-all/" class="u-align-center-md u-align-center-sm u-align-center-xs u-btn u-btn-round u-button-style u-custom-font u-font-montserrat u-radius-8 u-text-palette-2-base u-white u-btn-3">Erase Now</a>
                </div>
              </div>
              <div class="u-align-left u-container-style u-layout-cell u-radius-15 u-size-20 u-white u-layout-cell-3">
                <div class="u-container-layout u-container-layout-8">
                  <h4 class="u-align-center-md u-align-center-sm u-align-center-xs u-text u-text-palette-2-base u-text-14"> Erase 30% of your annual carbon emissions</h4>
                  <h1 class="u-align-center-md u-align-center-sm u-custom-font u-font-roboto u-text u-text-default u-text-palette-2-base u-text-17"> ??? <?php echo $price * (30/100)??''; ?></h1>
                  <a href="https://lowsoot.com/erasethirty/" class="u-align-center-md u-align-center-sm u-align-center-xs u-border-none u-btn u-btn-round u-button-style u-custom-font u-font-montserrat u-palette-2-light-2 u-radius-8 u-text-palette-2-base u-btn-4">Erase Now</a>
                </div>
              </div>
              <div class="u-align-center-xs u-align-left-lg u-align-left-md u-align-left-sm u-align-left-xl u-container-style u-layout-cell u-radius-15 u-right-cell u-size-20 u-white u-layout-cell-4">
                <div class="u-container-layout u-valign-top u-container-layout-9">
                  <h4 class="u-text u-text-palette-2-base u-text-16"> Erase 10% of your annual carbon emissions</h4>
                  <h1 class="u-align-center-md u-align-center-sm u-custom-font u-font-roboto u-text u-text-default u-text-palette-2-base u-text-17"> ??? <?php echo $price * (10/100)??''; ?></h1>
                  <a href="https://lowsoot.com/eraseten/" class="u-align-center-md u-align-center-sm u-align-center-xs u-btn u-btn-round u-button-style u-custom-font u-font-montserrat u-palette-2-light-2 u-radius-8 u-text-palette-2-base u-btn-5">Erase Now</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <section class="u-align-center u-clearfix u-image u-shading u-section-1" src="" data-image-width="1280" data-image-height="809" id="sec-2355">
      <div class="u-clearfix u-sheet u-valign-middle u-sheet-1">
        <h1 class="u-text u-text-body-alt-color u-text-default u-text-1">Your Account</h1>
        <p class="u-text u-text-2"></p>
        <div class="u-expanded-width u-list u-list-1">
          <div class="u-repeater u-repeater-1">
            <div class="u-align-center u-container-style u-list-item u-repeater-item u-video-cover u-white u-list-item-1" data-animation-name="" data-animation-duration="0" data-animation-delay="0" data-animation-direction="">
              <div class="u-container-layout u-similar-container u-valign-middle u-container-layout-1"><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-1"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 438.483 438.483" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-242c"></use></svg><svg class="u-svg-content" viewBox="0 0 438.483 438.483" x="0px" y="0px" id="svg-242c" style="enable-background:new 0 0 438.483 438.483;"><g><g><path d="M431.168,230.762c-23.552-75.776-98.304-127.488-187.904-129.024V13.162c0-4.096-3.584-7.68-7.68-7.68    c-1.536,0-3.072,0.512-4.608,1.536L3.136,171.882c-3.584,2.56-4.096,7.168-1.536,10.752c0.512,0.512,1.024,1.024,1.536,1.536    l227.84,163.84c3.584,2.56,8.192,1.536,10.752-1.536c1.024-1.536,1.536-3.072,1.536-4.608v-88.064    c55.296,0,101.888,26.112,118.272,65.536c13.824,33.792,2.56,70.144-30.208,100.352c-3.072,3.072-3.584,7.68-0.512,10.752    c1.536,1.536,3.584,2.56,5.632,2.56h6.144c1.536,0,3.072-0.512,4.096-1.536C421.952,381.802,454.208,304.49,431.168,230.762z"></path>
</g>
</g></svg>
            
            
          </span>
                <h4 class="u-align-center u-text u-text-default u-text-3">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-palette-1-base u-btn-1" href="https://lowsoot.com/">Back to Home</a>
                </h4>
              </div>
            </div>
            <div class="u-align-center u-container-style u-list-item u-repeater-item u-video-cover u-white u-list-item-2" data-animation-name="" data-animation-duration="0" data-animation-delay="0" data-animation-direction="">
              <div class="u-container-layout u-similar-container u-valign-middle u-container-layout-2"><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-2"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 423.055 423.055" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-34b8"></use></svg><svg class="u-svg-content" viewBox="0 0 423.055 423.055" x="0px" y="0px" id="svg-34b8" style="enable-background:new 0 0 423.055 423.055;"><g><g><path d="M362.021,10.869c-6.431-2.963-14.009-1.81-19.269,2.93l-27.755,24.575c-0.755,0.672-1.894,0.668-2.645-0.008L274.588,4.59    c-6.83-6.12-17.17-6.12-24,0l-37.73,33.745c-0.759,0.678-1.906,0.678-2.665,0L172.459,4.59c-6.83-6.119-17.17-6.119-24,0    L110.69,38.366c-0.756,0.676-1.898,0.679-2.658,0.007l-27.78-24.574c-7.37-6.554-18.658-5.893-25.212,1.477    c-2.939,3.305-4.547,7.583-4.513,12.005v368.494c-0.066,9.878,7.888,17.939,17.766,18.005c4.425,0.03,8.703-1.582,12.009-4.523    l27.755-24.575c0.755-0.672,1.894-0.668,2.645,0.008l37.764,33.776c6.83,6.12,17.17,6.12,24,0l37.734-33.745    c0.759-0.678,1.906-0.678,2.665,0l37.734,33.744c6.831,6.117,17.17,6.117,24,0l37.771-33.776c0.756-0.676,1.898-0.679,2.658-0.007    l27.78,24.574c7.373,6.551,18.66,5.885,25.211-1.488c2.934-3.302,4.54-7.575,4.508-11.993V27.281    C372.621,20.202,368.489,13.747,362.021,10.869z M116.734,143.528h99.586c4.418,0,8,3.582,8,8s-3.582,8-8,8h-99.586    c-4.418,0-8-3.582-8-8S112.316,143.528,116.734,143.528z M306.32,279.528H116.734c-4.418,0-8-3.582-8-8s3.582-8,8-8H306.32    c4.418,0,8,3.582,8,8S310.738,279.528,306.32,279.528z M306.32,219.528H116.734c-4.418,0-8-3.582-8-8s3.582-8,8-8H306.32    c4.418,0,8,3.582,8,8S310.738,219.528,306.32,219.528z"></path>
</g>
</g></svg>
            
            
          </span>
                <h4 class="u-align-center-sm u-align-center-xs u-text u-text-default u-text-4">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-palette-1-base u-btn-2" href="https://lowsoot.com/receipts">My Receipts</a>
                </h4>
              </div>
            </div>
            <div class="u-align-center u-container-style u-list-item u-repeater-item u-video-cover u-white u-list-item-3" data-animation-name="" data-animation-duration="0" data-animation-delay="0" data-animation-direction="">
              <div class="u-container-layout u-similar-container u-valign-middle u-container-layout-3"><span class="u-icon u-icon-circle u-text-custom-color-1 u-icon-3"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 512 512" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-399e"></use></svg><svg class="u-svg-content" viewBox="0 0 512 512" id="svg-399e"><g><path d="m467 165.999h-272c-24.814 0-45 20.186-45 45v150c0 24.814 20.186 45 45 45h235.789l55.605 55.605c4.33 4.33 10.82 5.559 16.348 3.252 5.61-2.314 9.258-7.793 9.258-13.857v-240c0-24.814-20.186-45-45-45zm-75 165h-122c-8.291 0-15-6.709-15-15s6.709-15 15-15h122c8.291 0 15 6.709 15 15s-6.709 15-15 15zm30-60h-182c-8.291 0-15-6.709-15-15s6.709-15 15-15h182c8.291 0 15 6.709 15 15s-6.709 15-15 15z"></path><path d="m9.258 344.856c5.528 2.307 12.017 1.078 16.348-3.252l55.605-55.605h38.789v-75c0-41.353 33.647-75 75-75h167v-45c0-24.853-20.147-45-45-45h-272c-24.853 0-45 20.147-45 45v240c0 6.064 3.647 11.543 9.258 13.857z"></path>
</g></svg>
            
            
          </span>
                <h4 class="u-align-center-sm u-align-center-xs u-text u-text-default u-text-5">
                  <a class="u-btn u-button-link u-button-style u-none u-text-palette-1-base u-btn-3" href="https://lowsoot.com/get-support">Get Support</a>
                </h4>
              </div>
            </div>
            <div class="u-align-center u-container-style u-list-item u-repeater-item u-video-cover u-white u-list-item-4" data-animation-name="" data-animation-duration="0" data-animation-delay="0" data-animation-direction="">
              <div class="u-container-layout u-similar-container u-valign-middle u-container-layout-4"><span class="u-icon u-icon-circle u-icon-4"><svg class="u-svg-link" preserveAspectRatio="xMidYMin slice" viewBox="0 0 122.775 122.776" style=""><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-70b5"></use></svg><svg class="u-svg-content" viewBox="0 0 122.775 122.776" x="0px" y="0px" id="svg-70b5" style="enable-background:new 0 0 122.775 122.776;"><g><path d="M86,28.074v-20.7c0-3.3-2.699-6-6-6H6c-3.3,0-6,2.7-6,6v3.9v78.2v2.701c0,2.199,1.3,4.299,3.2,5.299l45.6,23.601   c2,1,4.4-0.399,4.4-2.7v-23H80c3.301,0,6-2.699,6-6v-32.8H74v23.8c0,1.7-1.3,3-3,3H53.3v-30.8v-19.5v-0.6c0-2.2-1.3-4.3-3.2-5.3   l-26.9-13.8H71c1.7,0,3,1.3,3,3v11.8h12V28.074z"></path><path d="M101.4,18.273l19.5,19.5c2.5,2.5,2.5,6.2,0,8.7l-19.5,19.5c-2.5,2.5-6.301,2.601-8.801,0.101   c-2.399-2.399-2.1-6.4,0.201-8.8l8.799-8.7H67.5c-1.699,0-3.4-0.7-4.5-2c-2.8-3-2.1-8.3,1.5-10.3c0.9-0.5,2-0.8,3-0.8h34.1   c0,0-8.699-8.7-8.799-8.7c-2.301-2.3-2.601-6.4-0.201-8.7C95,15.674,98.9,15.773,101.4,18.273z"></path>
</g></svg>
            
            
          </span>
                <h4 class="u-align-center-sm u-align-center-xs u-text u-text-default u-text-6">
                  <a class="u-active-none u-border-none u-btn u-button-link u-button-style u-hover-none u-none u-text-palette-1-base u-btn-4" href="https://lowsoot.com/logout">Logout</a>
                </h4>
              </div>
            </div>
          </div>
        </div>
        <a href="#Top" class="u-border-none u-btn u-btn-round u-button-style u-grey-90 u-hover-custom-color-2 u-radius-50 u-btn-5">My Calculation</a>
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
        <img class="u-image u-image-default u-image-1" src="<?php echo get_template_directory_uri(); ?>/calculation_asset/new_asset/MainSitefinal/images/textlogowhite.png" alt="" data-image-width="2000" data-image-height="483">
      </div></footer>
  </body>
</html>