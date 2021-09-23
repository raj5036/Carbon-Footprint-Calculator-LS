<?php
    session_start();
    require('razorpay/config.php');
    require('razorpay/razorpay-php/Razorpay.php');
    use Razorpay\Api\Api;
    use Razorpay\Api\Errors\SignatureVerificationError;
    
    global $wpdb;
    
    $success = true;
    
    $error = "Payment Failed";
    
    if (empty($_POST['razorpay_payment_id']) === false)
    {
        $api = new Api($keyId, $keySecret);
    
        try
        {
            // Please note that the razorpay order ID must
            // come from a trusted source (session here, but
            // could be database or something else)
            $attributes = array(
                'razorpay_order_id' => $_SESSION['razorpay_order_id'],
                'razorpay_payment_id' => $_POST['razorpay_payment_id'],
                'razorpay_signature' => $_POST['razorpay_signature']
            );
    
            $api->utility->verifyPaymentSignature($attributes);
        }
        catch(SignatureVerificationError $e)
        {
            $success = false;
            $error = 'Razorpay Error : ' . $e->getMessage();
        }
    }
    
    if ($success === true)
    {
        //Store order details in the db
        $table_name=$wpdb->prefix."order_stats";
        $wpdb->insert(
            $table_name,
            [
                "order_id"=>$_SESSION['razorpay_order_id'],
                "order_status"=>'success',
                "razorpay_payment_id"=>$_POST['razorpay_payment_id'],
                "user_email"=>$_SESSION['email']
            ]
        );
        
        
        //reduce carbon footprint for the current user
        $users_table=$wpdb->prefix."users";
        $user_details = $wpdb->get_row("SELECT * FROM ".$users_table." WHERE ID=".get_current_user_id()." LIMIT 1");
        
        $current_carbon_footprint=$user_details->current_carbon_footprint;
        $updated_carbon_footprint=0;
        
        if($_SESSION['carbon_percentage_reduced']==100){  //100%
            $updated_carbon_footprint=0;
        }else if($_SESSION['carbon_percentage_reduced']==30){  //30%
            $updated_carbon_footprint=$current_carbon_footprint-($current_carbon_footprint*0.3);
        }else{ //10%
            $updated_carbon_footprint=$current_carbon_footprint-($current_carbon_footprint*0.1);
        }
        
        $wpdb->update(
                        $users_table,      //table_name
                        ["current_carbon_footprint"=>$updated_carbon_footprint],  //data to update
                        ["ID"=>get_current_user_id()]  //Where conditions
        ); 
     
        $html = "<p>Your payment was successful</p>
                 <p>Payment ID: {$_POST['razorpay_payment_id']}</p>";
    }
    else
    {
        //Store order details in the db
        $table_name=$wpdb->prefix."order_stats";
        $wpdb->insert(
            $table_name,
            [
                "order_id"=>$_SESSION['razorpay_order_id'],
                "order_status"=>"falied",
                "razorpay_payment_id"=>NULL,
                "user_email"=>$_SESSION['email']
            ]
        );
        
        $html = "<p>Your payment failed</p>
                 <p>{$error}</p>";
    }
    
    echo $html;