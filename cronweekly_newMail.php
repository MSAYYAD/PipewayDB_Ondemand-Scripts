
<?php

ob_start();
date_default_timezone_set('US/Eastern');
ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');
include_once('PHPMailer/PHPMailerAutoload.php');
$host      = "localhost";
$username  = "pipewaydb";
$password  = "A8EK5hKjq*CX&w";
$database  = "aaca_live";
$conn      = mysqli_connect($host, $username, $password, $database);

$dir       = '/var/lib/mysql-files'; // The directory containing the files. 
$ext       = '.csv'; // The file extension. 
$files     = glob($dir . '/*' . $ext);
$fileopen  = fopen("/var/lib/mysql-files/date_trigger.csv", "r");
$fileR     = fgetcsv($fileopen);
$date      = date('dmY'); //sampledate:"1702201","date"
$datedb    = date('Y-m-d');
$datetime  = date('Y-m-d h:i:s a');
if ($date == $fileR[0]) {
  $mailfire = new PHPMailer;
  $mailfire->isSMTP();
  // $mailfire->Host = 'smtp.office365.com'; 
  // adding mail after changeing post fix  // nilesh 18-11-25
  $mail->Host = '172.16.13.208';
  // $mailfire->SMTPAuth = true;
  // $mailfire->Username = 'pwadmin@aacanet.org';                 
  // $mailfire->Password = 'december.SURVEY.95';                           
  // $mailfire->SMTPSecure = 'tls';
  $mailfire->Port = 25;
  $mailfire->From = 'pwadmin@aacanet.org';
  $mailfire->FromName = 'CRONWEEKLY BIDEV script notification';

  $mailfire->addAddress('bandana.kumari@goolean.tech');
  $mailfire->addAddress('nilesh.karanjkar@goolean.tech');
  $mailfire->addAddress('nitin.kumar@goolean.tech');
  //$mailfire->addAddress('anwar.hussain@goolean.tech');
  $mailfire->isHTML(true); // Set email format to HTML
  $mailfire->Subject = 'Weekly tables update has been started';
  $mailfire->Body .= "Weekly tables update has been started";
  $mailfire->Body .= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";
  if (!$mailfire->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mailfire->ErrorInfo;
  } else {
    echo 'mail send';
  }
  // $truncateweekly="truncate CRON_TEST ";
  // mysqli_query($conn, $truncateweekly);
  $updatetabledaily = "UPDATE report_status SET status=1";
  mysqli_query($conn, $updatetabledaily);
  /* CHECK wether file found for todays date or not*/
  $query = "SELECT * FROM CRON_TEST where DATETIME='" . $datedb . "' LIMIT 1 ";
  $resquery = mysqli_query($conn, $query);
  if ($resquery->num_rows == 0) {
    foreach ($files as $file) {
      $filename     = basename($file, $ext);
      $names        = explode('_', $filename);
      foreach ($names as $newname) {
        if ($newname == 'TENM') {
          $tablename = 'RMSPHISTFL';
          $mailtable = $filename;
        } else if ($newname == 'HSTL9094') {
          $tablename = 'HSTL9094';
          $mailtable = $filename;
        }
        // if($newname=='RMSPSYSCDE'){
        //   $tablename='RMSPSYSCDE';
        //   $mailtable=$tablename;
        // }else if($newname=='RMDLINRV'){
        //    $tablename='RMDLINRV';
        //   $mailtable=$tablename;
        // }
        else {
          $tablename = '';
        }

        /* get list of table that exist in database*/
        $gettable   = "SELECT TABLE_NAME FROM UPLD_TBL_LST WHERE TABLE_NAME='" . $tablename . "'"; //echo  $gettable ;
        $getrestable = mysqli_query($conn, $gettable);
        $tablerow = mysqli_fetch_assoc($getrestable);
        $table = isset($tablerow['TABLE_NAME']) ? $tablerow['TABLE_NAME'] : '';
        if ($table == $tablename) {
          /* check wether table exist or not*/

          $sql    = "SHOW TABLES LIKE '" . $table . "'"; //echo  $sql;
          $result = mysqli_query($conn, $sql);

          /* if table exist then upload csv*/
          if ($result->num_rows == 1) {
            $myfile =  basename($file);
            if ($filename == 'TENM_01' || $filename == 'HSTL9094') {
              $renameque = "RENAME TABLE " . $tablename . " TO " . $tablename . "_LATEST";
              $renamequeRes = mysqli_query($conn, $renameque);
              $renameque1 = "RENAME TABLE " . $tablename . "_bk TO " . $tablename . " ";
              $renameRes1 = mysqli_query($conn, $renameque1);
              $truncque = "TRUNCATE TABLE " . $tablename . " ";
              $truncRes = mysqli_query($conn, $truncque);
              $renameque3 = "RENAME TABLE " . $tablename . "_LATEST TO " . $tablename . "_bk";
              $renameRes3 = mysqli_query($conn, $renameque3);
            }

            $inserQuery = "INSERT INTO CRON_TEST(DATETIME,STATUS,TABLE_NAME) VALUES ('$datedb','YES','" . $mailtable . "')";
            $resins = mysqli_query($conn, $inserQuery);

            $file = '/var/lib/mysql-files/' . $myfile;
            $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $tablename CHARACTER SET ASCII FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ;"; //echo      $loadSQL;exit;
            $resLoad = mysqli_query($conn, $loadSQL);
            $errno = mysqli_errno($conn);
            $error = mysqli_error($conn);
            $errorlog = $errno . ": " . $error;

            /* if any issue found send email*/
            if ($errno > 0) {
              $mail = new PHPMailer;
              $mail->isSMTP();
              // adding mail after changeing post fix  // nilesh 18-11-25
              $mail->Host = '172.16.13.208';
              // $mail->SMTPAuth = true;
              // $mail->Username = 'pwadmin@aacanet.org';                 
              // $mail->Password = 'december.SURVEY.95';                           
              // $mail->SMTPSecure = 'tls';                           
              $mail->Port = 25;
              $mail->From = 'pwadmin@aacanet.org';
              $mail->FromName = 'CRONWEEKLY BIDEV script notification';
              $mail->addAddress('bandana.kumari@goolean.tech');
              $mail->addAddress('anwar.hussain@goolean.tech');
              $mail->addAddress('nilesh.karanjkar@goolean.tech');
              $mail->addAddress('nitin.kumar@goolean.tech');
              $mail->isHTML(true);
              $mail->Subject = $mailtable . ' Table Warning';
              $mail->Body .= "<p> " . $errorlog . " <p>";
              $mail->Body .= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";
              if (!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
              } else {
                echo 'mail send';
              }
            }
          }
        }
      }
    }
    #RMSPSYSCDE,RMDLINRV,RMSPHISTFL,HSTL9094
    /* At the end send email*/
    $Rmspquery = " SELECT count(*) as total  from RMSPHISTFL";
    $Rmsres    = mysqli_query($conn, $Rmspquery);
    $Rmscount  = mysqli_fetch_assoc($Rmsres);
    $Rmsrows   = isset($Rmscount['total']) ? $Rmscount['total'] : '';

    $Hstlquery  = " SELECT count(*) as total  from HSTL9094";
    $Hstlres    = mysqli_query($conn, $Hstlquery);
    $Hstlcount  = mysqli_fetch_assoc($Hstlres);
    $Hstlrows   = isset($Hstlcount['total']) ? $Hstlcount['total'] : '';

    $mail = new PHPMailer;
    $mail->isSMTP();
    // adding mail after changeing post fix  // nilesh 18-11-25
              $mail->Host = '172.16.13.208';
    // $mail->SMTPAuth = true;
    // $mail->Username = 'pwadmin@aacanet.org';
    // $mail->Password = 'december.SURVEY.95';
    // $mail->SMTPSecure = 'tls';
    $mail->Port = 25;
    $mail->From = 'pwadmin@aacanet.org';
    $mail->FromName = 'CRONWEEKLY BIDEV script notification';
   
    $mail->addAddress('bandana.kumari@goolean.tech');
    $mail->addAddress('anwar.hussain@goolean.tech');
    $mail->addAddress('nilesh.karanjkar@goolean.tech');
    $mail->addAddress('nitin.kumar@goolean.tech');

    $mail->isHTML(true); // Set email format to HTML
    $mail->Subject = ' Weekly tables update';

    $mail->Body .= "<Table border='1'>";
    $mail->Body .= "<thead>";
    $mail->Body .= "<tr>";
    $mail->Body .= "<th>File Name </th>";
    $mail->Body .= "<th>Rows Count</th>";
    $mail->Body .= "</tr>";
    $mail->Body .= "</thead>";
    $mail->Body .= "<tbody>";

    $mail->Body .= "<tr>";
    $mail->Body .= "<td>RMSPHISTFL</td>";
    $mail->Body .= "<td>" . $Rmsrows . "</td>";
    $mail->Body .= "</tr>";

    $mail->Body .= "<tr>";
    $mail->Body .= "<td>HSTL9094</td>";
    $mail->Body .= "<td>" . $Hstlrows . "</td>";
    $mail->Body .= "</tr>";

    $mail->Body .= "</tbody>";
    $mail->Body .= "</table>";
    $mail->Body .= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";
    if (!$mail->send()) {
      echo 'Message could not be sent.';
      echo 'Mailer Error: ' . $mail->ErrorInfo;
    } else {
      echo 'mail send';
    }
    $reupdatetabledaily = "UPDATE report_status SET status=0";
    mysqli_query($conn, $reupdatetabledaily);
  } else {
    echo "already present";
  }
} else {
  echo "No file found for todays date";
  //sleep(900);//for 15 min sleep;
}

?>
