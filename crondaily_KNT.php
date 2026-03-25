<?php 

/*------------------------------------------------------------------------------------*/
// Author - KEANT Technologies       Date: 22 JAN 2025
// Descrition - This script is used to upload daily csv files to mysql database tables
// and send email notification after completion
/*------------------------------------------------------------------------------------*/
/* --------------------------CHANGE LOG ----------------------------*/
/* Author         Date            Description     Search tag  
/* VK22JAN2026   22 JAN 2026     Changed timezone  VK22JAN2026 */
/*------------------------------------------------------------------------------------*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); 

// date_default_timezone_set('US/Eastern');     //VK22JAN2026
date_default_timezone_set('America/New_York');  //VK22JAN2026

ini_set('max_execution_time', 0);
ini_set('memory_limit', '-1');

include_once('PHPMailer/PHPMailerAutoload.php');

$host     ="localhost"; 
$username ="pipewaydb"; 
$password ="A8EK5hKjq*CX&w"; 
$database ="aaca_live";


$conn     = mysqli_connect($host, $username, $password, $database); 
$dir      ='/var/lib/mysql-files'; // The directory containing the files. 
$ext      ='.csv'; // The file extension. 
$files    =glob($dir . '/*' . $ext);

$fileopen =fopen("/var/lib/mysql-files/date_trigger.csv","r");
$fileR    =fgetcsv($fileopen);          
$date     =date('dmY');
$datedb   =date('Y-m-d');



if($date==$fileR[0]){ 
    
    $mailfire = new PHPMailer;

    $mailfire->isSMTP(); 

    $mailfire->Host = 'smtp.office365.com'; 
    // $mail->Host = '172.16.13.208';

    $mailfire->SMTPAuth = true;                             
    $mailfire->Username = 'pwadmin@aacanet.org';                 
    $mailfire->Password = 'december.SURVEY.95';                           
    $mailfire->SMTPSecure = 'tls';                           

    $mailfire->Port = 587;  

    $mailfire->From = 'pwadmin@aacanet.org';   

    $mailfire->FromName = 'CRONDAILY dbadmin script notification';

    // VK22JAN2026 - start
    // $mailfire->addAddress('anwar.hussain@goolean.tech');  
    // $mailfire->addAddress('bandana.kumari@goolean.tech');
    // $mailfire->addAddress('nilesh.karanjkar@goolean.tech');
    // $mailfire->addAddress('nitin.kumar@goolean.tech');

    $mailfire->addAddress('tbalcerzak@aacanet.org');
    $mailfire->addAddress('droberts@aacanet.org');
    $mailfire->addAddress('vishalkul94@gmail.com');

    // VK22JAN2026 - end
   


    $mailfire->isHTML(true); // Set email format to HTML

    $mailfire->Subject = 'Daily tables update has been started';

    $mailfire-> Body.= "Daily tables update has been started";

    $mailfire-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>"; 

    if (!$mailfire->send()) {
        $errorMessage = 'Message could not be sent. Mailer Error: ' . $mailfire->ErrorInfo; // VK22JAN2026
        echo $errorMessage; // VK22JAN2026
        logError($errorMessage); // VK22JAN2026
    } else {
        echo 'mail send'; // VK22JAN2026
    }

  /* CHECK wether file found for todays date or not*/

  $truncatequery="truncate Tbl_File_Upload_Info ";

  mysqli_query($conn, $truncatequery);

  $truncatedaily="truncate CRON_DAILY ";

  mysqli_query($conn, $truncatedaily);



  $updatetabledaily="UPDATE report_status SET status=1";

  mysqli_query($conn, $updatetabledaily);



   /*update table to not allow to pull report from pipeway*/

  $statusupadteQuery="UPDATE DATA_UPDATE_STATUS_CHECK set UPLOAD_STATUS=1";

  mysqli_query($conn,$statusupadteQuery);



  $query="SELECT * FROM CRON_DAILY where DATETIME='".$datedb."' LIMIT 1 ";

  $resquery=mysqli_query($conn, $query);

    if($resquery->num_rows == 0){

       foreach ($files as $file){

        $lastModified = filemtime($file);

        $lastModifiedDate = date("Y-m-d", $lastModified);

         if($lastModifiedDate==$datedb){

        $filename     = basename($file, $ext);

        $names        = explode('_', $filename);

          foreach($names as $newname){

           

            if($newname!='TENM' && $newname!='HSTL9094'){

              $tablename=basename($file, $ext);

              $mailtable=basename($file, $ext);

            }



            else{

              $tablename='';

            }

           

            /* get list of table that exist in database*/

            $gettable   ="SELECT TABLE_NAME FROM UPLD_TBL_LST WHERE TABLE_NAME='".$tablename."'";//echo  $gettable ;

            $getrestable=mysqli_query($conn, $gettable); 

            $tablerow   =mysqli_fetch_assoc($getrestable);

            $table      =isset($tablerow['TABLE_NAME']) ? $tablerow['TABLE_NAME'] : '';

            if($table==$tablename){

          /* check wether table exist or not*/

           $sql = "SHOW TABLES LIKE '" .$table . "'";//echo  $sql;

           $result = mysqli_query($conn, $sql); 

             /* if table exist then upload csv*/ 

             if ($result->num_rows == 1) { 

                $myfile =  basename($file);echo   $myfile;"<br>";

                $renameque="RENAME TABLE " . $table . " TO " . $table . "_LATEST";

                $renameRes=mysqli_query($conn, $renameque);

                $renameque1="RENAME TABLE " . $table . "_bk TO " . $table . " ";

                $renameRes1=mysqli_query($conn, $renameque1);

                $truncque="TRUNCATE TABLE " . $table . " ";

                $truncRes=mysqli_query($conn, $truncque);

                $renameque3="RENAME TABLE " . $table . "_LATEST TO " . $table . "_bk";

                $renameRes3=mysqli_query($conn, $renameque3);

                

            

                $inserQuery="INSERT INTO CRON_DAILY(DATETIME,STATUS,TABLE_NAME) VALUES ('$datedb','YES','".$mailtable."')";

                $resins=mysqli_query($conn, $inserQuery);

             

                // $file='/var/lib/mysql-files/'.$myfile;

                // $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $table CHARACTER SET ASCII FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ;";//echo      $loadSQL;"<br>";

                $file='/var/lib/mysql-files/'.$myfile;

                if($myfile=='WFAACAIMG.csv'){

                   $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $table CHARACTER SET ASCII FIELDS TERMINATED BY ',' escaped by '\b' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ();";//echo      $loadSQL;"<br>";

                }else if($myfile=='HSFLCLNTWF.csv'){

                   $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $table CHARACTER SET ASCII FIELDS TERMINATED BY ',' escaped by '\b' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ();";//echo      $loadSQL;"<br>";

                }else if($myfile=='CMBPAACAB.csv'){

                   $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $table CHARACTER SET ASCII FIELDS TERMINATED BY ',' escaped by '\b' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ();";//echo      $loadSQL;"<br>";

                }else if($myfile=='PLACPFHS.csv'){

                   $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $table CHARACTER SET ASCII FIELDS TERMINATED BY ',' escaped by '\b' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ();";//echo      $loadSQL;"<br>";

                }else{

                 $loadSQL = "LOAD DATA INFILE '$file' IGNORE INTO TABLE $table CHARACTER SET ASCII FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ;";//echo      $loadSQL;"<br>";

                }



                $resloadSql=mysqli_query($conn, $loadSQL);

                $errno =mysqli_errno($conn);

                $error =mysqli_error($conn);

                $errorlog=$errno.": ". $error;

              /* if any issue found send email*/

              if($errno > 0) {
                $errorMessage = $table . ' Table Warning: ' . $errorlog; // VK22JAN2026
                $mail = new PHPMailer; // VK22JAN2026
                $mail->isSMTP(); // VK22JAN2026
                $mail->Host = 'smtp.office365.com'; // VK22JAN2026
                $mail->SMTPAuth = true; // VK22JAN2026
                $mail->Username = 'pwadmin@aacanet.org'; // VK22JAN2026
                $mail->Password = 'december.SURVEY.95'; // VK22JAN2026
                $mail->SMTPSecure = 'tls'; // VK22JAN2026
                $mail->Port = 587; // VK22JAN2026
                $mail->From = 'pwadmin@aacanet.org'; // VK22JAN2026
                $mail->FromName = 'CRONDAILY dbadmin script notification'; // VK22JAN2026
                
                // VK22JAN2026 - start 
                //$mail->addAddress('anwar.hussain@goolean.tech'); // VK22JAN2026
                // $mail->addAddress('bandana.kumari@goolean.tech'); // VK22JAN2026
                // $mail->addAddress('nilesh.karanjkar@goolean.tech'); // VK22JAN2026
                // $mail->addAddress('nitin.kumar@goolean.tech'); // VK22JAN2026
                $mail->addAddress('tbalcerzak@aacanet.org');
                $mail->addAddress('droberts@aacanet.org');
                $mail->addAddress('vishalkul94@gmail.com');
                // VK22JAN2026 - end

                $mail->isHTML(true); // VK22JAN2026
                $mail->Subject = $table . ' Table Warning'; // VK22JAN2026
                $mail->Body = "<p>$errorlog</p><div style='margin-bottom:10px'>Don't Reply on this mail</div>"; // VK22JAN2026

                if (!$mail->send()) {
                    $errorMessage = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo; // VK22JAN2026
                    echo $errorMessage; // VK22JAN2026
                    logError($errorMessage); // VK22JAN2026
                } else {
                    echo 'mail send'; // VK22JAN2026
                }

                // Log the error
                logError($errorMessage); // VK22JAN2026
            }

              

                $rowcountquery="SELECT count(1) as total  from ".$table." ";

                $rescount     =mysqli_query($conn, $rowcountquery);

                $rowcount     = mysqli_fetch_assoc($rescount); 

                $count        =isset($rowcount['total']) ? $rowcount['total'] : '';

                $datetime     =date('Y-m-d h:i:s a');

                $inserttbl="INSERT INTO Tbl_File_Upload_Info(FileName,Num_of_file_from_mysql,DateTime) VALUES ('$mailtable','$count','$datetime')";

                $resinserttbl=mysqli_query($conn, $inserttbl);

      

                }



                ob_flush();

                flush();

               //sleep(300000);

              }

          }

       }

     }

            /* At the end send email for table update*/

          $mailsendquery="SELECT FileName,Num_of_file_from_mysql,DateTime FROM Tbl_File_Upload_Info  order by DateTime asc";

          $resmailquery=mysqli_query($conn,$mailsendquery);

          $mail = new PHPMailer;

          $mail->isSMTP(); 

          $mail->Host = 'smtp.office365.com'; 
          //  $mail->Host = '172.16.13.208';

          $mail->SMTPAuth = true;                             
          $mail->Username = 'pwadmin@aacanet.org';                 
          $mail->Password = 'december.SURVEY.95';                           
          $mail->SMTPSecure = 'tls';                           

          $mail->Port = 587;  

          $mail->From = 'pwadmin@aacanet.org';   

          $mail->FromName = 'CRONDAILY dbadmin script notification';

          // VK22JAN2026 - start
          // $mail->addAddress('anwar.hussain@goolean.tech');
          // $mail->addAddress('bandana.kumari@goolean.tech');
          // $mail->addAddress('nilesh.karanjkar@goolean.tech');
          // $mail->addAddress('nitin.kumar@goolean.tech');
          $mail->addAddress('tbalcerzak@aacanet.org');
          $mail->addAddress('droberts@aacanet.org');
          $mail->addAddress('vishalkul94@gmail.com');
          //VK22JAN2026 - end
          


          $mail->isHTML(true); // Set email format to HTML

          $mail->Subject = 'Daily tables update';



          $mail-> Body.= "<Table border='1'>";

          $mail-> Body.= "<thead>";

          $mail-> Body.= "<tr>";

          $mail-> Body.= "<th> File Name </th>";

          $mail-> Body.= "<th>Mysql Rows Count</th>";

          $mail-> Body.= "<th>DateTime</th>";

          $mail-> Body.= "</tr>";

          $mail-> Body.= "</thead>";

          $mail-> Body.= "<tbody>";

          while($mailrow=mysqli_fetch_assoc($resmailquery)){

          $mail-> Body.= "<tr>";

          $mail-> Body.= "<td>".$mailrow['FileName']."</td>";

          $mail-> Body.= "<td>".$mailrow['Num_of_file_from_mysql']."</td>";

          $mail-> Body.= "<td>".$mailrow['DateTime']."</td>";

          $mail-> Body.= "</tr>";

          }

          $mail-> Body.= "</tbody>";

          $mail-> Body.= "</table>";

          $mail-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>"; 

          if(!$mail->send()) {

            echo 'Message could not be sent.';

            echo 'Mailer Error: ' . $mail->ErrorInfo;

          } else {

           $msg= 'mail send';



          }

        /* At the end send email for table update ends here*/

        /*call procedure and send email*/

        $day=date("w");

     

       if($day >=2 && $day <=5){

         //$procedure       ="CALL COMPLETE_UPDATE()";
 
          $procedure      ="CALL INCRMNT_UPDATE()";

         $resprocedure    =mysqli_query($conn,$procedure);

         $procerrno       =mysqli_errno($conn);

         $errorproc       =mysqli_error($conn);

         $errorlogproc    =$procerrno.": ". $errorproc;

           if($procerrno > 0){//if part

            $mail = new PHPMailer;

            $mail->isSMTP(); 

            $mail->Host = 'smtp.office365.com'; 
              // $mail->Host = '172.16.13.208';

            $mail->SMTPAuth = true;                             
            $mail->Username = 'pwadmin@aacanet.org';                 
            $mail->Password = 'december.SURVEY.95';                           
            $mail->SMTPSecure = 'tls';                           

            $mail->Port = 587;  

            $mail->From = 'pwadmin@aacanet.org';   

            $mail->FromName = 'CRONDAILY dbadmin script notification';

            // VK22JAN2026 - start
            //$mail->addAddress('anwar.hussain@goolean.tech');
            //$mail->addAddress('bandana.kumari@goolean.tech');
            //$mail->addAddress('teasterling@aacanet.org');
            $mail->addAddress('twright@aacanet.org');
            $mail->addAddress('Daniel@aacanet.org');
            //$mail->addAddress('nilesh.karanjkar@goolean.tech');
            //$mail->addAddress('nitin.kumar@goolean.tech');
            $mail->addAddress('tbalcerzak@aacanet.org');
            $mail->addAddress('droberts@aacanet.org');
            $mail->addAddress('vishalkul94@gmail.com');

            // VK22JAN2026 - end


            $mail->isHTML(true);

            $mail-> Subject= 'INCRMNT_UPDATE';//'COMPLETE_UPDATE';

            $mail-> Body.= "<p> ".$errorlogproc. " <p>";

            $mail-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";

              if(!$mail->send()) {

                echo 'Message could not be sent.';

                echo 'Mailer Error: ' . $mail->ErrorInfo;

              } else {

                echo 'mail send';

              }

          } else {//else part

            $mail = new PHPMailer;

            $mail->isSMTP(); 

            $mail->Host = 'smtp.office365.com';
            // $mail->Host = '172.16.13.208'; 

            $mail->SMTPAuth = true;                             
            $mail->Username = 'pwadmin@aacanet.org';                 
            $mail->Password = 'december.SURVEY.95';                           
            $mail->SMTPSecure = 'tls';                           

            $mail->Port = 587;  

            $mail->From = 'pwadmin@aacanet.org';   

            $mail->FromName = 'CRONDAILY dbadmin script notification';
            
            // VK22JAN2026 - start

            //$mail->addAddress('anwar.hussain@goolean.tech');
            //$mail->addAddress('bandana.kumari@goolean.tech');
            //$mail->addAddress('teasterling@aacanet.org');
            $mail->addAddress('twright@aacanet.org');
            $mail->addAddress('Daniel@aacanet.org');
            //$mail->addAddress('nilesh.karanjkar@goolean.tech');
            //$mail->addAddress('nitin.kumar@goolean.tech');
            $mail->addAddress('tbalcerzak@aacanet.org');
            $mail->addAddress('droberts@aacanet.org');
            $mail->addAddress('vishalkul94@gmail.com');

            // VK22JAN2026 - end
 

            $mail->isHTML(true);

            $mail-> Subject= 'Daily Data Refresh Process Completed';

            $mail-> Body.= "<p>Daily data refresh process has been completed successfully.<p>";

            $mail-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";

                if(!$mail->send()) {

                  echo 'Message could not be sent.';

                  echo 'Mailer Error: ' . $mail->ErrorInfo;

                } else {

                 echo 'mail send';



                }

            }

          }

        if($day ==1){

         $procedure1       ="CALL COMPLETE_UPDATE()";

         $resprocedure1    =mysqli_query($conn,$procedure1);

         $procerrno1       =mysqli_errno($conn);

         $errorproc1       =mysqli_error($conn);

         $errorlogproc1    =$procerrno1.": ". $errorproc1;

           if($procerrno1 > 0){

            $mail = new PHPMailer;

            $mail->isSMTP(); 

            $mail->Host = 'smtp.office365.com';
            // $mail->Host = '172.16.13.208'; 

            $mail->SMTPAuth = true;                             
            $mail->Username = 'pwadmin@aacanet.org';                 
            $mail->Password = 'december.SURVEY.95';                           
            $mail->SMTPSecure = 'tls';                           

            $mail->Port = 587;  

            $mail->From = 'pwadmin@aacanet.org';   

            $mail->FromName = 'CRONDAILY BIDEV script notification';

            // VK22JAN2026 - start

            //$mail->addAddress('anwar.hussain@goolean.tech');
            //$mail->addAddress('bandana.kumari@goolean.tech');
            $mail->addAddress('teasterling@aacanet.org');
            $mail->addAddress('twright@aacanet.org');
            $mail->addAddress('Daniel@aacanet.org');
            //$mail->addAddress('nilesh.karanjkar@goolean.tech');
            //$mail->addAddress('nitin.kumar@goolean.tech');
            $mail->addAddress('tbalcerzak@aacanet.org');
            $mail->addAddress('droberts@aacanet.org');
            $mail->addAddress('vishalkul94@gmail.com');

            // VK22JAN2026 - end
          

            $mail->isHTML(true);

            $mail-> Subject= 'COMPLETE_UPDATE';

            $mail-> Body.= "<p> ".$errorlogproc1. " <p>";

            $mail-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";

            if(!$mail->send()) {

              echo 'Message could not be sent.';

              echo 'Mailer Error: ' . $mail->ErrorInfo;

          } else {

                echo 'mail send';

             

          }

            }else{//else part

            $mail = new PHPMailer;

            $mail->isSMTP(); 

            $mail->Host = 'smtp.office365.com'; 
            // $mail->Host = '172.16.13.208'; 

            $mail->SMTPAuth = true;                             
            $mail->Username = 'pwadmin@aacanet.org';                 
            $mail->Password = 'december.SURVEY.95';                           
            $mail->SMTPSecure = 'tls';                           

            $mail->Port = 587;  

            $mail->From = 'pwadmin@aacanet.org';   

            $mail->FromName = 'CRONDAILY BIDEV script notification';

            //$mail->addAddress('anwar.hussain@goolean.tech');
            //$mail->addAddress('bandana.kumari@goolean.tech');
            //$mail->addAddress('teasterling@aacanet.org');
            $mail->addAddress('twright@aacanet.org');
            $mail->addAddress('Daniel@aacanet.org');
            //$mail->addAddress('nilesh.karanjkar@goolean.tech');
            //$mail->addAddress('nitin.kumar@goolean.tech');
            $mail->addAddress('tbalcerzak@aacanet.org');
            $mail->addAddress('droberts@aacanet.org');
            $mail->addAddress('vishalkul94@gmail.com');


            $mail->isHTML(true);

            $mail-> Subject= 'COMPLETE_UPDATE';

            $mail-> Body.= "<p> COMPLETE_UPDATE Procedure has been updated successfully.<p>";

            $mail-> Body.= "<div style='margin-bottom:10px'>Don't Reply on this mail</div>";

            if(!$mail->send()) {

              echo 'Message could not be sent.';

              echo 'Mailer Error: ' . $mail->ErrorInfo;

          } else {

                echo 'mail send';

             

          }

            }

          }

        /*call procedure and send email ends here*/

    $reupdatetabledaily="UPDATE report_status SET status=0";

    mysqli_query($conn, $reupdatetabledaily);



     /*update table to not allow to pull report from pipeway*/

    $statusupadteQuery1="UPDATE DATA_UPDATE_STATUS_CHECK set UPLOAD_STATUS=0";

    mysqli_query($conn,$statusupadteQuery1);

    }else{

      echo "already present";

    }

}

else{

  echo "No file found for todays date";

  //sleep(900);//for 15 min sleep;

}



// Add a function to log errors with date and timestamp
// VK22JAN2026
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents('crondaily_job_error_log.txt', $logMessage, FILE_APPEND); // VK22JAN2026
}

?>

