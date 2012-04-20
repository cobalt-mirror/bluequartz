#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: import_php_ini_settings.pl, v1.1.0.4 Tue 16 Jun 2009 09:08:46 AM EDT mstauber Exp $
# Copyright 2006-2009 Solarspeed Ltd. All rights reserved.
# Copyright 2006-2010 Team BlueOnyx. All rights reserved.

# This script parses php.ini and brings CODB up to date on how PHP is configured.
# Can easily be extended to parse third party php.ini's through an optional config file.
#
# If your 3rd party PHP installs into /home/mycompany/php/ and your php.ini is located 
# in /home/mycompany/php/etc/php.ini, then create a /etc/thirdparty_php file and put 
# the complete path to your php.ini into it. By doing so this script will ignore the
# default /etc/php.ini and will use the one you specified in /etc/thirdparty_php

# Debugging switch:
$DEBUG = "0";

# Uncomment correct type:
$whatami = "constructor";
#$whatami = "handler";

# Location of php.ini:
$php_ini = "/etc/php.ini";

#
#### No configureable options below!
#

# Location of config file in which 3rd party vendors can
# specify where their 3rd party PHP's php.ini is located:
#
# IMPORTANT: Do NOT modify the line below, as this script WILL
# be updated through YUM from time to time. Which overwrites 
# any changes you make in here!
$thirdparty = "/etc/thirdparty_php";

use CCE;
use Data::Dumper;
use Sauce::Config;
use FileHandle;
use File::Copy;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();
}
else {
    $cce->connectuds();
}

# Check for presence of third party PHP config file:
if (-f $thirdparty) {
    open (F, $thirdparty) || die "Could not open $thirdparty: $!";
    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               # skip blank lines
        next if $line =~ /^#$/;               	# skip comments
        if ($line =~ /^\/(.*)\/php\.ini$/) {
		$php_ini = $line;
	}
        if ($line =~ /^\/(.*)\/etc\/php\.ini$/) {
		$thirdpartydir = "/" . $1 . "/bin";
	}
    }
    close(F);
}
else {
    # In this case the variable is actually misnamed and we use the path to the onboard PHP:
    $thirdpartydir = "/usr/bin";
}

# Find out which version of PHP we are running and store it in CCE:
$PHP_version = `$thirdpartydir/php -v|/bin/grep ^PHP | /bin/awk '\{print \$2\}'`;
chomp($PHP_version);

# Fix third party php-cgi location in /etc/suphp.conf:
if ((-f "$thirdpartydir/php-cgi") && (-f "/etc/suphp.conf")) {
    umask(0077);
    my $stage = "/etc/suphp.conf~";
    open(HTTPD, "/etc/suphp.conf");
    unlink($stage);
    sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
    while(<HTTPD>) {
	s/^x-httpd-suphp="(.*)"/x-httpd-suphp="php:$thirdpartydir\/php-cgi"/g;
	s/^x-httpd-suphpthirdparty="(.*)"/x-httpd-suphpthirdparty="php:$thirdpartydir\/php-cgi"/g;
	print STAGE;
    }
    close(STAGE);
    close(HTTPD);
    chmod(0644, $stage);
    if(-s $stage) {
	move($stage,"/etc/suphp.conf");
	chmod(0644, "/etc/suphp.conf"); # paranoia
    }
}

# Config file present?
if (-f $php_ini) {

	# Array of PHP config switches that we want to update in CCE:
	&items_of_interest;

	# Read, parse and hash php.ini:
        &ini_read;
        
        # Verify input and set defaults if needed:
        &verify;
        
        # Shove ouput into CCE:
        &feedthemonster;
}
else {
	# Ok, we have a problem: No php.ini found.
	# So we just weep silently and exit. 
	$cce->bye('FAIL', "$php_ini not found!");
	exit(1);
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse php.ini:
sub ini_read {
    open (F, $php_ini) || die "Could not open $php_ini: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;               	# skip blank lines
        next if $line =~ /^\;*$/;               	# skip comment lines
        next if $line =~ /^url_rewriter(.*)$/;    	# skip line starting with url_rewriter.tags
        if ($line =~ /^([A-Za-z_\.]\w*)/) {		
	    $line =~s/\s//g; 				# Remove spaces
	    $line =~s/;(.*)$//g; 			# Remove trailing comments in lines
	    $line =~s/\"//g; 				# Remove double quotation marks

            @row = split (/=/, $line);			# Split row at the equal sign
    	    $CONFIG{$row[0]} = $row[1];			# Hash the splitted row elements
        }
    }
    close(F);

    # At this point we have all switches from php.ini cleanly in a hash, split in key / value pairs.
    # To read how "safe_mode" is set we query $CONFIG{'safe_mode'} for example. 

    # For debugging only:
    if ($DEBUG > "1") {
	while (my($k,$v) = each %CONFIG) {
    	    print "$k => $v\n";
	}
    }

    # For debugging only:
    if ($DEBUG == "1") {
	print "safe mode: " . $CONFIG{'safe_mode'} . "\n";
    }

}

sub verify {

    # Find out if we have ever run before:
    @oids = $cce->find('PHP', {'applicable' => 'server'});
    if ($#oids < 0) {
	$first_run = "1";
    }
    else {
	$first_run = "0";    
    }

    # Go through list of config switches we're interested in:
    foreach $entry (@whatweneed) {
	if (!$CONFIG{"$entry"}) {
	    # Found key without value - setting defaults for those that need it:
	    if ($entry eq "allow_url_include") {
		$CONFIG{"$entry"} = "Off";
	    }
	    if (($entry eq "disable_functions") && ($first_run eq "1")) {
		#$CONFIG{"$entry"} = "exec,system,passthru,shell_exec,popen,escapeshellcmd,proc_open,proc_nice,ini_restore";
		$CONFIG{"$entry"} = "exec,system,passthru,shell_exec,proc_open,proc_nice,ini_restore";
	    }
	    if (($entry eq "open_basedir") && ($first_run eq "1")) {
		$CONFIG{"$entry"} = "/tmp/:/var/lib/php/session/:/usr/sausalito/configs/php/";
	    }
	}
	if ($first_run eq "1") {
	    # If we're indeed running for the first time, make sure safe defaults
	    # are set for all our remaining switches of most importance:
	    $CONFIG{"safe_mode"} = "On";
	    $CONFIG{"register_globals"} = "Off";
	    $CONFIG{"allow_url_fopen"} = "Off";
	
	}
	# For debugging only:
        if ($DEBUG == "1") {
	    print $entry . " = " . $CONFIG{"$entry"} . "\n";
	}
    }

    # If we have base-squirrelmail.mod installed, we make sure that 'popen' and 'escapeshellcmd' are not present in 'disable_functions':
    if (-f "/etc/httpd/conf.d/squirrelmail.conf") {
	@old_disable_functions = split(/,/, $CONFIG{"disable_functions"});
	foreach $value (@old_disable_functions) {
	    # Transform to lower case:
	    $value =~ tr/A-Z/a-z/;
	    # Weed out undersired options:
	    unless (($value eq "popen") || ($value eq "escapeshellcmd") || ($value eq "")) {
		# Push the rest to new array:
    		push(@new_disable_functions, $value);
	    }
	}
	# Turn the cleaned array back to a string:
	$CONFIG{"disable_functions"} = join(",", @new_disable_functions);
    }
}

sub feedthemonster {

    # Making sure 'open_basedir' has the bare minimum defaults:
    @php_settings_temporary = split(":", $CONFIG{"open_basedir"});
    @my_baremetal_minimums = ('/usr/sausalito/configs/php/', '/tmp/', '/var/lib/php/session/');
    @php_settings_temp_joined = (@php_settings_temporary, @my_baremetal_minimums);
    
    # Remove duplicates:
    foreach my $var ( @php_settings_temp_joined ){
        if ( ! grep( /$var/, @open_basedir ) ){   
            push(@open_basedir, $var );
        }
    }
    $CONFIG{"open_basedir"} = join(":", @open_basedir);
    
    # Just to be really sure:
    unless (($CONFIG{"open_basedir"} =~ m#/usr/sausalito/configs/php/#) && ($CONFIG{"open_basedir"} =~ m#/tmp/#) && ($CONFIG{"open_basedir"} =~ m#/var/lib/php/session/#)) {
        &debug_msg("Fixing 'open_basedir': It is missing our 'must have' entries. Restoring it to the defaults. \n");
        $CONFIG{"open_basedir"} = "/tmp/:/var/lib/php/session/:/usr/sausalito/configs/php/";
    }

    @oids = $cce->find('PHP', {'applicable' => 'server'});
    if ($#oids < 0) {
        # Object not yet in CCE. Creating new one and forcing re-write of php.ini by setting "force_update":
	($ok) = $cce->create('PHP', {
	    'applicable' => 'server',
	    'PHP_version' => $PHP_version,
	    'safe_mode' => $CONFIG{"safe_mode"},  
	    'safe_mode_allowed_env_vars' => $CONFIG{"safe_mode_allowed_env_vars"},   
	    'safe_mode_exec_dir' => $CONFIG{"safe_mode_exec_dir"},   
	    'safe_mode_gid' => $CONFIG{"safe_mode_gid"},   
	    'safe_mode_include_dir' => $CONFIG{"safe_mode_include_dir"},  
	    'safe_mode_protected_env_vars' => $CONFIG{"safe_mode_protected_env_vars"},  
    	    'register_globals' => $CONFIG{"register_globals"},  
	    'allow_url_fopen' => $CONFIG{"allow_url_fopen"},   
	    'allow_url_include' => $CONFIG{"allow_url_include"},  
	    'disable_classes' => $CONFIG{"disable_classes"},   
	    'disable_functions' => $CONFIG{"disable_functions"},  
	    'open_basedir' => $CONFIG{"open_basedir"},   
	    'post_max_size' => $CONFIG{"post_max_size"},   
	    'upload_max_filesize'  => $CONFIG{"upload_max_filesize"},  
	    'max_execution_time' => $CONFIG{"max_execution_time"},   
	    'max_input_time' => $CONFIG{"max_input_time"},   
	    'memory_limit' => $CONFIG{"memory_limit"},   
	    'php_ini_location' => $php_ini,  
	    'force_update' => time()  
        });
    }
    else {
        # Object already present in CCE. Updating it, NOT forcing a rewrite of php.ini.
        ($sys_oid) = $cce->find('PHP', {'applicable' => 'server'});
        ($ok, $sys) = $cce->get($sys_oid);
        ($ok) = $cce->set($sys_oid, '',{
	    'PHP_version' => $PHP_version,  
	    'safe_mode' => $CONFIG{"safe_mode"},  
	    'safe_mode_allowed_env_vars' => $CONFIG{"safe_mode_allowed_env_vars"},   
	    'safe_mode_exec_dir' => $CONFIG{"safe_mode_exec_dir"},   
	    'safe_mode_gid' => $CONFIG{"safe_mode_gid"},   
	    'safe_mode_include_dir' => $CONFIG{"safe_mode_include_dir"},  
	    'safe_mode_protected_env_vars' => $CONFIG{"safe_mode_protected_env_vars"},  
    	    'register_globals' => $CONFIG{"register_globals"},  
	    'allow_url_fopen' => $CONFIG{"allow_url_fopen"},   
	    'allow_url_include' => $CONFIG{"allow_url_include"},  
	    'disable_classes' => $CONFIG{"disable_classes"},   
	    'disable_functions' => $CONFIG{"disable_functions"},  
	    'open_basedir' => $CONFIG{"open_basedir"},   
	    'post_max_size' => $CONFIG{"post_max_size"},   
	    'upload_max_filesize' => $CONFIG{"upload_max_filesize"},  
	    'max_execution_time' => $CONFIG{"max_execution_time"},   
	    'max_input_time' => $CONFIG{"max_input_time"},   
	    'memory_limit' => $CONFIG{"memory_limit"},   
	    'php_ini_location' => $php_ini  
        });
    }
}

sub items_of_interest {
    # List of config switches that we're interested in:
    @whatweneed = ( 
	'safe_mode', 
	'safe_mode_allowed_env_vars', 
	'safe_mode_exec_dir', 
	'safe_mode_gid', 
	'safe_mode_include_dir', 
	'safe_mode_protected_env_vars',	
	'register_globals', 
	'allow_url_fopen', 
	'allow_url_include', 
	'disable_classes', 
	'disable_functions', 
	'open_basedir', 
	'post_max_size', 
	'upload_max_filesize',
	'max_execution_time',
	'max_input_time',
	'memory_limit'
	);
}

$cce->bye('SUCCESS');
exit(0);

