<?php
global $wpdb;
$frmt_form_entry_meta = $wpdb->prefix."frmt_form_entry_meta";
$postmeta = $wpdb->prefix."postmeta";
$form_id='';
$entry_id='';
$calculate_price=0;
$current_user = wp_get_current_user();
$tablename = $wpdb->prefix."forminator_track";
$entry = $wpdb->get_row("SELECT * FROM ".$tablename." where user_id='".get_current_user_id()."' order by id limit 1");
if(isset($_GET['form_id']) && isset($_GET['entry_id'])){
    $form_id=sanitize_text_field($_GET['form_id']);
    $entry_id=sanitize_text_field($_GET['entry_id']);
}
//check current user
if(isset($entry->form_id)){
  if($entry->form_id==$form_id && $entry_id==$entry->entry_id){
  }
  else{
    die('No record found');
  }
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
<!-- <h1>Carbon Footprint</h1> -->
<style> td {font-size: 50px;
    padding: 20px 0px 0px 0px;
    color: #f37421;} 
    .innercontainer
    {margin-top:20px!important;}
    </style>
<div  style="width:100%;background-color:#363636;padding:10px;font-size: 25px; border-radius: 5px; padding:50px!important;">
<table width='100%'  style='border-collapse: collapse;'>
  <tr>
   <!-- <th>SLNO.</th>
   <th>Question</th>
   <th>Answer</th> -->
   <th style="">Your Annual Carbon Emissions</th>
  </tr>
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
      /*  echo "<tr>
      <td>".$count."</td>
      <td>".$question."</td>
      <td>".$answer."</td>
      <td>".$calculation_value."</td>
      </tr> */
     
      $count++;
    }
    

 }
 echo "
<tr> 
<td> ".$total_calculation_value." KGs per Annum</td> 
</tr>";

}
//echo do_shortcode('[RZP]')
?>
<td style="color:#fff!important; font-size:24px; font-weight:300; padding:0px 0px 40px 0px">Your individual carbon emissions can be erased by investing in Lowsoot's projects.</td>
</table>


<!-- payment -->
<?php

// Store users Annual carbon emissions to DB first
echo "jhdkehwk";

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
    'amount'          => $price * 100, // 2000 rupees in paise
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



<button style="    background: #198730;
    border-width: 0px;
    padding: 16px 33px;
    border-radius: 5px;
    color: #fff;" id="rzp-button1">Pay to Erase</button>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<form name='razorpayform' action="?payment=payment" method="POST">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature"  id="razorpay_signature" >
</form>
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
</script>
</div>

<table style="100%">
  <tr>
    <th style="width:33%;"><img src="/wp-content/themes/twentytwentyone/images/invest.jpg" width="100%" style ="padding: 10px;
    border-radius: 25px!important;"></th>
    <th style="width:33%;"> <img src="/wp-content/themes/twentytwentyone/images/touch.jpg" width="100%" style ="padding: 10px;
    border-radius: 25px!important;"></th> 
    <th style="width:33%;"> <img src="/wp-content/themes/twentytwentyone/images/Activist.jpg" width="100%" style ="padding: 10px;
    border-radius: 25px!important;"></th>
  </tr></table>