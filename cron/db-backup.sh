#!/bin/bash

TIMESTAMP=$(date +"%F")
DIR="$( cd "$( dirname "$0" )" && pwd )"

BACKUP_DIR="/backup/databases"

source $DIR/.env

mkdir -p $BACKUP_DIR/$TIMESTAMP

find "$BACKUP_DIR" -mtime +3 -type d -exec rm -rf {} \;

databases=`$MYSQL -u $MYSQL_USER -h $MYSQL_HOST -p$MYSQL_PASSWORD -e "show databases"| grep -Ev "(database|Database|information_schema|performance_schema|phpmyadmin|mysql|sys)"`

for db in $databases; do

$MYSQLDUMP -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD --databases --triggers --routines --events $db > $BACKUP_DIR/$TIMESTAMP/$db.sql

$PHP $DIR/../gdrive/cli.php $BACKUP_DIR/$TIMESTAMP/$db.sql $GOOGLE_DRIVE_FOLDER_ID $TIMESTAMP/$db.sql

done