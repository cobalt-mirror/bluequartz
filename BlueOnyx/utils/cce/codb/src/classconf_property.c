/* $Id: classconf_property.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * implements the codb_property object defined in codb_classconf.h
 */

#include <cce_common.h>
#include <codb_classconf.h>
#include <stdlib.h>
#include <string.h>
#include <glib.h>

struct codb_property_struct {
	char *name;
	char *type;
	char *readacl;
	char *writeacl;
	char *def_val;
	int optional;
	int array;
	GSList *indexes;
	codb_typedef *type_binding; 
};

codb_property*
codb_property_new(char *name, char *type, 
	char *readacl, char *writeacl,
	char *def_val, char *optional, char *array)
{
	codb_property *prop;
	if (!name) { 
		return NULL; 
	}

	prop = malloc(sizeof(codb_property));
	if (prop) {
		int fail = 0;
		#define ASSIGN(x)          \
			if (!x) { x = ""; }  \
			prop->x = strdup(x); \
			if (!prop->x) { fail++; }
		ASSIGN(name);
		ASSIGN(type);
		ASSIGN(readacl);
		ASSIGN(writeacl);
		ASSIGN(def_val);
		#undef ASSIGN
   
		if (!optional || optional[0] == '\0' || !strcmp(optional, "0")) {
		    	prop->optional = 0;
		} else {
		    	prop->optional = 1;
		}

		if (!array || array[0] == '\0' || !strcmp(array, "0")) {
		    	prop->array = 0;
		} else {
		    	prop->array = 1;
		}

		prop->indexes = NULL;
		prop->type_binding = NULL;
		
		if (!prop->name || fail) {
			codb_property_destroy(prop);
			prop = NULL;
		}
	}
	return (prop);
}

int
codb_property_assign(codb_property *prop, char *name, char *type,
	char *readacl, char *writeacl,
	char *def_val, char *optional, char *array)
{
	int fail = 0;
	#define ASSIGN(x)		                           \
		if (x) {                                     \
			if (prop->x) free(prop->x);            \
			prop->x = strdup(x);                   \
			if (!prop->x) fail++;                  \
		} else { prop->x = NULL; }
	
	ASSIGN(name);
	ASSIGN(type);
	ASSIGN(readacl);
	ASSIGN(writeacl);
	ASSIGN(def_val);
	#undef ASSIGN
	prop->type_binding = NULL;

	if (!optional || optional[0] == '\0' || !strcmp(optional, "0")) {
		prop->optional = 0;
	} else {
		prop->optional = 1;
	}

	if (!array || array[0] == '\0' || !strcmp(array, "0")) {
		prop->array = 0;
	} else {
		prop->array = 1;
	}


	return fail;
}

const char *
codb_property_get_name ( codb_property *prop )
{
	if (!prop) {
		return NULL;
	}
	return prop->name;
}

const char *
codb_property_get_type ( codb_property *prop )
{
	if (!prop) {
		return NULL;
	}
	return prop->type;
}

void
codb_property_bindtype ( codb_property *prop, codb_typedef *td)
{
	prop->type_binding = td;
}

const char *
codb_property_get_readacl ( codb_property *prop )
{
	if (!prop) {
		return NULL;
	}
	return prop->readacl;
}

const char *
codb_property_get_writeacl ( codb_property *prop )
{
	if (!prop) {
		return NULL;
	}
	return prop->writeacl;
}

const char *
codb_property_get_def_val ( codb_property *prop )
{
	if (!prop) {
		return NULL;
	}
	return prop->def_val;
}

int
codb_property_get_optional(codb_property *prop)
{
	if (!prop) {
		return 0;
	}
	return prop->optional;
}

int
codb_property_get_array(codb_property *prop)
{
	if (!prop) {
		return 0;
	}
	return prop->array;
}

codb_typedef *
codb_property_get_typedef ( codb_property *prop )
{
	if (!prop) {
		return NULL;
	}
	return prop->type_binding;
}

void
codb_property_destroy(codb_property *prop)
{
	#define MYFREE(x) if(prop->x) free(prop->x)
	MYFREE(name);
	MYFREE(type);
	MYFREE(readacl);
	MYFREE(writeacl);
	MYFREE(def_val);

	if (prop->indexes) {
		GSList *curr;
		curr = prop->indexes;
		while (curr) {
			codb_index_destroy(curr->data);
			curr = g_slist_next(curr);
		}
		g_slist_free(prop->indexes);
	}
	free(prop);
}

int
codb_property_addindex(codb_property *prop, codb_index *ind)
{
	prop->indexes = g_slist_append(prop->indexes, ind);
	return CODB_RET_SUCCESS;
}

GSList *
codb_property_getindexes(codb_property *prop)
{
	if (!prop)
		return NULL;
	return (prop->indexes);
}

// eof
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
