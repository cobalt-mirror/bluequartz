/* $Id: populate_db.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
#include <cce_common.h>
#include <stdlib.h>
#include <stdio.h>
#include "debug.h"
#include <codb.h>
#include <libdebug.h>

void delete_db()
{
  /* flush the odb */
  system("/bin/rm -rf codb");
  system("/bin/rm -rf codb.oids");
}  

void mass_assign(GHashTable *attr, char **foo)
{
	while (*foo) {
	  odb_attr_hash_assign(attr, *foo, *(foo+1));
    foo+=2;
  }
}
  	
void
populate_site_with_users(odb_handle *h, char *sitename, int sitenum)
{
	GHashTable *attr;
  char username[80];
  char uid[80];
  char home[80];
  oid_t oid;
  int i;

	fprintf (stderr, "Adding users to site %s\n", sitename);
  
  for (i = 0; i < 200; i++)
  {
    sprintf(username, "s%du%d", sitenum, i);
    sprintf(uid, "%d", (sitenum * 1000) + i);
    sprintf(home, "/home/sites/%s/users/%s", sitename, username);
  
    attr = odb_attr_hash_new();
    {
	    char *foo[] = {
        "name", 					username,
        "enabled",				"t",
        "uid",						uid,
        "gid",						"101",
        "home",						home,
        "shell",					"/bin/sh",
        "password",				"fubar",
        "info_first",			username,
        "info_middle",		"Q",
        "info_last",			"Public",
        "info_sortname",	username,
        "sysadmin", 			"",
        "siteadmin", 			"",
        "site", 					sitename,
        0, 0,
      };
	    mass_assign(attr, foo);
		  odb_create(h, "User", attr, &oid);
    }
    odb_attr_hash_destroy(attr);
  }
}

void populate()
{
	odb_handle *h;
  GHashTable *attr;
  char buf[256];
  oid_t oid;
  int i;
  
  h = odb_handle_new(NULL);
  attr = odb_attr_hash_new();
  
  /* Create the system object */
  odb_attr_hash_flush(attr);
  {
	  char *foo[] = {
      "hostname", 		"myhost",
      "domainname",		"mydomain.com",
      "eth0_ipaddr",	"172.16.1.6",
      "eth0_netmask", "255.255.0.0",
      "eth0_up",			"t",
      "eth1_ipaddr",	"",
      "eth1_netmask",	"",
      "eth1_up",			"",
      "eth1_avail", 	"",
      "gateway",			"172.16.1.254",
      "dns_primary",	"172.16.1.1",
      "dns_secondary",	"172.16.1.2",
      "notify_email",	"foo@bar.com",
      "time_region",	"region1",
      "time_country",	"us",
      "time_zone",		"PST",
      "sitedef_ipaddr",			"172.16.1.6",
      "sitedef_domainname",	"mydomain.com",
      "sitedef_quota",			"10",
      "sitedef_maxusers",		"500",
      "reboot", "",
      0, 0,
    };
	  mass_assign(attr, foo);
  }
	odb_create(h, "System", attr, &oid);
  
  /* Create a few site objects */
  odb_attr_hash_flush(attr);
  {
	  char *foo[] = {
      "name",					"mysite",
      "hostname",			"mysite",
      "domainname",		"mydom.com",
      "maxusers",			"40",
      "quota",				"30",
      "ipaddr",				"172.16.2.1",
      "netmask",			"255.255.0.0",
      "enabled",			"t",
      0, 0,
    };
	  mass_assign(attr, foo);
  }
  for (i = 0; i < 9; i++) {
		sprintf(buf, "site%d", i);
    odb_attr_hash_assign(attr, "name", buf);
    odb_attr_hash_assign(attr, "hostname", buf);
		odb_create(h, "Site", attr, &oid);
    populate_site_with_users(h, buf, i);
    odb_commit(h);
  }
  
  odb_commit(h);
  
  /* free */
  odb_attr_hash_destroy(attr);
  odb_handle_destroy(h);
}  

int main()
{
	delete_db();
  populate();
  memdebug_dump();
  
  return 0;
}

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
