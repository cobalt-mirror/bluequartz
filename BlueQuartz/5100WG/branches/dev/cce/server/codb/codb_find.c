/* $Id: codb_find.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* the functions necessary to implement FIND */

#include "cce_common.h"
#include <string.h>
#include "codb.h"

#include "codb_handle.h"
#include "odb_transaction.h"
#include "odb_helpers.h"
#include "classconf.h"
#include "codb_security.h"
#include "compare.h"
#include "argparse.h"
#include <regex.h>
#include "g_hashwrap.h"

static int
hex2int(char c)
{
	if (c >= '0' && c <= '9') {
		return (int)(c - '0');
	}
	if (c >= 'a' && c <= 'f') {
		return (int)(10 + c - 'a');
	}
	if (c >= 'A' && c <= 'F') {
		return (int)(10 + c - 'A');
	}
	return -1;
}

/*
 * arraycmp: looks for a match of a string in a cce array.
 * returns: 0 if match, !0 if no match.
 */
static int
arraycmp(const char *str, char *criteria)
{
	int found;
	GString *buffer;

	found = 0;
	buffer = g_string_new("");

	if (*str == '&')
		str++;

	while ((!found) && (*str)) {
		if ((*str == '%')
		    && (hex2int(*(str + 1)) >= 0)
		    && (hex2int(*(str + 2)) >= 0)) {
			/* is an escaped special character: */
			int i;

			i = 16 * hex2int(*(str + 1)) + hex2int(*(str + 2));
			g_string_append_c(buffer, (gchar) i);
			str += 3;
		} else if (*str == '&') {
			/* separates the men from the boys: */
			if (strcmp(buffer->str, criteria) == 0)
				found = 1;
			g_string_assign(buffer, "");
			str += 1;
		} else {
			/* is an ordinary character: */
			g_string_append_c(buffer, (gchar) (*str));
			str += 1;
		}
	}
	if (*(buffer->str) != '\0') {
		if (strcmp(buffer->str, criteria) == 0)
			found = 1;
	}
	g_string_free(buffer, 1);

	return (found) ? 0 : -1;
}

/* return TRUE if match, FALSE otherwise */
int
codb_match_against_object(codb_handle *h,
    GHashWrap *criteria, const char *class, odb_oid *odboid)
{
	int flag;
	int hashi, hashn;

	DPRINTF(DBG_CODB, "codb_match_against_object: %ld(%s)\n",
	    (long)(odboid->oid), class);

	flag = 1;
	hashn = g_hashwrap_size(criteria);
	for (hashi = 0; hashi < hashn && flag; hashi++) {
		char *namespace;
		char *propname;
		const char *propval = NULL;
		GString *expanded_key = NULL;
		char *keybuf;
		cce_scalar *sc;
		int is_array_prop;
		gpointer key, val;

		g_hashwrap_index(criteria, hashi, &key, &val);

		sc = cce_scalar_new_undef();

		/* working copy */
		keybuf = strdup((char *)key);

		/* pre-process the key */
		if ((propname = strchr(keybuf, '.'))) {
			*propname = '\0';
			namespace = keybuf;
			propname++;
		} else {
			namespace = "";
			propname = keybuf;
		}

		expanded_key = g_string_new(namespace);
		expanded_key = g_string_append_c(expanded_key, '.');
		expanded_key = g_string_append(expanded_key, propname);

		/* try to get the object attribute */
		odb_txn_get(h->txn, odboid, expanded_key->str, sc);
		if (!cce_scalar_isdefined(sc)) {
			codb_getdefval(h, odboid, expanded_key->str, &sc);
		}

		DPRINTF(DBG_CODB, "-- key=%s val=%s\n",
		    expanded_key->str, (char *)sc->data);

		{
			codb_class *cl;
			GHashWrap *props;
			codb_property *p;
			codb_ret ret;

			cl = codb_classconf_getclass(h->classconf, class,
			    namespace);
			props = codb_class_getproperties(cl);
			p = (codb_property *)g_hashwrap_lookup(props,
			    propname);

			if (p) {
				/* check that we can read it */
				ret = codb_security_can_read_prop(h,
				    odboid->oid, p);
				if (ret != CODB_RET_SUCCESS) {
					propval = NULL;
				} else if (sc->data) {
					/* we can read it, and it exists */
					propval = (const char *)sc->data;
				} else {
					/* try a default value */
					propval =
					    codb_property_get_def_val(p);
				}
				is_array_prop = codb_property_get_array(p);
			}
		}

		/* compare the object attribute with the criteria */
		DPRINTF(DBG_CODB,
		    "    comparing: %ld.%s \"%s\" =? \"%s\": ",
		    odboid->oid, expanded_key->str, propval, (char *)val);

		if (!propval)
			flag = 0;
		else {
			if (is_array_prop) {
				if (arraycmp(propval, (char *)val) != 0) {
					flag = 0;
				}
			} else {
				if (strcmp(propval, (char *)val) != 0) {
					flag = 0;
				}
			}
		}
		if (flag) {
			DPRINTF(DBG_CODB, "MATCH\n");
		} else {
			DPRINTF(DBG_CODB, "no match\n");
		}

		/* cleanup */
		if (expanded_key)
			g_string_free(expanded_key, 1);
		free(keybuf);
		cce_scalar_destroy(sc);
	}

	return (flag);
}

void
codb_regmatch_oidlist(codb_handle *h, const char *propname, regex_t * reg,
    const char *class, odb_oidlist * oidlist, odb_oidlist * oidlist2)
{
	odb_oid *odboid;
	cce_scalar *sc = cce_scalar_new_undef();
	GString *expanded_key = NULL;
	char *namespace, *prop;
	char *keybuf = NULL;

	/* working copy */
	keybuf = strdup(propname);

	/* pre-process the key */
	if ((prop = strchr(keybuf, '.'))) {
		*prop = '\0';
		namespace = keybuf;
		prop++;
	} else {
		namespace = "";
		prop = keybuf;
	}

	expanded_key = g_string_new(namespace);
	expanded_key = g_string_append_c(expanded_key, '.');
	expanded_key = g_string_append(expanded_key, prop);

	for (odboid = odb_oidlist_first(oidlist);
	    odboid; odboid = odb_oidlist_next(oidlist)) {
		odb_txn_get(h->txn, odboid, expanded_key->str, sc);
		if (!cce_scalar_isdefined(sc)) {
			codb_getdefval(h, odboid, expanded_key->str, &sc);
		}

		if (cce_scalar_isdefined(sc)
		    && !regexec(reg, cce_scalar_string(sc), 0, NULL, 0)) {
			odb_oidlist_add(oidlist2, odboid, 1, NULL);
		}
	}

	cce_scalar_destroy(sc);
	if (expanded_key)
		g_string_free(expanded_key, 1);
	if (keybuf)
		free(keybuf);

}

/*
 * This function does the best job it can of finding a small group of oids
 * given the class, and the criteria.
 * It uses the first index found when inspecting the criteria
 */
codb_ret
codb_find_oidlist_from_criteria(codb_handle *h, char *class,
    GHashWrap *criteria, odb_oidlist * oids)
{
	codb_ret ret = CODB_RET_SUCCESS;
	GSList *indexes = NULL;
	codb_index *index;
	int hashi, hashn;
	gpointer key, val;

	hashn = g_hashwrap_size(criteria);
	for (hashi = 0; hashi < hashn; hashi++) {
		char *propname;
		char *keybuf;
		char *namespace;

		g_hashwrap_index(criteria, hashi, &key, &val);

		/* working copy */
		keybuf = strdup((char *)key);

		/* pre-process the key */
		if ((propname = strchr(keybuf, '.'))) {
			*propname = '\0';
			namespace = keybuf;
			propname++;
		} else {
			namespace = "";
			propname = keybuf;
		}

		ret = codb_classconf_get_indexes(h->classconf,
		    class, namespace, propname, &indexes);
		/* 
		 * TODO: work on all attribute indexes to find
		 * the best ones.  for now, just use the first.
		 */
		free(keybuf);
		if (indexes)
			break;
	}

	if (indexes) {
		/* TODO: work on all indexes. * for now, just pick first * 
		 * one. */
		index = indexes->data;

		ret = odb_txn_index_get(h->txn, val,
		    codb_index_get_name(index), oids);
	} else {
		ret = odb_txn_index_get(h->txn, class, "classes", oids);
	}
	return ret;
}

codb_ret
codb_sort_oidlist(codb_handle *h, odb_oidlist * oidlist, GSList ** oids,
    char *class, const char *sorttype, const char *sortprop)
{
	GSList *sorted = NULL;
	GSList *cursor = NULL;
	odb_oid *oid;
	sortfunc *compfunc = NULL;
	codb_matchtype *sortwith = NULL;
	GString *expanded_key = NULL;
	const char *prop = NULL;
	GSList *args;

	if (sorttype) {
		const char *next;
		char *func;

		if (!arg_parse(sorttype, &func, &args, NULL, &next)) {
			sortwith =
			    codb_classconf_getmatchtype(h->classconf,
			    func);
		}
		if (func) {
			free(func);
		}
		if (next && *next) {
			free_arglist(args);
			return CODB_RET_BADARG;
		}
		if (sortwith) {
			compfunc = codb_matchtype_getcompfunc(sortwith);
		}
		if (!compfunc) {
			/* if we're given a sorttype, we must find a func */
			free_arglist(args);
			return CODB_RET_BADARG;
		}
	}

	/* TODO: generic property name fixup everywhere */
	/* TODO: verify sortprop */
	if (sortprop) {
		if (!strchr(sortprop, '.')) {
			expanded_key = g_string_new(".");
			expanded_key = g_string_append(expanded_key,
			    sortprop);
			prop = expanded_key->str;
		} else {
			prop = sortprop;
		}
	}

	oid = odb_oidlist_first(oidlist);
	while (oid) {
		sortstruct *stuff;

		stuff = (sortstruct *) malloc(sizeof(sortstruct));
		stuff->value = cce_scalar_new_undef();
		stuff->oid = oid->oid;
		if (prop && compfunc) {
			stuff->args = args;
			odb_txn_get(h->txn, oid, prop, stuff->value);
			if (!cce_scalar_isdefined(stuff->value)) {
				codb_getdefval(h, oid, prop,
				    &stuff->value);
			}

			sorted = g_slist_insert_sorted(sorted, stuff,
			    compfunc);
		} else {
			/* unsorted */
			sorted = g_slist_append(sorted, stuff);
		}
		oid = odb_oidlist_next(oidlist);
	}

	if (expanded_key) {
		g_string_free(expanded_key, 1);
	}

	cursor = sorted;
	while (cursor) {
		sortstruct *s = cursor->data;
		oid_t *oid = malloc(sizeof(oid_t));

		*oid = s->oid;
		*oids = g_slist_append(*oids, oid);

		/* free the data from the sorted list */
		cce_scalar_destroy(s->value);
		free(s);
		cursor = cursor->next;
	}
	g_slist_free(sorted);

	return CODB_RET_SUCCESS;
}

/*
 * codb_find
 *
 * arguements:
 *   h - codb object
 *   class - name of class to search within
 *   criteria - ghash of tests (key is attribute to test, value is value
 *      to compare against).
 *   regexcriteria - ghash of tests (key is attribute to test, value is value
 *      to compare against).
 *   sorttype - classconf matchtype to use to sort
 *   sortprop - namespace/property to sort by
 *   oids - GSList to store oid_t object id's in.
 */
codb_ret
codb_find(codb_handle *h, char *class,
    GHashWrap *criteria, GHashWrap *regexcriteria,
    const char *sorttype, const char *sortprop, GSList ** oids)
{
	codb_ret ret = CODB_RET_SUCCESS;
	odb_oidlist *oidlist;	/* from indexes */
	odb_oidlist *oidlist2;	/* after criteria match */
	odb_oidlist *oidlist3;	/* after regex match */
	odb_oid *odboid;
	codb_class *cl;

	/* flag the oidlist as NULL, in case it wasn't initialized */
	*oids = NULL;

	/* check classconf for the class */
	if (!(cl =
		codb_classconf_getclass(h->classconf, (char *)class,
		 ""))) return CODB_RET_UNKCLASS;

	DPRINTF(DBG_CODB, "Finding a %s:\n", class);

	oidlist = odb_oidlist_new();

	/* Find the list of oids */
	if (!criteria || !g_hashwrap_size(criteria)) {
		ret = odb_txn_index_get(h->txn, class, "classes", oidlist);
	} else {
		ret = codb_find_oidlist_from_criteria(h, class, criteria,
		    oidlist);
		/* TODO: remove index criteria from hash table */
	}

	if (ret != CODB_RET_SUCCESS)
		return ret;

	if (criteria && g_hashwrap_size(criteria)) {
		oidlist2 = odb_oidlist_new();
		/* for every oid: check if it matches criteria */
		for (odboid = odb_oidlist_first(oidlist);
		    odboid; odboid = odb_oidlist_next(oidlist)) {
			if (codb_match_against_object(h, criteria, class,
				odboid)) {
				odb_oidlist_add(oidlist2, odboid, 1, NULL);
			}
		}
	} else {
		/* for the next stage, use the list we had before */
		oidlist2 = oidlist;
		oidlist = NULL;
	}
	if (regexcriteria && g_hashwrap_size(regexcriteria)) {
		regex_t reg;
		int hashi, hashn;

		/* for every regex */
		hashn = g_hashwrap_size(regexcriteria);
		for (hashi = 0; hashi < hashn; hashi++) {
			gpointer key, val;

			g_hashwrap_index(regexcriteria, hashi, &key, &val);

			regcomp(&reg, val,
			    REG_EXTENDED | REG_NOSUB | REG_NEWLINE);
			/* for every oid, check if it matches the regexes */
			oidlist3 = odb_oidlist_new();
			codb_regmatch_oidlist(h, key, &reg, class,
			    oidlist2, oidlist3);
			/* next time, check all the oids we found this * * 
			 * time */
			odb_oidlist_destroy(oidlist2);
			oidlist2 = oidlist3;
			oidlist3 = NULL;
		}
		/* for the next stage, so we have the list we found */
		oidlist3 = oidlist2;
		oidlist2 = NULL;
	} else {
		/* for the next stage, use the list we had before */
		oidlist3 = oidlist2;
		oidlist2 = NULL;
	}

	/* sort found_object_list */
	ret =
	    codb_sort_oidlist(h, oidlist3, oids, class, sorttype,
	    sortprop);

	/* free oid lists */
	if (oidlist) {
		odb_oidlist_destroy(oidlist);
	}
	if (oidlist2) {
		odb_oidlist_destroy(oidlist2);
	}
	if (oidlist3) {
		odb_oidlist_destroy(oidlist3);
	}

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
