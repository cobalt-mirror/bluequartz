#!/usr/bin/perl -w
# $Id: :sysreset:linkToWizard.pl 201 2003-07-18 19:11:07Z will $
# Copyright 2000-2002 Sun Microsystems, Inc., All rights reserved.

# use these two packages to make sure diretories needed exist
use lib qw(/usr/sausalito/perl);
use Sauce::Config;
use File::Path;
use File::Copy;
use Base::HomeDir qw(homedir_get_group_dir);

# default to assuming the existence of some home directory for use as the
# default since the qube puts the public server in /home/groups/home/web
# and raqs don't care
# temporary hack because get_group_dir hashes
my $default_webdir = '/home/sites/home/web';
$default_webdir = '/home/groups/home/web' if (-d '/home/groups');

# default welcome file, this should be product specific, so I'm assuming a standard name
my $welcome_file = '/wizardlogin.cgi';
my $welcome_qual = '/usr/sausalito/ui/web'.$welcome_file;
my $welcome_src = '/usr/sausalito/ui/web/base/wizard/wizardlogin.cgi';

my $fileName80 = "$default_webdir/index.html";
my $fileName81 = "/usr/sausalito/ui/web/index.html";

# need to make sure directories exists otherwise this won't work
mkpath($default_webdir, 0755) unless( -d $default_webdir );

mkpath("/usr/sausalito/ui/web", 1, 0755) unless(-d "/usr/sausalito/ui/web");

open(FILE, ">$fileName80")
    || die("Error: linkToWizard.pl: Cannot open file $fileName80\n");
print FILE <<END;
<HTML>
<HEAD>
<META HTTP-EQUIV="expires" CONTENT="-1">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</HEAD>
<BODY onLoad=\"location='http://'+location.host+':444/'\">
</BODY>
</HTML>
END
close(FILE);

# it is just a temporary file and everyone should be able to clean it up if
# it is left unclean
chmod(0644, $fileName80);

open(FILE, ">$fileName81")
    || die("Error: linkToWizard.pl: Cannot open file $fileName81\n");
print FILE <<END;
<HTML>
<HEAD>
<META HTTP-EQUIV="expires" CONTENT="-1">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
</HEAD>
<BODY onLoad=\"location='$welcome_file'\">
</BODY>
</HTML>
END
close(FILE);

chmod(0644, $fileName81);

unlink($welcome_file);
copy($welcome_src, $welcome_qual);
chmod(0755, $welcome_qual);

exit 0;
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
