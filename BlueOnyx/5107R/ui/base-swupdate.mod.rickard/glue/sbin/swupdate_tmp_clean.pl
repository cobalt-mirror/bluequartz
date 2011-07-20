#!/usr/bin/perl
# $Id: swupdate_tmp_clean.pl Tue 17 May 2011 09:58:09 PM CEST mstauber $
# Copyright 2011 Team BlueOnyxc. All rights reserved.
#
# When PKGs are installed through the GUI, we cannot restart CCEd. Although we often
# need to do that. Otherwise the PKG install fails if CCEd is installed while the GUI
# pages jump through the install procedures and realize that CCEd died under them.
#
# This script helps us in two ways:
#
# It periodically checks every 15 minutes (through a cronjob) if the file
# /tmp/.guipkginstall exists.
#
# If it exists, a PKG was recently installed through the GUI. If that's the case, 
# then we restart CCEd and delete the file.

if (-f "/tmp/.guipkginstall") {
    system("/etc/init.d/cced.init restart > /dev/null 2>&1&");
    system("/bin/rm -f /tmp/.guipkginstall");
}

exit(0);
