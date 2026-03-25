#! /bin/bash

# Author - Bandana Kumari             Created on - 14 April 2021
# Supervisor - Ashish Diwakar
# Purpose - This program will wait for AS400 to send trigger file (date_trigger.csv), upon which this script 
#           will call crondaily.php script, this is a wrapper program the actual data upload is happening in php script
#           This script will send email to monitoring group in every half an hour, if the trigger file from AS400 
#           is not received. It will wait for max 2 hours, upon completion of 2 hours script will end itself
#           and send out a final email of failure to monitoring group.
#
#           This script will execute daily (from monday to friday)
# Developed for AACANet, for any queries contact admin or creators of this program.
#
#```````````````````````````````````````````````````````````````````````````````````````````````````````````````````````
# Add modification log below
# Author - KEANT Technologies                              Date - 22 JAN 2026
# Description - use crondaily_KNT.php instead of crondaily.php
# Search tag - VK22JAN2026
#```````````````````````````````````````````````````````````````````````````````````````````````````````````````````````

#````````````````````````````````````````Begining of mainline `````````````````````````````````````````````````````````
# Fetch current date
sys_cur_date=$(date +"%d%m%Y")

# fetch seconds since January 1, 1970(epoch)
starttime=$(date +"%s")


while IFS=',' read  value date  < <(tail -1 /var/lib/mysql-files/date_trigger.csv | tr -d '"')
  do
  if [[ $sys_cur_date == $value ]]; then
    
    # php -f  /var/www/html/cron/crondaily.php >> /home/pipewaydb/crondaily_job_error_log.txt    # VK22JAN2026
    php -f  /var/www/html/cron/crondaily_KNT.php >> /home/pipewaydb/crondaily_job_error_log.txt     # VK22JAN2026
    
    break 
  else
    

    endtime=$(date +"%s")
    mail_time=$(($endtime - $starttime))
    #loop to send email at interval of 30 minute

    if [[ $mail_time -eq 1800 || $mail_time -eq 3600 ||  $mail_time -eq 5400  || $mail_time -eq 7200 ]] ; then
      sleep 1
      echo "Subject: Daily_script waiting for trigger from AS400" | sendmail Anwar.hussain@goolean.tech bandana.kumari@goolean.tech puja.kumari@goolean.tech nitin.kumar@goolean.tech nilesh.karanjkar@goolean.tech 

   #using elif statement to stop infinte loop here.
    elif [[ $mail_time -gt 7200 ]]; then
      echo "Subject: No trigger found for today's date for Daily_script" | sendmail Anwar.hussain@goolean.tech puja.kumari@goolean.tech bandana.kumari@goolean.tech nitin.kumar@goolean.tech nilesh.karanjkar@goolean.tech
      exit;
    fi
  fi
done < /var/lib/mysql-files/date_trigger.csv
#````````````````````````````````End of script ``````````````````````````````````````````````````````````````````````
