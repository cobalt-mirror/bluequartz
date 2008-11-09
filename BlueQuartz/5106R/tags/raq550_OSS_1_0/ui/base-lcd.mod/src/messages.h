/*
*
*
*	File: 	messages.h
*	Andrew Bose	
*
*/

#include <libintl.h>
#define gettext_noop(String) (String)

unsigned char   selstr[] = gettext_noop("SELECT:         ");
unsigned char   sysstr[] = gettext_noop("  SYSTEM INFO   ");
unsigned char   netstr[] = gettext_noop(" SETUP NETWORK  ");
unsigned char   offstr[] = gettext_noop("   POWER DOWN   ");
unsigned char   qoffstr[] = gettext_noop("  POWER DOWN?   ");
unsigned char   rebstr[] = gettext_noop("     REBOOT     ");
unsigned char   qrebstr[] = gettext_noop("    REBOOT?     ");
unsigned char   resnetstr[] = gettext_noop(" RESET NETWORK  ");
unsigned char   qresnetstr[] = gettext_noop(" RESET NETWORK? ");
unsigned char   resingnetstr[] = gettext_noop("   RESETTING    ");
unsigned char   resingnetstr2[] = gettext_noop("    NETWORK     ");
unsigned char   resfiltstr[] = gettext_noop(" RESET FILTERS  ");
unsigned char   qresfiltstr[] = gettext_noop(" RESET FILTERS? ");
unsigned char   resingfiltstr[] = gettext_noop("   RESETTING    ");
unsigned char   resingfiltstr2[] = gettext_noop("    FILTERS     ");
unsigned char   extstr[] = gettext_noop("      EXIT      ");
unsigned char   qextstr[] = gettext_noop("     EXIT?      ");
unsigned char   ynstr[]  = gettext_noop("yes_no");
unsigned char   respwstr1[]  = gettext_noop("RESETTING ADMIN ");
unsigned char   respwstr2[]  = gettext_noop("   PASSWORD     ");
unsigned char   nullstr[]  = "                ";
unsigned char   exstr[] = gettext_noop("SAVE/CANCEL ");
unsigned char   finishstr[] = gettext_noop("      SAVE      ");
unsigned char   redostr[]   = gettext_noop("      EDIT      ");
unsigned char   ipstr[] = gettext_noop("PRIMARY IP ADDR:");
unsigned char   xipstr[] = gettext_noop("INVALID IP:     ");
unsigned char   ipaddrm[] = gettext_noop("IP Address:     ");
unsigned char   ipmess1[] = gettext_noop(" PLEASE SAVE IP ");
unsigned char   ipmess2[] = gettext_noop("    ADDRESS     ");
unsigned char   gwstr[] = gettext_noop("ENTER GATEWAY:  ");
unsigned char   xgwstr[] = gettext_noop("INVALID GATEWAY:");
unsigned char   nmstr[] = gettext_noop("PRIMARY NETMASK:");
unsigned char   xnmstr[] = gettext_noop("INVALID NETMASK:");
unsigned char   nmmess1[] = gettext_noop("  PLEASE SAVE   ");
unsigned char   nmmess2[] = gettext_noop("    NETMASK     ");
unsigned char   nsstr[] = gettext_noop("ENTER DNS:      ");
unsigned char   xnsstr[] = gettext_noop("INVALID DNS:    ");
unsigned char   verstr[] = gettext_noop("VERIFYING:      ");
unsigned char   savingstr[] = gettext_noop("     SAVING     ");
unsigned char   rebootingstr[] = gettext_noop("SYSTEM REBOOTING");
unsigned char   nonetstr[]  = gettext_noop("NO NETWORK FOUND");
unsigned char   connectstr[]  = gettext_noop("halt_connect");



unsigned char   ipaddr[] = "000.000.000.000";
unsigned char   fipaddr[] = "000.000.000.000";
unsigned char   gwaddr[] = "000.000.000.000";
unsigned char   fgwaddr[] = "000.000.000.000";
unsigned char   nsaddr[] = "000.000.000.000";
unsigned char   fnsaddr[] = "000.000.000.000";
unsigned char   noip[] = "000.000.000.000";
unsigned char   defmaskA[] = "255.000.000.000";
unsigned char   defmaskB[] = "255.255.000.000";
unsigned char   defmaskC[] = "255.255.255.000";
unsigned char   defmask[] = "255.255.255.000";
unsigned char   fdefmask[] = "255.255.255.000";
/* Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * -Redistribution of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 * 
 * -Redistribution in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution. 
 *
 * Neither the name of Sun Microsystems, Inc. or the names of contributors may
 * be used to endorse or promote products derived from this software without 
 * specific prior written permission.

 * This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.
 * 
 * You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
 */
