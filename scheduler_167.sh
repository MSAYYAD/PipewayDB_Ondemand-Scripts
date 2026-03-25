#! /bin/bash
#sys_cur_date=$(date +"%Y-%m-%d")
starttime=$(date +"%s")
day=$(date +"%u")
 
    #php -f  /var/www/html/cron/hello.php
    #sleep 1

while  read -r f1 f2  < <(mysql -h 192.168.13.167 -u admin167 -pAaca@123#  -D aaca_live  -e 'SELECT LAST_EXEC_LINE_NO, DATE(LAST_EXEC_DATE_TIME) FROM SP_ERROR_HNDLNG ORDER BY LAST_EXEC_LINE_NO  desc LIMIT 1' | tail -1)
do
     now=$(date +"%Y-%m-%d")
     mail_flag=$(mysql -h 192.168.13.167 -u admin167 -pAaca@123#  -D aaca_live  -e 'SELECT mail_flag FROM SP_ERROR_HNDLNG order by LAST_EXEC_LINE_NO desc limit 1' | tail -1 )
     count=$(mysql -h 192.168.13.167 -u admin167 -pAaca@123#  -D aaca_live  -e 'SELECT count(1) FROM SP_ERROR_HNDLNG' | tail -1)
     if [[ "$day" == 1  &&   "$count" == 126  &&   "$mail_flag" == 0  ]] || [[  "$day" == 2  &&   "$count" == 134  &&   "$mail_flag" == 0   ]] || [[  "$day" == 3  &&   "$count" == 134  &&   "$mail_flag" == 0   ]] || [[  "$day" == 4  &&   "$count" == 134  &&   "$mail_flag" == 0    ]] || [[  "$day" == 5  &&   "$count" == 134   &&   "$mail_flag" == 0   ]] ; then
echo -e "Subject: DAILY UPDATE PROCESS COMPLETED \n\n Daily Data Update Process has been completed,scheduled reports are in progress..." | sendmail bandana.kumari@goolean.tech deepak.verma@goolean.tech anwar.hussain@knovaone.com
mysql -h 192.168.13.167 -u admin167 -pAaca@123#  -D aaca_live  -e 'update SP_ERROR_HNDLNG set mail_flag ='1' '
     fi
  if [[ "$day" == 1  &&  "$f1" == 126  &&  "$f2" == $now  && "$count" == 126  ]] || [[  "$day" == 2  &&  "$f1" == 134  &&  "$f2" == $now  && "$count" == 134 ]] || [[  "$day" == 3  &&  "$f1" == 134  &&  "$f2" == $now  && "$count" == 134 ]] || [[  "$day" == 4  &&  "$f1" == 134  &&  "$f2" == $now  && "$count" == 134  ]] || [[  "$day" == 5  &&  "$f1" == 134  &&  "$f2" == $now  && "$count" == 134 ]] ; then
       
        php -f  /var/www/html/cron/DailyReport.php
        #echo "hello"
        sleep 60
        #break
else
	sleep 15
    endtime=$(date +"%s")
    mail_time=$(($endtime - $starttime))
    if [[ $mail_time -eq 1800 || $mail_time -eq 3600 ||  $mail_time -eq 5400  || $mail_time -eq 7200 ]] ; then
   sleep 1
    echo "Subject: waiting for DAILY DATA REFRESHMENT" | sendmail bandana.kumari@goolean.tech deepak.verma@goolean.tech anwar.hussain@knovaone.com
   #using elif statement to stop infinte loop here.
  elif [[ $mail_time -gt 7200   &&   "$mail_flag" == 0 ]]; then
     echo "Subject: DAILY REPORTS ARE OVER FOR TODAY" | sendmail bandana.kumari@goolean.tech deepak.verma@goolean.tech anwar.hussain@knovaone.com
     exit;
  fi
  if   [[ $mail_time -gt 7200   &&   "$mail_flag" == 1 ]]  ; then 
 echo "Subject: DAILY REPORTS ARE OVER FOR TODAY" | sendmail bandana.kumari@goolean.tech deepak.verma@goolean.tech anwar.hussain@knovaone.com  
 exit; 
fi
fi
done
