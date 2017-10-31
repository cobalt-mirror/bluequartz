#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_apache_settings.pl
#
# This script parses blueonyx.conf and brings CODB up to date on how Apache is configured.

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
$whatami = "constructor";
#$whatami = "handler";

# Location of blueonyx.conf:
$blueonyx_conf = "/etc/httpd/conf.d/blueonyx.conf";

#
#### No configureable options below!
#

use CCE;
use Data::Dumper;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();
}
else {
    $cce->connectuds();
}

# Config file present?
if (-f $blueonyx_conf) {

	# Array of PHP config switches that we want to update in CCE:
	&items_of_interest;

	# Read, parse and hash blueonyx.conf:
        &ini_read;
        
        # Verify input and set defaults if needed:
        &verify;

	# Populate all output variables:
	&populate_switches;
        
        # Shove ouput into CCE:
        &feedthemonster;
}
else {
	# Ok, we have a problem: No blueonyx.conf found.
	# So we just weep silently and exit. 
	$cce->bye('FAIL', "$blueonyx_conf not found!");
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse blueonyx.conf:
sub ini_read {
    open (F, $blueonyx_conf) || die "Could not open $blueonyx_conf: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               					# skip blank lines
        next if $line =~ /^\#*$/;               					# skip comment lines
        next if $line =~ /^Rewrite(.*)$/;    						# skip line starting with Rewrite
        next if $line =~ /^<Files(.*)$/;    						# skip line starting with <Files
        next if $line =~ /^deny(.*)$/;    						# skip line starting with deny
        next if $line =~ /^<\/Files>(.*)$/;    						# skip line starting with </Files>
        next if $line =~ /^order(.*)$/;    						# skip line starting with order
        next if $line =~ /^allow(.*)$/;    						# skip line starting with allow
        next if $line =~ /^UserDir(.*)$/;    						# skip line starting with UserDir
        next if $line =~ /^DirectoryIndex(.*)$/;    					# skip line starting with DirectoryIndex
        next if $line =~ /^Alias(.*)$/;    						# skip line starting with Alias
        next if $line =~ /^ErrorDocument(.*)$/;    					# skip line starting with ErrorDocument

        next if $line =~ /^<Directory \/home\/.sites\/*\/*\/>(.*)$/;    		# skip line starting with <Directory /home/.sites/*/*/>
        next if $line =~ /^Options \-FollowSymLinks \+SymLinksIfOwnerMatch(.*)$/;    	# skip line starting with Options -FollowSymLinks +SymLinksIfOwnerMatch
        next if $line =~ /^Options \+MultiViews(.*)$/;    				# skip line starting with Options +MultiViews
        next if $line =~ /^<\/Directory>(.*)$/;    					# skip line starting with </Directory>

        if ($line =~ /^([A-Za-z_\.]\w*)/) {		
	    $line =~s/#(.*)$//g; 			# Remove trailing comments in lines
	    $line =~s/\"//g; 				# Remove double quotation marks

            @row = split (/\s/, $line);			# Split row at the equal sign
	    $parsed_key = shift(@row);			# Remove first entry in @row and use it as key in $CONFIG
	    $CONFIG{$parsed_key} = join("&", @row);	# Join the remaining values with the ampersand as delimiter
        }
    }
    close(F);

    # At this point we have all switches from blueonyx.conf cleanly in a hash, split in key / value pairs.
    # To read how "Options" is set we query $CONFIG{'Options'} for example. 

    # For debugging only:
    if ($DEBUG > "1") {
	while (my($k,$v) = each %CONFIG) {
    	    print "$k => $v\n";
	}
    }
}

sub verify {

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed) {
	if (!$CONFIG{"$entry"}) {
	    # Found key without value and none should be empty! Resetting to defaults then:
	    if ($entry eq "Options") {
		$CONFIG{"$entry"} = "Indexes&FollowSymLinks&Includes&MultiViews";
	    }
	    if ($entry eq "AllowOverride") {
		$CONFIG{"$entry"} = "AuthConfig&Indexes&Limit";
	    }
	}
	# For debugging only:
        if ($DEBUG == "1") {
	    print $entry . " = " . $CONFIG{"$entry"} . "\n";
	}
    }
}

sub feedthemonster {

    @oids = $cce->find('System');
    if ($#oids < 0) {
  	# we have major problems if the System object doesn't exist.
  	print STDERR "No System object in CCE!\n";
  	exit 0;
    }
    else {
        # Object already present in CCE. Updating it, NOT forcing a rewrite of blueonyx.conf.
        ($ok, $sys) = $cce->get($oids[0], "Web");
        ($ok) = $cce->set($oids[0], 'Web',{
		'Options_All' => $Options_All,
		'Options_FollowSymLinks' => $Options_FollowSymLinks,
		'Options_Includes' => $Options_Includes,
		'Options_Indexes' => $Options_Indexes,
		'Options_MultiViews' => $Options_MultiViews,
		'Options_SymLinksIfOwnerMatch' => $Options_SymLinksIfOwnerMatch,

		'AllowOverride_All' => $AllowOverride_All,
		'AllowOverride_AuthConfig' => $AllowOverride_AuthConfig,
		'AllowOverride_FileInfo' => $AllowOverride_FileInfo,
		'AllowOverride_Indexes' => $AllowOverride_Indexes,
		'AllowOverride_Limit' => $AllowOverride_Limit,
		'AllowOverride_Options' => $AllowOverride_Options
        });
    }
}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = ( 
	'Options', 
	'AllowOverride'
	);
}

sub populate_switches {

	# $CONFIG{"Options"}
	# $CONFIG{"AllowOverride"}

	# Split our Options down:
	@Options = split(/&/, $CONFIG{"Options"});

	$Options_All = "0";
	$Options_FollowSymLinks = "0";
	$Options_Includes = "0";
	$Options_Indexes = "0";
	$Options_MultiViews = "0";
	$Options_SymLinksIfOwnerMatch = "0";

	foreach $value (@Options) {
		if ($value eq "All") {
			$Options_All = "1";
		}
		if ($value eq "FollowSymLinks") {
			$Options_FollowSymLinks = "1";
		}
		if ($value eq "Includes") {
			$Options_Includes = "1";
		}
		if ($value eq "Indexes") {
			$Options_Indexes = "1";
		}
		if ($value eq "MultiViews") {
			$Options_MultiViews = "1";
		}
		if ($value eq "SymLinksIfOwnerMatch") {
			$Options_SymLinksIfOwnerMatch = "1";
		}
	}

	# Split our AllowOverride down:
	@AllowOverride = split(/&/, $CONFIG{"AllowOverride"});

	$AllowOverride_All = "0";
	$AllowOverride_AuthConfig = "0";
	$AllowOverride_FileInfo = "0";
	$AllowOverride_Indexes = "0";
	$AllowOverride_Limit = "0";
	$AllowOverride_Options = "0";

	foreach $value (@AllowOverride) {
		if ($value eq "All") {
			$AllowOverride_All = "1";
		}
		if ($value eq "AuthConfig") {
			$AllowOverride_AuthConfig = "1";
		}
		if ($value eq "FileInfo") {
			$AllowOverride_FileInfo = "1";
		}
		if ($value eq "Indexes") {
			$AllowOverride_Indexes = "1";
		}
		if ($value eq "Limit") {
			$AllowOverride_Limit = "1";
		}
		if ($value eq "Options") {
			$AllowOverride_Options = "1";
		}
	}
}

$cce->bye('SUCCESS');
exit(0);

# Default lines from blueonyx.conf:
#
# Options Indexes FollowSymLinks Includes MultiViews
# AllowOverride AuthConfig Indexes Limit
#
# How the code parses them:
#
# Options => Indexes&FollowSymLinks&Includes&MultiViews
# AllowOverride => AuthConfig&Indexes&Limit

# Options_All
# Options_FollowSymLinks
# Options_Includes
# Options_Indexes
# Options_MultiViews
# Options_SymLinksIfOwnerMatch

# AllowOverride_All
# AllowOverride_AuthConfig
# AllowOverride_FileInfo
# AllowOverride_Indexes
# AllowOverride_Limit
# AllowOverride_Options

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#	 notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#	 notice, this list of conditions and the following disclaimer in 
#	 the documentation and/or other materials provided with the 
#	 distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#	 contributors may be used to endorse or promote products derived 
#	 from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 