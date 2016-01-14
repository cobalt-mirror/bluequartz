/* $Id: ccewrap_capability.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <stdlib.h>
#include <string.h>
#include <stdio.h>

#include "../include/cce.h"
#include "../include/xml_parse.h"

#include "ccewrap_capability.h"

struct ccewrapconf_capability_t {
	char *requires;
	char *user;
};

struct ccewrapconf_capability_t *
mk_capability (struct xml_element *e)
{
	struct ccewrapconf_capability_t *cap;
	char *requires;
	char *user;

	cap = ccewrapconf_capability_new();

	requires = (char *)g_hash_table_lookup(e->el_props, "requires");
	user = (char *)g_hash_table_lookup(e->el_props, "user");

	if (requires) {
		ccewrapconf_capability_setrequires(cap, requires);
	}

	if (user) {
		ccewrapconf_capability_setuser(cap, user);
	}

	return cap;
}

struct ccewrapconf_capability_t *
ccewrapconf_capability_new (void) 
{
	struct ccewrapconf_capability_t *capability;
	capability = malloc(sizeof(struct ccewrapconf_capability_t));
	capability->requires = NULL;
	capability->user = NULL;
	return capability;
}

void
ccewrapconf_capability_free (struct ccewrapconf_capability_t *capability)
{
	free(capability);
}

void
ccewrapconf_capability_setrequires (struct ccewrapconf_capability_t *capability, char *requires)
{
	capability->requires = requires;
}

char *
ccewrapconf_capability_getrequires (struct ccewrapconf_capability_t *capability)
{
	return (capability->requires ? capability->requires : "");
}

void
ccewrapconf_capability_setuser (struct ccewrapconf_capability_t *capability, char *user) 
{
	capability->user = user;
}

char *
ccewrapconf_capability_getuser (struct ccewrapconf_capability_t *capability)
{
	return (capability->user != NULL ? capability->user : "");
}

int
ccewrapconf_capability_allowed(struct ccewrapconf_capability_t *capability, ccewrapconf conf, GList **validUsers) 
{
	char *capstring;
	char *searchstring;
	char *capname;
	int ret = 0;


	/* systemAdministators pass all capability checks */
	if (ccewrapconf_issystemadministrator(conf)) {
		return 1;
	}

	capstring = ccewrapconf_getusercapabilities(conf);
	
	/* check this user's caps to see if they can do this */
	capname = ccewrapconf_capability_getrequires(capability);
	searchstring = malloc(strlen(capname)+3); 
	sprintf(searchstring, "&%s&", capname);

	if (strstr(capstring, searchstring)) {
		*validUsers = g_list_append(*validUsers, ccewrapconf_capability_getuser(capability));	
		ret = 1;
	}

	if (!strcmp(capname, "") || capname == NULL) {
		/* default of no caps specified is to allow it */
		*validUsers = g_list_append(*validUsers, ccewrapconf_capability_getuser(capability));
		ret = 1;
	}

	free(searchstring);

	return ret;
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
