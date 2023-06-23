#!/bin/bash
# wrapper around masterrt to set up a environment variables
# setting environment in httpd.conf would probably be better
#if [ -z "${TPP_HOME}" ]; then . /usr/local/etc/aqms.env; fi
. /usr/local/etc/aqms.env;
${TPP_BIN_HOME}/masterrt.pl
