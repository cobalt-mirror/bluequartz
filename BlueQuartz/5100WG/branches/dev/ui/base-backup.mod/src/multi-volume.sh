#!/bin/bash

# $Id: multi-volume.sh 201 2003-07-18 19:11:07Z will $
#
# Cobalt Multi-volume archive script
# Author: Jeff Lovell <jlovell@cobalt.com>
# Copyright 2000 Cobalt Networks, Inc.
# http://www.cobalt.com

CUR_ARCHV=$1	# The name of the current Archive needing renamed
NEW_ARCHV=""	# Name of the archive to move the current one to
NAME_BASE=$2	# Base of the archive nameing scheme: base = base[n].tar

function get_nextvol
# Return the next available name for archival renaming
{
        let count=0
        while [ -d /tmp ];
        do
                if [ ! -f $NAME_BASE$count.tar ]; then
                        NEW_ARCHV=$NAME_BASE$count.tar
                        return
                fi
                let count=count+1
        done
}

function usage
# Return simple usage to user
{
        echo "Usage: $0 <current archive name> <new archive base name>"
}

# Main
if [ ! -f $CUR_ARCHV ]; then
        echo "no such file: $CUR_ARCHV"
        exit 1
fi

get_nextvol

mv -f "$CUR_ARCHV" "$NEW_ARCHV"

# Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
# 
# Redistribution and use in source and binary forms, with or without 
# modification, are permitted provided that the following conditions are met:
# 
# -Redistribution of source code must retain the above copyright notice, 
# this list of conditions and the following disclaimer.
# 
# -Redistribution in binary form must reproduce the above copyright notice, 
# this list of conditions and the following disclaimer in the documentation  
# and/or other materials provided with the distribution.
# 
# Neither the name of Sun Microsystems, Inc. or the names of contributors may 
# be used to endorse or promote products derived from this software without 
# specific prior written permission.
# 
# This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
# 
# You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
