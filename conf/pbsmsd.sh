#!/bin/sh
#
# pbsmsd.sh:
# FreeBSD-style rc.d script for PledgeBank SMS daemon.
#
# $Id: pbsmsd.sh,v 1.1 2005-04-05 17:09:15 chris Exp $
#

# PROVIDE: pbsmsd
# REQUIRE: LOGIN
# BEFORE:  securelevel
# KEYWORD: FreeBSD shutdown

. "/etc/rc.subr"

name="pbsmsd"
rcvar=`set_rcvar`

command="/data/vhost/www.pledgebank.com/mysociety/pb/bin/pbsmsd"
command_args=""
pidfile="/data/vhost/www.pledgebank.com/$name.pid"

# read configuration and set defaults
load_rc_config "$name"

: ${pbsmsd_user="pb"}
: ${pbsmsd_chdir="/data/vhost/www.pledgebank.com/mysociety/pb/bin"}
: ${command_interpreter="/usr/bin/perl"}
run_rc_command "$1"
