#!/bin/bash
set -x
#directory containing CSV files
# csv_directory="/var/www/html/bi/dist/BAYREADFILE"
# cd $csv_directory

# for f in *.csv
# do
  
# while IFS=',' read  field1 field2 field3 < <(tail -1 /var/www/html/bi/dist/BAYREADFILE/$f | tr -d '"')
# do

# filename="$field1" 
# #filename="//BAY/judmedia/MAA#485215-REPO_485215_REPO-2.PDF"
# #echo "$filename"
# sftp -i /var/www/.ssh/id_rsa bkumari@192.168.21.26 <<EOF
# lcd /var/www/html/bi/dist/BAYREADFILE
# get "$filename"
# exit
# EOF
# cd /var/www/html/bi/dist/BAYREADFILE/
# chmod 777 "$field2"
# rm "$field3"

# done < /var/www/html/bi/dist/BAYREADFILE/"$f"

# done

#directory containing CSV files
csv_directory="/var/www/html/bi/dist/BAYREADFILE"
cd $csv_directory

for f in *.csv
do
	
#sed -i 's/&amp;/\&/g' "$f"
	 
while IFS='|' read  field1 field2 field3 < <(tail -1 /var/www/html/bi/dist/BAYREADFILE/$f | tr -d '"')
do
filename="$field1" 
#filename="//BAY/judmedia/MAA#485215-REPO_485215_REPO-2.PDF"


sftp -i /var/www/.ssh/id_rsa bkumari@192.168.21.26 <<EOF
lcd /var/www/html/bi/dist/BAYREADFILE
get "$filename"
exit
EOF
cd /var/www/html/bi/dist/BAYREADFILE/
chmod 777 "$field2"
 
#mv $f $field3

rm "$field3"

done < /var/www/html/bi/dist/BAYREADFILE/"$f"

done