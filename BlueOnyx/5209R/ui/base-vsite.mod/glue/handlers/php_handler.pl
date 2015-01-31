#!/usr/bin/perl -I/usr/sausalito/perl
# $Id: php_handler.pl
#
# This handler is run whenever a CODB Object called "PHP" is created, destroyed or 
# modified. 
#
# If the "PHP" Object with "applicable" => "server" is created or modified, it 
# updates php.ini with those changes and Apache is restarted. Additionally it
# handles updates to the PHP-FPM master-pool config files.

# Debugging switch:
$DEBUG = "0";
if ($DEBUG)
{
        use Sys::Syslog qw( :DEFAULT setlogsock);
}

# Uncomment correct type:
#$whatami = "constructor";
$whatami = "handler";

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
use Sauce::Service;
use Sauce::Util;
use Sauce::Config;
use FileHandle;
use File::Copy;

my $cce = new CCE;
my $conf = '/var/lib/cobalt';

if ($whatami eq "handler") {
    $cce->connectfd();

    # Get our events from the event handler stack:
    $oid = $cce->event_oid();
    $obj = $cce->event_object();

    $old = $cce->event_old();
    $new = $cce->event_new();

    # Get Object PHP from CODB to find out which PHP version we use:
    @sysoids = $cce->find('PHP');
    ($ok, $mySystem) = $cce->get($sysoids[0]);
    $platform = $mySystem->{'PHP_version'};
    if ($platform >= "5.3") {
        # More modern PHP found:
        $legacy_php = "0";
    }
    else {
        # Older PHP found:
        $legacy_php = "1";
    }

    # Get Object PHP for php.ini:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);
    $PHP_server_OID = $oids[0];

    # Get system Timezone out of CODB:
    @system_oid = $cce->find('System');
    ($ok, $tzdata) = $cce->get($system_oid[0], "Time");
    $timezone = $tzdata->{'timeZone'};

    # Check for presence of third party config file:
    &thirdparty_check;

    # We're creating or modifying the main server PHP object:
    if ((($cce->event_is_create()) || ($cce->event_is_modify())) && ($PHP_server_OID eq $oid)) {

        # If someone used the "expert mode", move the temporary php.ini to 
        # the right place, chown it and restart Apache:
        # Function disabled for now!
        $edisabled = "1";
        if ((-f "/tmp/php.ini") && ($edisabled ne "1")) {
            system("/bin/chown root:root /tmp/php.ini");
            system("/bin/cp /tmp/php.ini $php_ini");
            system("/bin/rm -f /tmp/php.ini");
            &restart_apache;
        }
        else {
            # Someone used the GUI to edit some php.ini parameters. Update
            # the existing php.ini and restart Apache:
            if (-f $php_ini) {
                # Edit php.ini:
                &edit_php_ini;

                # Update the PHP-FPM master-pool:
                &handle_fpm_master_pool;

                # Update PHP settings for Vsites:
                &update_vsites;

                # Restart Apache:
                &restart_apache;
            }
            else {
                # Ok, we have a problem: No php.ini found.
                # So we just weep silently and exit. 
                $cce->bye('FAIL', "$php_ini not found!");
                exit(1);
            }
        }
    }
}
else {
    $cce->connectuds();

    # Check for presence of third party config file:
    &thirdparty_check;

    # Get Object PHP from CODB to find out which PHP version we use:
    @sysoids = $cce->find('PHP');
    ($ok, $mySystem) = $cce->get($sysoids[0]);
    $platform = $mySystem->{'PHP_version'};
    if ($platform >= "5.3") {
        # More modern PHP found:
        $legacy_php = "0";
    }
    else {
        # Older PHP found:
        $legacy_php = "1";
    }

    # Get Object PHP for php.ini:
    @oids = $cce->find('PHP', { 'applicable' => 'server' });
    ($ok, $server_php_settings) = $cce->get($oids[0]);
    $PHP_server_OID = $oids[0];

    if (-f $php_ini) {
        &edit_php_ini;
        &handle_fpm_master_pool;
        &update_vsites;
        &restart_apache;
    }
    else {
        # Ok, we have a problem: No php.ini found.
        # So we just weep silently and exit. 
        $cce->bye('FAIL', "$php_ini not found!");
        exit(1);
    }
}

$cce->bye('SUCCESS');
exit(0);

# Read and parse php.ini:
sub ini_read {
    open (F, $php_ini) || die "Could not open $php_ini: $!";

    while ($line = <F>) {
        chomp($line);
        next if $line =~ /^\s*$/;                   # skip blank lines
        next if $line =~ /^\;*$/;                   # skip comment lines
        next if $line =~ /^url_rewriter(.*)$/;      # skip line starting with url_rewriter.tags
        if ($line =~ /^([A-Za-z_\.]\w*)/) {     
        $line =~s/\s//g;                # Remove spaces
        $line =~s/;(.*)$//g;            # Remove trailing comments in lines
        $line =~s/\"//g;                # Remove double quotation marks

            @row = split (/=/, $line);          # Split row at the equal sign
            $CONFIG{$row[0]} = $row[1];         # Hash the splitted row elements
        }
    }
    close(F);

    # At this point we have all switches from php.ini cleanly in a hash, split in key / value pairs.
    # To read how "safe_mode" is set we query $CONFIG{'safe_mode'} for example. 

}

sub thirdparty_check {
    # Check for presence of third party config file:
    if (-f $thirdparty) {
    open (F, $thirdparty) || die "Could not open $thirdparty: $!";
    while ($line = <F>) {
            chomp($line);
            next if $line =~ /^\s*$/;                   # skip blank lines
            next if $line =~ /^#$/;                 # skip comments
            if ($line =~ /^\/(.*)\/php\.ini$/) {
        $php_ini = $line;
        }
    }
    close(F);
    }
}

sub restart_apache {
    # Restarts Apache - hard restart:
    if (-f "/usr/bin/systemctl") {
        # Got Systemd:
        system("systemctl restart httpd.service --no-block");
    }
    else {
        # Thank God, no Systemd:
        system("/sbin/service httpd restart");
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
        'memory_limit',
        'mail.add_x_header',
        'sendmail_path',
        'auto_prepend_file',
        'date.timezone'
        );
}

sub edit_php_ini {

    # Making sure 'open_basedir' has the bare minimum defaults:
    @php_settings_temporary = split(":", $server_php_settings->{"open_basedir"});
    @my_baremetal_minimums = ('/usr/sausalito/configs/php/', '/tmp/', '/var/lib/php/session/');
    @php_settings_temp_joined = (@php_settings_temporary, @my_baremetal_minimums);

    # Remove duplicates:
    foreach my $var ( @php_settings_temp_joined ){
        if ( ! grep( /$var/, @open_basedir ) ){
            push(@open_basedir, $var );
        }
    }
    $server_php_settings->{"open_basedir"} = join(":", @open_basedir);

    # Making sure 'safe_mode_include_dir' has the bare minimum defaults:
    @smi_temporary = split(":", $server_php_settings->{"safe_mode_include_dir"});
    @smi_baremetal_minimums = ('/usr/sausalito/configs/php/');
    @smi_temp_joined = (@smi_temporary, @smi_baremetal_minimums);
        
    # Remove duplicates:
    foreach my $var ( @smi_temp_joined ){
        if ( ! grep( /$var/, @safe_mode_include_dir ) ){
            push(@safe_mode_include_dir, $var );
        }
    }
    $server_php_settings->{"safe_mode_include_dir"} = join(":", @safe_mode_include_dir);

    # Just to be really sure:
    unless (($server_php_settings->{"open_basedir"} =~ m#/usr/sausalito/configs/php/#) && ($server_php_settings->{"open_basedir"} =~ m#/tmp/#) && ($server_php_settings->{"open_basedir"} =~ m#/var/lib/php/session/#)) {
    &debug_msg("Fixing 'open_basedir': It is missing our 'must have' entries. Restoring it to the defaults. \n");
        $server_php_settings->{"open_basedir"} = "/tmp/:/var/lib/php/session/:/usr/sausalito/configs/php/";
    }

    # Making sure 'safe_mode_allowed_env_vars' has the bare minimum defaults:
    @smaev_temporary = split(",", $server_php_settings->{"safe_mode_allowed_env_vars"});
    @smi_baremetal_minimums = ('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
    @smaev_temp_joined = (@smaev_temporary, @smi_baremetal_minimums);
        
    # Remove duplicates:
    foreach my $var ( @smaev_temp_joined ){
        if ( ! grep( /$var/, @safe_mode_allowed_env_vars ) ){
            push(@safe_mode_allowed_env_vars, $var );
        }
    }
    $server_php_settings->{"safe_mode_allowed_env_vars"} = join(",", @safe_mode_allowed_env_vars);

    if ($platform >= "5.4") {
        $server_php_settings->{"register_globals"} = "Off";
    }

    if ($legacy_php == "0") {
    # Build output hash for PHP-5.3 or newer:
    $server_php_settings_writeoff = { 
        'register_globals' => $server_php_settings->{"register_globals"}, 
        'allow_url_fopen' => $server_php_settings->{"allow_url_fopen"}, 
        'allow_url_include' => $server_php_settings->{"allow_url_include"}, 
        'disable_classes' => $server_php_settings->{"disable_classes"}, 
        'disable_functions' => $server_php_settings->{"disable_functions"}, 
        'open_basedir' => $server_php_settings->{"open_basedir"}, 
        'post_max_size' => $server_php_settings->{"post_max_size"}, 
        'upload_max_filesize' => $server_php_settings->{"upload_max_filesize"},
        'max_execution_time' => $server_php_settings->{"max_execution_time"}, 
        'max_input_time' => $server_php_settings->{"max_input_time"}, 
        'memory_limit' => $server_php_settings->{"memory_limit"}, 
        'mail.add_x_header' => 'On',
        'sendmail_path' => '/usr/sausalito/sbin/phpsendmail',
        'auto_prepend_file' => '/usr/sausalito/configs/php/set_php_headers.php',
        'date.timezone' => "'" . $timezone . "'"
    };
    }
    else {
    # Build output hash for and older PHP:
    $server_php_settings_writeoff = { 
        'safe_mode' => $server_php_settings->{"safe_mode"}, 
        'safe_mode_allowed_env_vars' => $server_php_settings->{"safe_mode_allowed_env_vars"}, 
        'safe_mode_exec_dir' => $server_php_settings->{"safe_mode_exec_dir"}, 
        'safe_mode_gid' => $server_php_settings->{"safe_mode_gid"}, 
        'safe_mode_include_dir' => $server_php_settings->{"safe_mode_include_dir"}, 
        'safe_mode_protected_env_vars' => $server_php_settings->{"safe_mode_protected_env_vars"},   
        'register_globals' => $server_php_settings->{"register_globals"}, 
        'allow_url_fopen' => $server_php_settings->{"allow_url_fopen"}, 
        'allow_url_include' => $server_php_settings->{"allow_url_include"}, 
        'disable_classes' => $server_php_settings->{"disable_classes"}, 
        'disable_functions' => $server_php_settings->{"disable_functions"}, 
        'open_basedir' => $server_php_settings->{"open_basedir"}, 
        'post_max_size' => $server_php_settings->{"post_max_size"}, 
        'upload_max_filesize' => $server_php_settings->{"upload_max_filesize"},
        'max_execution_time' => $server_php_settings->{"max_execution_time"}, 
        'max_input_time' => $server_php_settings->{"max_input_time"}, 
        'memory_limit' => $server_php_settings->{"memory_limit"},
        'mail.add_x_header' => 'On',
        'sendmail_path' => '/usr/sausalito/sbin/phpsendmail',
        'auto_prepend_file' => '/usr/sausalito/configs/php/set_php_headers.php',
        'date.timezone' => "'" . $timezone . "'"
    };
    }

    # Write changes to php.ini using Sauce::Util::hash_edit_function. The really GREAT thing
    # about this function is that it replaces existing values and appends those new ones that 
    # are missing in the output file. And it does it for ALL values in our hash in one go.

    $ok = Sauce::Util::editfile(
        $php_ini,
        *Sauce::Util::hash_edit_function,
        ';',
        { 're' => '=', 'val' => ' = ' },
        $server_php_settings_writeoff);

    # Error handling:
    unless ($ok) {
        $cce->bye('FAIL', "Error while editing $php_ini!");
        exit(1);
    }

    # Now this is a bit dirty: 
    # On Systems with newer PHP than 5.3 we need to comment out 'Safe_Mode'
    # or else we get error messages:

    if ($legacy_php == "0") {

        my $confdir = '/etc';
        umask(0077);
        my $stage = "$confdir/php.ini~";
        open(HTTPD, "$confdir/php.ini");
        unlink($stage);
        sysopen(STAGE, $stage, 1|O_CREAT|O_EXCL, 0600) || die;
        while(<HTTPD>) {
          s/^safe_mode\s/;safe_mode /;
          s/^Safe_Mode\s/;safe_mode /;

          print STAGE;
        }
        close(STAGE);
        close(HTTPD);

        chmod(0644, $stage);
        if(-s $stage) {
          move($stage,"$confdir/php.ini");
          chmod(0644, "$confdir/php.ini"); # paranoia
        }
    }
}

sub update_vsites {

    # Find all Vsites:
    my @vhosts = ();
    my (@vhosts) = $cce->findx('Vsite');

    # Walk through all Vsites:
    for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    &debug_msg("Updating PHP settings for Vsite $my_vsite->{fqdn} \n");

    ($ok) = $cce->set($vsite, 'PHPVsite',{
            'force_update' => time()
        });
    }
}

sub handle_fpm_master_pool {
 
    # Get new prefered owner:
    $pool_UID = 'apache';

    # Get group just to be sure:
    $pool_group = 'apache';

    # Pool Port:
    $fpmPort = 9000;

    # Location of the PHP-FPM master-pool file:
    $pool_file = "/etc/php-fpm.d/www.conf";

    &debug_msg("Updating PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");
    
    #
    ### Define Pool config file contends:
    #

    $pool_conf = '; Global PHP-FPM settings for master-pool:' . "\n";
    $pool_conf .= '[global]' . "\n";
    $pool_conf .= 'log_level = notice' . "\n";
    $pool_conf .= 'emergency_restart_threshold = 0' . "\n";
    $pool_conf .= 'emergency_restart_interval = 0' . "\n";
    $pool_conf .= 'process_control_timeout = 0' . "\n";
    $pool_conf .= 'daemonize = yes' . "\n";
    $pool_conf .= '' . "\n";
    $pool_conf .= '; Pool name and settings:' . "\n";
    $pool_conf .= '[www]' . "\n";
    $pool_conf .= 'listen = 127.0.0.1:' . $fpmPort . "\n";
    $pool_conf .= 'listen.allowed_clients = 127.0.0.1' . "\n";
    $pool_conf .= 'listen.owner = ' . $pool_UID . "\n";
    $pool_conf .= 'listen.group = ' . $pool_group . "\n";
    $pool_conf .= 'listen.mode = 0666' . "\n";
    $pool_conf .= '' . "\n";
    $pool_conf .= '; UID/GID:' . "\n";
    $pool_conf .= 'user = ' . $pool_UID . "\n";
    $pool_conf .= 'group = ' . $pool_group . "\n";
    $pool_conf .= '' . "\n";
    $pool_conf .= '; Set to \'ondemand\' and set limits:' . "\n";
    $pool_conf .= 'pm = ondemand' . "\n";
    $pool_conf .= 'pm.max_children = 5' . "\n";
    $pool_conf .= 'pm.process_idle_timeout = 10s' . "\n";
    $pool_conf .= 'pm.max_requests = 500' . "\n";
    $pool_conf .= '' . "\n";
    $pool_conf .= '; Set session path to a directory owned by process user' . "\n";
    $pool_conf .= 'php_value[session.save_handler] = files' . "\n";
    $pool_conf .= 'php_value[session.save_path] = /var/lib/php/session' . "\n";
    $pool_conf .= '' . "\n";
    $pool_conf .= '; Global PHP settings inherited from the main php.ini:' . "\n";

    # Piece the individual PHP Vsite settings together:

    if ($legacy_php == "1") {
        # These options only apply to PHP versions prior to PHP-5.3:
        if ($server_php_settings->{"safe_mode"} ne "") {
            $pool_conf .= 'php_admin_flag[safe_mode] = ' . $server_php_settings->{"safe_mode"} . "\n"; 
        }
        if ($server_php_settings->{"safe_mode_gid"} ne "") {
            $pool_conf .= 'php_admin_flag[safe_mode_gid] =' . $server_php_settings->{"safe_mode_gid"} . "\n";
        }
        if ($server_php_settings->{"safe_mode_allowed_env_vars"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_allowed_env_vars] = ' . $server_php_settings->{"safe_mode_allowed_env_vars"} . "\n"; 
        }
        if ($server_php_settings->{"safe_mode_exec_dir"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_exec_dir] = ' . $server_php_settings->{"safe_mode_exec_dir"} . "\n"; 
        }

        if ($server_php_settings->{"safe_mode_include_dir"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_include_dir] = ' . $server_php_settings->{"safe_mode_include_dir"} . "\n"; 
        }
        if ($server_php_settings->{"safe_mode_protected_env_vars"} ne "") {
            $pool_conf .= 'php_admin_value[safe_mode_protected_env_vars] = ' . $server_php_settings->{"safe_mode_protected_env_vars"} . "\n"; 
        }
    }

    if ($server_php_settings->{"register_globals"} ne "") {
        $pool_conf .= 'php_admin_flag[register_globals] = ' . $server_php_settings->{"register_globals"} . "\n"; 
    }
    if ($server_php_settings->{"allow_url_fopen"} ne "") {
        $pool_conf .= 'php_admin_flag[allow_url_fopen] = ' . $server_php_settings->{"allow_url_fopen"} . "\n"; 
    }
    if ($server_php_settings->{"allow_url_include"} ne "") {
        $pool_conf .= 'php_admin_flag[allow_url_include] = ' . $server_php_settings->{"allow_url_include"} . "\n"; 
    }

    if ($server_php_settings->{"open_basedir"} ne "") {
        $pool_conf .= 'php_admin_value[open_basedir] = ' . $server_php_settings->{"open_basedir"} . "\n";
    }

    if ($server_php_settings->{"post_max_size"} ne "") {
        $pool_conf .= 'php_admin_value[post_max_size] =' . $server_php_settings->{"post_max_size"} . "\n"; 
    }
    if ($server_php_settings->{"upload_max_filesize"} ne "") {
        $pool_conf .= 'php_admin_value[upload_max_filesize] = ' . $server_php_settings->{"upload_max_filesize"} . "\n"; 
    }
    if ($server_php_settings->{"max_execution_time"} ne "") {
        $pool_conf .= 'php_admin_value[max_execution_time] = ' . $server_php_settings->{"max_execution_time"} . "\n"; 
    }
    if ($server_php_settings->{"max_input_time"} ne "") {
        $pool_conf .= 'php_admin_value[max_input_time] = ' . $server_php_settings->{"max_input_time"} . "\n"; 
    }
    if ($server_php_settings->{"memory_limit"} ne "") {
        $pool_conf .= 'php_admin_value[memory_limit] = ' . $server_php_settings->{"memory_limit"} . "\n"; 
    }

    # Email related:
    $pool_conf .= 'php_admin_flag[mail.add_x_header] = On' . "\n";
    $pool_conf .= 'php_admin_value[sendmail_path] = /usr/sausalito/sbin/phpsendmail' . "\n";
    $pool_conf .= 'php_admin_value[auto_prepend_file] = /usr/sausalito/configs/php/set_php_headers.php' . "\n";

    # Edit the Pool config file or die!:
    &debug_msg("Editing PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");

    if(!Sauce::Util::editfile($pool_file, *pool_printer)) {
        &debug_msg("Failed to edit PHP-FPM pool config $pool_file through php_vsite_handler.pl \n");
        $cce->bye('FAIL', '[[base-apache.cantEditVhost]]');
        exit(1);
    }

    # Restart PHP-FPM:
    &debug_msg("Reloading PHP-FPM through php_vsite_handler.pl \n");
    service_run_init('php-fpm', 'reload');

}

sub pool_printer {
    ($in, $out) = @_;
    print $out $pool_conf;
    return 1;
}

sub debug_msg {
    if ($DEBUG) {
        my $msg = shift;
        $user = $ENV{'USER'};
        setlogsock('unix');
        openlog($0,'','user');
        syslog('info', "$ARGV[0]: $msg");
        closelog;
    }
}

$cce->bye('SUCCESS');
exit(0);

# 
# Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#     notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#     notice, this list of conditions and the following disclaimer in 
#     the documentation and/or other materials provided with the 
#     distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#     contributors may be used to endorse or promote products derived 
#     from this software without specific prior written permission.
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