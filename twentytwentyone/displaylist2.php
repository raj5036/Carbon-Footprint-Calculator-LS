<?php
global $wpdb;
$tablename = $wpdb->prefix."forminator_track";
?>
<!-- <h1>Carbon Footprint List</h1> -->
<style> a {color: #fff!important; text-decoration: auto!important; padding: 10px!important;
    font-size: 18px;
    background: #f37421!important;}</style>
    
<div  style="width:100%;background-color:#363636;padding:35px 30px;font-size: 25px; border-radius:5px!important;">
    
<table width='100%' style='border-collapse: collapse;'>
    <h1 style=" Font-size:20px;   font-weight: 900;
    background-color: #fff;
    color: #000;
    padding: 10px;
    border-radius:4px;">Previous Calculations</h1>
  <tr>
   <th style="font-size: 15px;
    color: #f37421;
    padding: 25px 0px;">No. </th>
   <th style="font-size: 15px;
    color: #f37421;
    padding: 25px 0px;">Calculation Date </th>
   <th style="font-size: 15px;
    color: #f37421;
    padding: 25px 0px;"> Actions </th>
  </tr>
  <?php
  // Select records
  $entriesList = $wpdb->get_results("SELECT * FROM ".$tablename." where user_id='".get_current_user_id()."' order by id limit 1");
  if(count($entriesList) > 0){
    $count = 1;
    foreach($entriesList as $entry){
      echo "<tr>
      <td>".$count."</td>
      <td>".date('Y-m-d',strtotime($entry->date_created))."</td>
      <td>
    
    <a href=?calculate=calculate&form_id=".$entry->form_id."&entry_id=".$entry->entry_id." >View My Carbon Footprint</a> </td>
      </tr>
      ";
      $count++;
   }
 }else{
   echo "<tr><td colspan='3'>No record found</td></tr>";
 }
?>
</table>


</div>

<table style="100%">
  <tr>
    <th style="width:33%;"><img src="/wp-content/themes/twentytwentyone/images/total.jpg" width="100%" style ="padding: 10px;
    border-radius: 25px!important;"></th>
    <th style="width:33%;"> <img src="/wp-content/themes/twentytwentyone/images/forest.jpg" width="100%" style ="padding: 10px;
    border-radius: 25px!important;"></th> 
    <th style="width:33%;"> <img src="/wp-content/themes/twentytwentyone/images/chicks.jpg" width="100%" style ="padding: 10px;
    border-radius: 25px!important;"></th>
  </tr></table>