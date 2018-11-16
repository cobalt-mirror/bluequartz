#!/usr/bin/perl -I/usr/sausalito/perl

# Debugging switch:
$DEBUG = "1";
if ($DEBUG) {
    use Sys::Syslog qw( :DEFAULT setlogsock);
}

use AM::Util;
use Sauce::Service;
use CCE;
use Data::Dumper;
use Sauce::Util;
use Sauce::Config;
use Base::Httpd qw(httpd_set_server_aliases);
use Sauce::Service qw(service_run_init);
use List::MoreUtils qw/ uniq /;

# Email related:
use Sys::Hostname;
use POSIX qw(isalpha);
use MIME::Lite;
use Encode::Encoder;
use Encode qw(from_to);
my $host = hostname();
my $now = localtime time;

my $cce = new CCE;
$cce->connectuds();

my %am_states = am_get_statecodes();
my $vhost_basepath = '/etc/httpd/conf/vhosts/';

my $failure = '0';

&debug_msg("Starting: am_alias.pl \n");

# Find all Vsites:
my @vhosts = ();
my (@vhosts) = $cce->findx('Vsite');

# Walk through all Vsites:
for my $vsite (@vhosts) {
    ($ok, my $my_vsite) = $cce->get($vsite);

    #&debug_msg("am_alias.pl: Processing Vsite " . $my_vsite->{fqdn} .  "\n");

    # Check if Vsite has 'webAliases' and check if siteX config has it, too:
    if ($my_vsite->{webAliases}) {
        my @webAliases = $cce->scalar_to_array($my_vsite->{webAliases});
        my $vhostFile = $vhost_basepath . $my_vsite->{name};
        my $vhost_conf = `/usr/bin/cat $vhostFile | /usr/bin/grep ^ServerAlias| /usr/bin/head -1`;
        $vhost_conf =~ s/^ServerAlias\s+//;
        chomp($vhost_conf);
        my @siteAliases = split / /, $vhost_conf;

        # Sort both Arrays:
        my @sorted_webAliases = sort @webAliases;
        my @sorted_siteAliases = sort @siteAliases;
        uniq(@sorted_webAliases);
        uniq(@sorted_siteAliases);

        # Compare both Arrays:
        if (@sorted_webAliases != @sorted_siteAliases) {

            #
            ### Compare failed! We need to fix the Aliases for this Vsite!
            #

            if ($failure eq '0') {
                # Grab last 30 minutes of events from /var/log/messages:
                system("rm -f /tmp/messages.30");
                `/usr/sausalito/bin/tail30.pl /var/log/messages > /tmp/messages.30`;

                # Email Body:
                $body_head = "Active Monitor has detected a Webserver Alias problem related to the following Vsites:\n\n";
                $body = "";

                &debug_msg("am_alias.pl: Constructing Email Body.\n");
            }

            $failure++;

            &debug_msg("am_alias.pl: WARNING! " . $my_vsite->{fqdn} .  " has an alias problem!\n");
            &debug_msg("am_alias.pl: CODB: " . join(' ', @sorted_webAliases) .  "\n");
            &debug_msg("am_alias.pl: CFG : " . join(' ', @sorted_siteAliases) .  "\n");

            # Extend Email Body:
            &debug_msg("am_alias.pl: Extending Email Body.\n");
            $body .= "   * " . $my_vsite->{fqdn} . " - Webserver Aliases: " . join(' ', @sorted_webAliases) . "\n";

            # map into a hash and then take the keys to avoid duplicates
            &debug_msg("am_alias.pl: Fixing ServerAlias line in $vhostFile\n");
            my %web_aliases = map { $_ => 1 } $cce->scalar_to_array($my_vsite->{webAliases});
            my @web_aliases = keys %web_aliases;
            if (!httpd_set_server_aliases(\@web_aliases, $my_vsite->{name})) {
                &debug_msg("am_alias.pl: Edit of ServerAlias line in $vhostFile failed!\n");
            }
            $baggage = $vhost_basepath . 'site*.backup.*';
            system("/usr/bin/rm -f $baggage");
        }
    }
}

if ($failure gt '0') {

    # Parse /etc/swatch.conf:
    if (-f '/etc/swatch.conf') {
        &debug_msg("am_alias.pl: Parsing /etc/swatch.conf.\n");
        open(CONF, "< /etc/swatch.conf");
        @email_list;
        my $lang = "en";
        my $enabled = "true";
        while (<CONF>) {
            chomp;
            my($key, $val) = split /\s*=\s*/, $_, 2;
            if ($key eq "email_list") {
                @email_list = split /\s*,\s*/, $val;
            } 
            elsif ($key eq "lang") {
                $lang = $val;
            } 
            elsif ($key eq "enabled") {
                $enabled = $val;
            }
        }
    }

    #
    ### Send Email:
    #

    &debug_msg("am_alias.pl: Final Email prep stage.\n");

    $body = $body_head . $body;

    # Need to convert to UTF-8. Ain'that funny. The source *IS* UTF-8:
    from_to($body, "windows-1252", "utf-8");

    my $subject = $host . ": " . Encode::encode("MIME-B", "Active Monitor: Webserver Alias changes");
    my $to;

    foreach $to (@email_list) {

        # Build the message using MIME::Lite:
        $MIME::Lite::AUTO_CONTENT_TYPE = 1;

        #-- create the multipart container
        my $msg = MIME::Lite->new (
           From     => "root",
           To       => $to,
           Subject  => $subject,
           Type     =>'multipart/mixed'
        );

        #-- add the text message part
        $msg->attach (
           Type => 'TEXT',
           Data => $body
        );

        #-- add the logfile
        $msg->attach (
           Type        => 'AUTO',
           Path        => "/tmp/messages.30",
           Filename    => 'messages.txt',
           Disposition => 'attachment'
           );

        # Out with the email:
        $msg->send;
        &debug_msg("am_alias.pl: Sending AM Email!\n");
    }

    # Reload Apache:
    service_run_init('httpd', 'reload');
    system("rm -f /tmp/messages.30");

    &debug_msg("am_alias.pl: Found and fixed $failure Webserver Alias problems!\n");

    # Pass out AM-State:
    print $ENV{redMsg};
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_YELLOW};
}
else {
    &debug_msg("am_alias.pl: No Webserver Alias problems found.\n");
    &debug_msg("Ending: am_alias.pl \n");

    # Pass out AM-State:
    print $ENV{greenMsg};
    $cce->bye('SUCCESS');
    exit $am_states{AM_STATE_GREEN};
}

# tell cce everything is okay
$cce->bye('SUCCESS');
exit(0);

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

# 
# Copyright (c) 2017 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2017 Team BlueOnyx, BLUEONYX.IT
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