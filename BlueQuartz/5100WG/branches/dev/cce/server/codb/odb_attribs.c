/* $Id: odb_attribs.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * odb_attribs_hash
 *
 * A set of utility functions to simplify working with hashes of object
 * attributes.
 */

#include "cce_common.h"
#include "odb_attribs.h"
#include "codb.h"

#include <stdlib.h>
#include <string.h>

OAttrHash *
codb_attr_hash_new()
{
	OAttrHash *attr;

	attr = g_hashwrap_new(g_str_hash, g_str_equal, NULL, NULL);
	return attr;
}

gboolean ghr_attr_flush(gpointer key, gpointer value, gpointer data)
{
	if (key)
		free((char *)key);
	if (value)
		free((char *)value);
	return TRUE;		/* always remove */
}

void
codb_attr_hash_flush(OAttrHash * attr)
{
	g_hashwrap_foreach_remove(attr, ghr_attr_flush, NULL);
}

void
codb_attr_hash_destroy(OAttrHash * attr)
{
	codb_attr_hash_flush(attr);
	g_hashwrap_destroy(attr);
}

int
codb_attr_hash_assign(OAttrHash * attr, char *key, char *value)
{
	char *key_copy, *value_copy;

	if (g_hashwrap_lookup_extended(attr, key,
		(gpointer *) & key_copy, (gpointer *) & value_copy)) {
		/* preserve state of key_copy */
		free(value_copy);
		value_copy = strdup(value);
	} else {
		key_copy = strdup(key);
		value_copy = strdup(value);
	}

	g_hashwrap_insert(attr, key_copy, value_copy);
	return 0;
}

int
codb_attr_hash_remove(OAttrHash * attr, char *key)
{
	char *key_copy, *value_copy;

	if (g_hashwrap_lookup_extended(attr, key,
		(gpointer *) & key_copy, (gpointer *) & value_copy)) {
		g_hashwrap_remove(attr, key_copy);
		free(key_copy);
		free(value_copy);
		return 0;
	}
	return 0;
}

char *
codb_attr_hash_lookup(OAttrHash * attr, char *key)
{
	return g_hashwrap_lookup(attr, (gpointer) key);
}

void
codb_attr_hash_dump(OAttrHash * hash)
{
#ifdef DEBUG_CODB
	int hashi, hashn;

	fprintf(stderr, "\ndumping hash\n");
	hashn = g_hashwrap_size(hash);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(hash, hashi, &key, &val);
		fprintf(stderr, "\t\"%s\"=\"%s\"\n",
		    key ? (char *)key : "*undef*",
		    val ? (char *)val : "*undef*");
	}
#endif
}

/* eof */
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
