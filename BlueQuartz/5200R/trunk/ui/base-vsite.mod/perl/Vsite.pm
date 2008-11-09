# Copyright Sun Microsystems, Inc. 2001
# $Id: Vsite.pm,v 1.8 2001/11/21 20:23:19 pbaltz Exp $
# Base::Vsite
# all Vsite specific configuration variables and methods should go here.

package Base::Vsite;

=pod
=head1 NAME

Base::Vsite - provides basic methods and constants for Vsites

=head1 SYNOPSIS

 use Base::Vsite;
 use Base::Vsite qw(vsite_method);

 Base::Vsite::vsite_update_site_admin_caps($cce, $vsite, 'siteService', 0);

=head1 DESCRIPTION

Base::Vsite is a collection of Vsite specific configuration variables and
methods. 

=head1 EXPORTS

All of the methods provided by Base::Vsite can be imported into the calling
namespace with the standard C<use Module qw(function1 function2 ...);> pragma.
Only methods are available for importing.  All configuration variables must be
used as C<$Base::Vsite::variable> wherever they are used.

=cut

use vars qw(@ISA @EXPORT_OK);

require Exporter;
@ISA = qw(Exporter);
@EXPORT_OK = qw(
                vsite_update_site_admin_caps
	        );

use lib qw(/usr/sausalito/perl);
use vars qw($SITE_ADMIN_GROUP $SERVER_ADMIN_GROUP);
$SITE_ADMIN_GROUP = 'site-adm';
$SERVER_ADMIN_GROUP = 'admin-users';

=pod

=head1 CONSTANTS

=over 4

=item $Base::Vsite::SITE_ADMIN_GROUP

This is the system group in which all site administrators for all sites are 
members.

=item $Base::Vsite::SERVER_ADMIN_GROUP

This is the system group in which all server administrators are members.  The
admin user is not a member of this group.

=back

=head1 METHODS

=over 4

=item vsite_update_site_admin_caps($cce, $vsite, $capability, $add_remove)

This will update the capLevels field for all the site admins for the given
vsite, so that the passed in capability is added or removed.  This is most
useful for having seperate capabilities tied to menu items in the UI, so the
menu can be shown or hidden based on whether a site is allowed to use a certain
feature.  The method returns true for success and false for failure.

=over 4

=item *

I<$cce> is a CCE object.  See /usr/sausalito/perl/CCE.pm.

=item *

I<$vsite> is the Vsite information returned by CCE::get.

=item *

I<$capability> is a string containing the name of the capability that should be
given to all site admins for the passed in Vsite.

=item *

I<$add_remove> is a boolean value.  If true, the given capability will be
added to the site admin capabilities.  If false, the given capability will
be removed.

=back

=back

=cut

sub vsite_update_site_admin_caps
{
    my ($cce, $vsite, $cap, $add_remove) = @_;

    # make sure we at least have $cce
    if (not defined($cce))
    {
        return 0;
    }
    elsif ($cce->event_is_destroy())
    {
        return 1;
    }
    
    # update siteAdminCaps field for this Vsite
    my $ok = 0;
    my @current = $cce->scalar_to_array($vsite->{siteAdminCaps});
    my $found = grep(/^$cap$/, @current);
    if ($add_remove && !$found)
    {
        push @current, $cap;
    }
    elsif (!$add_remove && $found)
    {
        @current = grep(!/^$cap$/, @current);
    }

    ($ok) = $cce->set($cce->event_oid(), '',
                { 'siteAdminCaps' => $cce->array_to_scalar(@current)});

    # non-fatal if above set failed
    if (not $ok)
    {
        $cce->warn('[[base-vsite.cantUpdateSiteAdminCaps]]');
    }
       
    return $ok;
}

=pod

=head1 NOTES

Never change the value of SERVER_ADMIN_GROUP or SITE_ADMIN_GROUP in any script.
The values in this module for these constants are considered law, and 
hard-coding these values in any script is strongly discouraged.  Just use the 
constant if you need the group name.

=head1 SEE ALSO

perl(1), CCE

=cut
1;
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
