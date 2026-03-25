#!/bin/bash
#`````````````````````````````````````````````````````````````````````````````````````````````````````````````|
# Author - Bandana     Date - 9th June 2021                                                                   |
# Aim - 1.This script will first fetch archieved files name from aaca_backup.MY_UPLOADS in exclude_list.txt   |
#       2. it will exclude the names listed in exclude_list.txt                                               |
#       3. than it will start syncing                                                                         |
#        folders source (/home/fileadmin/bandana/rsync_test/*) and                                            |
#        destination(/home/fileadmin/ashish/rsync_test_destination1/)                                         |                                                            
#`````````````````````````````````````````````````````````````````````````````````````````````````````````````

MYSQL_HOST='localhost' 
MYSQL_PORT='3306'
MYSQL_USER='biuser'
DATABASE_NAME='aaca_live'
PASSWORD="B@dcr3dit"


#this command will fetch file names from MY_UPLOADS table in  exclude_list.txt present in source directory
mysql  -u biuser -p$PASSWORD ${DATABASE_NAME} -e 'SELECT FileName FROM `MY_DOWNLOAD_ARCHIVE` WHERE archiveFlag=1' > /home/biuser/mako_exclude/exclude_list.txt

#this command will satrt syncing of source to destination and excluding archived files 
#rsync -avxP --exclude-from /home/fileadmin/bandana/rsync_test/exclude_list.txt /home/fileadmin/bandana/rsync_test/* /home/fileadmin/ashish/rsync_test_destination1/



#rsync -av --exclude={'test1.txt','test2.txt'} /home/fileadmin/bandana/rsync_test/* /home/fileadmin/ashish/rsync_test_destination1/

#rsync -avxPn --exclude-from /home/fileadmin/bandana/rsync_test/exclude_list.txt /home/fileadmin/bandana/rsync_test/* lenovo@192.168.0.169:D:\vandana\vandy
# a - archieve mode
# x - one file system (don't cross filesystem boundaries)
# P - partial-progress partial keeps the partial file, progress shows the progress of the transfer.
# n - dry run perform a trial run with no change mode
