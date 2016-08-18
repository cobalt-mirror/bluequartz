#!/usr/bin/python
#
# Version: 0.1.0
#
# A plugin for the Yellowdog Updater Modified which deals with CCEd restarts
# on BlueOnyx servers.
#
# To install this plugin, just drop it into /usr/lib/yum-plugins, and
# make sure you have 'plugins=1' in your /etc/yum.conf. The config 
# file for this plugin is at /etc/yum/pluginconf.d/blueonyx.conf
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# (C) Copyright 2016 Michael Stauber <mstauber@blueonyx.it
#

"""
B{BlueOnyx} is a Yum plugin which handles BlueOnyx specific cleanup
tasks after YUM updates on BlueOnyx servers.
"""

from yum.plugins import PluginYumExit, TYPE_CORE, TYPE_INTERACTIVE
from subprocess import call

requires_api_version = '2.3'
plugin_type = (TYPE_CORE, TYPE_INTERACTIVE)

def close_hook(conduit):
    call(["/usr/sausalito/sbin/cced_yum", ""])
    call(["/bin/chmod 777 /var/lib/php/session", ""])
