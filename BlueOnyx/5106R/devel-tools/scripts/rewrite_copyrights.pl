#!/usr/bin/perl
# $Id: rewrite_copyrights.pl 3 2003-07-17 15:19:15Z will $
# Copyright 2001 Sun Microsystems, Inc., All rights reserved.

# Tweak the regular expressions to fit non-perl languages.
# This script inserts a CVS id tag at line 2, and the
# Sun copyright at line three.  It also strips out what it things
# are old copyrights and cvs id's.  

my $copyright =<<EOF;
# Copyright 2000, 2001 Sun Microsystems, Inc., All rights reserved.
EOF

my $cvsid =<<EOF;
# \$Id\$
EOF

while(<>) {
	next if (/^\s*\#/ || !$_);
	chomp;
	my $file = $_;
	if(-r $file)
	{
		my $found = 0;
		my $script;
		open(SCRIPT, $file) || warn "*** Could not open $file: $!";
		$script = <SCRIPT>; # #!/usr/bin/feh
		$script .= $cvsid;
		$script .= $copyright;
		while(<SCRIPT>)
		{
			if (/^\s*\#\s*copyright/i || 
			    /^\s*\#\s*\$Id:\s+/   ||
			    /^\s*\#\s*\$Id\$\s*$/)
			{
				# $script .= "WAS: $_";
				warn "Discarding $file line:\n$_\n";
			}
			else
			{
				$script .= $_;
			}
		}
		close(SCRIPT);

		warn "Rewriting script $file...\n";
		rename($file, $file.'~') || warn "Could not rename $file: $!";

		if($script)
		{
			open(NUSCRIPT, ">$file") || die "Could not write $file: $!";
			print NUSCRIPT $script;
			close(NUSCRIPT);
		}
			
	}
	else
	{
		warn "*** Could not read $_\n";
	}
}
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
