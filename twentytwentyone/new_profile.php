<?php  
/* 
Template Name: Profile Page
*/  
global $wpdb, $user_ID; 
if (!$user_ID) 
{  
    echo "<script type='text/javascript'>window.location.href='". home_url() ."'</script>";
} 
 include "displaylist.php";
                             
				



