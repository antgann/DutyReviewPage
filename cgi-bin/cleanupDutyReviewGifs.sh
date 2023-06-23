#!/bin/bash
#
# Generic event directory cleaner
#
if [ -z "${TPP_HOME}" ]; then . /usr/local/etc/aqms.env; fi

#This script has hard-coded host network dependencies

#Where the files to be deleted are located
gifroot=$PP_HOME/www/review/eventfiles/gifs

# default days back age to delete
daysBack=30

# check command line args 
if [ "$1" == "-h" ]; then
  echo "  Usage: $0 [maxDaysAge (default=$daysBack)]"
  echo "  removes the evid 'gif' files below ${gifroot} older than maxDaysAge"
  exit
fi

# check command line 
if [ $# -gt 0 ]; then
    daysBack=${1}
fi

if [[ ! $daysBack =~ [0-9]+ ]]; then
 echo "Input maxdays: '$daysBack', must be an integer value"
 exit
fi

if [ ! -d "$gifroot" ]; then
 echo "cleanup dir does not exist: $gifroot"
 exit
fi

timetag=`date -u +"%Y%m%d%H%M"`
#one log per calendar day UTC
logroot="$PP_HOME/www/review/logs"
logfile="$logroot/cleanupReviewGifs_${timetag:0:8}.log"
# NOTE: Must add -ls or -print option at end (not at start) of find  to see what files are being deleted
{
  echo "$0 start at `date -u +'%F %T %Z'`";
  echo "  Found `find $gifroot -name "[0-9]*.gif" -mtime +${daysBack} | wc | awk '{print $1}'` gifs files older than $daysBack days"
  find $gifroot -name "[0-9]*.gif" -mtime +${daysBack} -ls -exec \rm '{}' \;
  echo "  Found `find $logroot -name "cleanupReviewGifs_*.log" -mtime +${daysBack} | wc | awk '{print $1}'` log files older than $daysBack days"
  find $logroot -name 'cleanupReviewGifs_*.log' -mtime +${daysBack} -ls -exec \rm '{}' \;
  echo "$0 done  at `date -u +'%F %T %Z'`";
} >>$logfile 2>&1
