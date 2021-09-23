<?php  
/* 
Template Name: Profile Page
*/  
 
global $wpdb, $user_ID;  
$tablename = $wpdb->prefix."forminator_track";
if (!$user_ID) 
{  
    echo "<script type='text/javascript'>window.location.href='". home_url() ."'</script>";
} 
//echo do_shortcode( "[only_logout]" ); 
$entry = $wpdb->get_row("SELECT * FROM ".$tablename." where user_id='".get_current_user_id()."' order by id limit 1");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <link href="<?php echo get_template_directory_uri(); ?>/calculation_asset/css/styles.css" rel="stylesheet" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css">
    <style> 
    .innercontainer
    {margin-top:20px!important;}
    </style>
    
    
    </head>
    <body style="background-color: #E4E4E4;">
	 <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #F37421;">
            <div class="container">
                <span class="" id="sidebarToggle" ><i class="fa fa-bars" aria-hidden="true"></i></span>
                <a class="navbar-brand" href="#"> <img src="<?php echo get_template_directory_uri(); ?>/calculation_asset/assets/logo.png" alt="..." style="width:20%;height:16.18%;max-width:1712px;"></a> 
                
                <a href="https://lowsoot.com/" style="color:#fff!important;padding: 10px 22px!important; background:#363636!important; border-radius:4px!important;text-decoration: auto!important;">Back to Main Site ⇲ </a>  
            </div>
	
        </nav>
        <div class="d-flex" id="wrapper">
            <!-- Sidebar-->
            <div class="border-end bg-white" id="sidebar-wrapper">
                <div style="padding: 60px!important;" class="list-group list-group-flush">
					<div class="cal">
                        <a style="color:#363636; text-decoration: auto!important;font-weight: 700; background:#fff!important" href="https://lowsoot.com/profile">
                            <i class="fa fa-calculator circle-icon fa-2x" aria-hidden="true"></i>
                            <p class="sidebartxt" ><a style="color:#363636!important; text-decoration: auto;font-weight: 700; background:#fff0!important; padding:0px!important;    margin: 0px 0px 0px 0px!important;
    font-size: 15px;" href="https://lowsoot.com/profile">Calculations</a></p>
                        </a>
					</div>
                    <a style="color:#363636; text-decoration: auto!important;font-weight: 700; background:#fff!important" href="#">
                        <div class="cal">
                            <i style="padding: 30px 37px;
    border-radius: 74px;" class="fa fa-usd circle-icon fa-2x" aria-hidden="true"></i>
                            <p class="sidebartxt" style="color:#363636; text-decoration: auto!important;font-weight: 700;" href="#">Erase</p>
                        </div>
                    </a>
					<div class="cal">
                        <a style="color:#363636; text-decoration: auto!important;font-weight: 700; background:#fff!important" href="<?php echo do_shortcode( "[only_logout]" ) ?>">
                            <i class="fa fa-power-off circle-icon fa-2x" aria-hidden="true"></i>
                            <p class="sidebartxt" style="color:#363636; text-decoration: auto!important;font-weight: 700;     margin: 0px 0px 10px 12px;" href="https://lowsoot.com/wp-login.php?action=logout&_wpnonce=0099004f87">Logout</p>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Page content wrapper-->
			
            <div id="page-content-wrapper">
                <!-- Top navigation-->
                
                <!-- Page content-->
                <div class="container-fluid" >
						<div class="innercontainer" >
                            <?php 
                                if(isset($_GET['calculate']) && $_GET['entry_id'] && isset($_GET['form_id']) && isset($_GET['entry_id'])){
                                        include "calculate.php";
                                    }
                                    else if(isset($_GET['result']) && $_GET['entry_id'] && isset($_GET['form_id']) && isset($_GET['entry_id'])){
                                        include "calculate_result.php";
                                    }
                                    else if(isset($_GET['pay']) && $_GET['entry_id'] && isset($_GET['form_id']) && isset($_GET['entry_id'])){
                                        include "calculate_pay.php";
                                    }
                                    else if(isset($_GET['payment'])){
                                        include "razorpay/verify.php";
                                    }
                                    else{
                                        include "displaylist.php";
                                    }

                              ?>
						</div>
                </div>
            </div>
        </div>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="<?php echo get_template_directory_uri(); ?>/calculation_asset/js/scripts.js"></script>
        
        
        
    </body>
</html>