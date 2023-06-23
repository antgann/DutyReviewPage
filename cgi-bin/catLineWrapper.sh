#!/bin/bash
# setting environment in httpd.conf would probably be better
#if [ -z "${TPP_HOME}" ]; then . /usr/local/etc/aqms.env; fi
. /usr/local/etc/aqms.env
${TPP_BIN_HOME}/catone.pl -t $1
