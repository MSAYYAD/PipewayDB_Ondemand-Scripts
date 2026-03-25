<?php 
//This is s test script to test any PHP related operation in prod env.

   // date_default_timezone_get();
   // $currenttime = date('h:i:s:u');
   // list($hrs,$mins,$secs,$msecs) = split(':',$currenttime);
   // echo " => $hrs:$mins:$secs\n";
   //include_once('PHPMailer/PHPMailerAutoload.php');
   date_default_timezone_set('US/Eastern');
   //date_default_timezone_set('Asia/Kolkata');
   $currenttime = date('Y-m-d h:i:s:a');
   $time        =date('h:i:s');
   if($time=='14:00:00'){ echo $currenttime;
    $mailfire = new PHPMailer;
    $mailfire->isSMTP(); 
   // adding mail after changeing post fix  // nilesh 18-11-25
              $mail->Host = '172.16.13.208';
   //  $mailfire->SMTPAuth = true;                             
   //  $mailfire->Username = 'pwadmin@aacanet.org';                 
   //  $mailfire->Password = 'Aaca1s@1';                           
   //  $mailfire->SMTPSecure = 'tls';                           
    $mailfire->Port = 25;  
    $mailfire->From = 'pwadmin@aacanet.org';   
    $mailfire->FromName = 'date check';
    $mailfire->addAddress('bandana.kumari@goolean.tech');
    $mailfire->isHTML(true); // Set email format to HTML
    $mailfire->Subject = 'date check';;
    $mailfire-> Body.= $currenttime;
    $mailfire-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>"; 
    if(!$mailfire->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mailfire->ErrorInfo;
    } else {
    echo 'mail send';

    }
  }
   // list($hrs,$mins,$secs,$msecs) = split(':',$currenttime);
   // echo " => $hrs:$mins:$secs\n";

//    date_default_timezone_set('America/New_York');
//    echo date_default_timezone_get();
//    $currenttime = date('h:i:s:u');
//    list($hrs,$mins,$secs,$msecs) = split(':',$currenttime);
//    echo " => $hrs:$mins:$secs\n";
// ?>