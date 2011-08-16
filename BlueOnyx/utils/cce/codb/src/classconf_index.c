/* $Id: classconf_index.c,v 1.6 2001/08/10 22:23:10 mpashniak Exp $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Implements the codb_index object defined in codb_classconf.h
 */

#include <cce_common.h>
#include <stdlib.h>
#include <string.h>

#include <codb_classconf.h>

struct codb_index_struct {
	char name[256];
	char *property;
	// SORTTYPE
};

codb_index *
codb_index_new(char *name, const char *classname, const char *property,
	char *sortname)
{
	codb_index *ind;
	
	ind = (codb_index *)malloc(sizeof(codb_index));
	if (!ind)
		return NULL;	/* OOM. */

	if (snprintf(ind->name, sizeof(ind->name), "%s.%s", classname, name)
		> sizeof(ind->name))
	{
		CCE_SYSLOG("Index %s on %s is too large.  Truncated.", name, classname);
	}
	ind->name[sizeof(ind->name) - 1] = '\0';

	ind->property = strdup(property);
/* SORTTYPE TODO */
/*	ind->sort = sortnametosorttype;
 */
	return ind;
}

char *
codb_index_get_name(codb_index *ind)
{
	return ind ? ind->name : NULL;
}

char *
codb_index_get_property(codb_index *ind)
{
	return ind ? ind->property : NULL;
}

void
codb_index_destroy(codb_index *ind)
{
	if (!ind)
		return;
	free(ind->property);
	free(ind);
}

codb_ret
codb_classconf_get_indexes(codb_classconf *cc, const char *classname,
	const char *namespace, const char *property, GSList **indexes)
{
	codb_class *class;
	codb_property *prop;

	class = codb_classconf_getclass(cc, classname, namespace);
	if (!class)
		return CODB_RET_UNKCLASS;

	prop = g_hash_table_lookup(codb_class_getproperties(class), property);
	*indexes = codb_property_getindexes(prop);
	return CODB_RET_SUCCESS;
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
