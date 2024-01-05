<?php
function storejson(){
$sql="select * from users";
$result=query($sql);




$data_array = array();
while($rows =mysqli_fetch_assoc($result)) {
   $data_array[] = $rows;
   $fp = fopen('C:/xampp/htdocs/login/functions/myjson.json', 'w');

   if(!fwrite($fp, json_encode($data_array)."\r\n")) {
     die('Error : File Not Opened. ' . mysql_error());
    }
  }



   fclose($fp);

?>
