/* $Id: codb2_glue.c 229 2003-07-18 20:22:20Z will $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/* 
 * Glues the odb_txn block into the codb2 (CSCP-Lite) interface.
 * author: jmayer@cobalt.com
 */
#include "cce_common.h"
/* implement this api: */
#include "codb.h"

/* implement in terms of: */
#include "codb_handle.h"
#include "odb_transaction.h"
#include "odb_txn_inspect.h"
#include "odb_txn_events.h"
#include "odb_helpers.h"
#include "classconf.h"
#include "codb_security.h"
#include <ctype.h>
#include <string.h>
#include "g_hashwrap.h"

/* this is the global class configuration */
codb_classconf *classconf = NULL;

/* global RO flag */
static int codb_is_ro = 0;

static codb_ret get_prop_old(codb_handle *h, odb_oid *oid,
    const char *ns, char *key, cce_scalar *val);
static codb_ret set_prop(codb_handle *h, odb_oid *oid, const char *ns,
    char *key, const char *val);
static codb_ret codb_set_core(codb_handle *h, oid_t oid,
    const char *namespace, GHashWrap *attribs, GHashWrap *attriberrs);
static codb_ret codb_indexing_core(codb_handle *h, oid_t oid,
    const char *classname, const char *namespace, GHashWrap *attribs);
static codb_ret codb_indexing_remove_oid(codb_handle *h, oid_t oid);

/* constructor and destructor code */
codb_handle *
codb_handle_new(const char *codbdir, cce_conf *conf)
{
	codb_handle *h;

	h = malloc(sizeof(codb_handle));

	if (!h) {
		return NULL;
	}

	h->flags = 0;
	h->conf = conf;
	h->impl = NULL;
	h->txn = NULL;
	h->root = NULL;
	h->branch_count = 0;

	h->impl = impl_handle_new(codbdir);
	if (!h->impl) {
		free(h);
		return NULL;
	}

	h->txn = odb_txn_new(h->impl);
	if (!h->txn) {
		impl_handle_destroy(h->impl);
		free(h);
		return NULL;
	}

	/* get the global class info */
	// FIXME: this can go away
	h->classconf = classconf;

	h->cur_oid = 0;

	DPRINTF(DBG_CODB, "CODB created h(0x%lx)\n", (long)h);

	return (h);
}

codb_handle *
codb_handle_branch(codb_handle *h)
{
	codb_handle *h2;

	h2 = malloc(sizeof(codb_handle));

	if (!h2) {
		return NULL;
	}

	h2->flags = h->flags;
	h2->conf = h->conf;
	h2->impl = h->impl;
	h2->classconf = h->classconf;	/* booya! */
	h2->txn = NULL;
	h2->root = h;
	h2->branch_count = h->branch_count + 1;	/* ya silly */

	h2->txn = odb_txn_new_meta(h2->impl, h->txn);
	if (!h2->txn) {
		free(h2);
		return NULL;
	}

	h2->cur_oid = h->cur_oid;

	DPRINTF(DBG_CODB, "CODB branched h2(0x%lx) off of h1(0x%lx)\n",
	    (long)h2, (long)h);

	return (h2);
}

int
codb_handle_branch_level(codb_handle *h)
{
	return h->branch_count;
}

codb_handle *
codb_handle_rootref(codb_handle *h)
{
	if (h->root)
		return h->root;
	else
		return h;
}

void
codb_handle_unbranch(codb_handle *h)
{
	odb_txn_destroy(h->txn);
	free(h);		/* free the handle itself */

	DPRINTF(DBG_CODB, "CODB unbranched h(0x%lx)\n", (long)h);
}

void
codb_handle_destroy(codb_handle *h)
{
	odb_txn_destroy(h->txn);
	impl_handle_destroy(h->impl);
	free(h);		/* free the handle itself */

	DPRINTF(DBG_CODB, "CODB destroyed h(0x%lx)\n", (long)h);
}

codb_ret
codb_handle_setoid(codb_handle *h, oid_t oid)
{
	// sets the oid that represents the current authenticated user.

	h->cur_oid = oid;

	return CODB_RET_SUCCESS;
}

oid_t
codb_handle_getoid(codb_handle *h)
{
	// gets the oid that represents the current authenticated user.

	return h->cur_oid;
}

void
codb_handle_setflags(codb_handle *h, unsigned int flags)
{
	h->flags = flags;
}

void
codb_handle_addflags(codb_handle *h, unsigned int flags)
{
	h->flags |= flags;
}

void
codb_handle_rmflags(codb_handle *h, unsigned int flags)
{
	h->flags &= (~flags);
}

unsigned int
codb_handle_getflags(codb_handle *h)
{
	return h->flags;
}


/* 
 * codb_create()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *  CODB_RET_UNKCLASS
 *  CODB_RET_BADDATA
 *  CODB_RET_PERMDENIED
 */
codb_ret
codb_create(codb_handle *h, const char *class, GHashWrap *attribs,
    GHashWrap *attriberrs, oid_t *oid)
{
	codb_ret ret;
	odb_oid odboid;
	struct timeval start;

	DPROFILE_START(PROF_CODB, &start, "codb_create()");

	/* make sure the class requested exists, and verify data */
	ret = codb_classconf_validate(h->classconf, class, NULL,
	    attribs, attriberrs);
	if (ret != CODB_RET_SUCCESS) {
		return ret;
	}

	/* 
	 * everything looks OK - create the object 
	 */

	odboid = odb_txn_oid_grab(h->txn);
	if (!odboid.oid) {
		/* h->lasterr is already set */
		return CODB_RET_OUTOFOIDS;
	}

	ret = odb_txn_createobj(h->txn, &odboid, class);
	if (ret != CODB_RET_SUCCESS) {
		odb_txn_oid_release(h->txn, odboid);
		return ret;
	}
	*oid = odboid.oid;	/* bleh */

	/* 
	 * set all magic attributes - NAMESPACE is a read-only value
	 */

	/* CLASS */
	ret = set_prop(h, &odboid, NULL, "CLASS", class);

	/* CLASSVER */
	if (ret == CODB_RET_SUCCESS) {
		codb_class *cc_class;
		const char *ver;

		cc_class =
		    codb_classconf_getclass(h->classconf, class, "");
		if (!cc_class) {
			ver = "";
		} else {
			ver = codb_class_get_version(cc_class);
		}

		ret = set_prop(h, &odboid, NULL, "CLASSVER", ver);
	}

	/* OID */
	if (ret == CODB_RET_SUCCESS) {
		char p[30];

		sprintf(p, "%ld", (long)odboid.oid);
		ret |= set_prop(h, &odboid, NULL, "OID", p);
	}

	if (ret == CODB_RET_SUCCESS) {
		ret = codb_set_core(h, *oid, "", attribs, attriberrs);
	}

	if (ret == CODB_RET_SUCCESS) {
		ret = codb_indexing_core(h, *oid, class, "", attribs);
	}

	DPROFILE(PROF_CODB, start, "codb_create() is done");

	/* 
	 * make sure the user is allowed to do this 
	 * We do this AFTER everything so that external rules can do 
	 * "get OID" and get all the correct infomation. 
	 */
	if (ret == CODB_RET_SUCCESS) {
		ret = codb_security_can_create(h, class, *oid, attriberrs);
	}

	/* security check all the properties also */
	if (ret == CODB_RET_SUCCESS) {
		ret = codb_security_write_filter(h, *oid, class, "",
		    attribs, attriberrs);
	}

	if (ret != CODB_RET_SUCCESS) {
		odb_txn_destroyobj(h->txn, &odboid, 1);
		/* FIXME: I'd like to free the oid here, but I'm afraid of 
		 * what may happen if the same oid is re-used within the 
		 * txn.  I'd rather tolerate an oid leak, for now. */
		return ret;
	}

	return CODB_RET_SUCCESS;
}

/* helper fn to get original properties */
static codb_ret
get_prop_old(codb_handle *h, odb_oid *oid, const char *ns, char *key,
    cce_scalar *val)
{
	GString *keybuf;
	int ret;

	/* build the key */
	keybuf = g_string_new(ns);
	g_string_append_c(keybuf, '.');
	g_string_append(keybuf, key);

	ret = odb_txn_get_old(h->txn, oid, keybuf->str, val);

	/* clean up */
	g_string_free(keybuf, 1);

	return ret;
}

/* helper fn to set properties */
static codb_ret
set_prop(codb_handle *h, odb_oid *oid, const char *ns, char *key,
    const char *val)
{
	GString *keybuf;
	cce_scalar *scbuf;
	int ret;

	/* JIC */
	if (!ns) {
		ns = "";
	}

	/* FIXME: a bit superflouous at this point, n'est ce pas ? */
	if (!isalpha(*key) && *key != '_') {
		/* must start with [A-Za-z_] */
		return CODB_RET_BADDATA;
	}

	/* build the key */
	keybuf = g_string_new(ns);
	g_string_append_c(keybuf, '.');
	g_string_append(keybuf, key);

	/* value */
	scbuf = cce_scalar_new_from_str(val);

	/* check to see if we're _really_ changing the property: */
	{
		codb_ret ret;
		cce_scalar *val_copy;

		val_copy = cce_scalar_new_undef();
		ret = odb_txn_get(h->txn, oid, keybuf->str, val_copy);
		if (ret == CODB_RET_SUCCESS &&
		    !cce_scalar_isdefined(val_copy)) {
			ret =
			    codb_getdefval(h, oid, keybuf->str, &val_copy);
		}
		if (cce_scalar_compare(scbuf, val_copy) == 0) {
			/* they are the same */

			cce_scalar_destroy(val_copy);
			cce_scalar_destroy(scbuf);
			g_string_free(keybuf, 1);
			return CODB_RET_SUCCESS;
		}
		cce_scalar_destroy(val_copy);
	}

	/* do it */
	ret = odb_txn_set(h->txn, oid, keybuf->str, scbuf);

	/* clean up */
	cce_scalar_destroy(scbuf);
	g_string_free(keybuf, 1);

	return ret;
}

/* 
 * codb_destroy()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *  CODB_RET_UNKOBJ
 */
codb_ret
codb_destroy(codb_handle *h, oid_t oid, GHashWrap *errs)
{
	codb_ret ret;
	odb_oid odboid;
	struct timeval start;

	DPROFILE_START(PROF_CODB, &start, "codb_destroy()");

	if (!codb_objexists(h, oid)) {
		return CODB_RET_UNKOBJ;
	}

	/* make sure the user is allowed to do this */
	ret = codb_security_can_destroy(h, oid, errs);
	if (ret != CODB_RET_SUCCESS) {
		return ret;
	}

	odboid.oid = oid;

	ret = odb_txn_destroyobj(h->txn, &odboid, 1);
	DPRINTF(DBG_CODB, "odb_txn_destroyobj ret=%d\n", ret);

	// tag an oid for eventual release back into the oid pool
	odb_txn_oid_release(h->txn, odboid);

	// find all the indexes
	codb_indexing_remove_oid(h, oid);

	DPROFILE(PROF_CODB, start, "codb_destroy() is done");

	return ret;
}

int
codb_objexists(codb_handle *h, oid_t oid)
{
	odb_oid myoid;

	myoid.oid = oid;
	return odb_txn_objexists(h->txn, &myoid);
}

char *
codb_get_classname(codb_handle *h, oid_t oid)
{
	char *buf = NULL;
	odb_oid myoid;
	cce_scalar *sc;
	codb_ret ret;

	myoid.oid = oid;
	sc = cce_scalar_new_undef();

	ret = odb_txn_get(h->txn, &myoid, ".CLASS", sc);

	if (ret == CODB_RET_SUCCESS) {
		if (cce_scalar_isdefined(sc)) {
			buf = strdup(sc->data);
		}
	} else {
		ret = odb_txn_get_old(h->txn, &myoid, ".CLASS", sc);
		if (ret == CODB_RET_SUCCESS) {
			if (cce_scalar_isdefined(sc)) {
				buf = strdup(sc->data);
			}
		} else {
			buf = NULL;
		}
	}
	cce_scalar_destroy(sc);
	return buf;
}

#define GFLAGS_OLD  (0x1)
#define GFLAGS_CHANGED  (0x2)
inline static codb_ret
 odb_get_general(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs, int flags);

codb_ret
codb_get(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs)
{
	return odb_get_general(h, oid, namespace, attribs, 0);
}

codb_ret
codb_get_old(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs)
{
	return odb_get_general(h, oid, namespace, attribs, GFLAGS_OLD);
}

codb_ret
codb_get_changed(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs)
{
	return odb_get_general(h, oid, namespace, attribs, GFLAGS_CHANGED);
}

codb_ret
odb_get_general(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs, int flags)
{
	odb_oid myoid;
	codb_ret ret;
	char *class;
	gint propidx;
	GHashWrap *props;
	codb_class *cl;
	gpointer k, v;

	myoid.oid = oid;

	/* check for the existence of the object */
	if (flags & GFLAGS_OLD) {
		if (!odb_txn_objexists_old(h->txn, &myoid)) {
			return CODB_RET_UNKOBJ;
		}
	} else {
		if (!odb_txn_objexists(h->txn, &myoid)) {
			return CODB_RET_UNKOBJ;
		}
	}

	/* make sure the namespace is valid */
	class = codb_get_classname(h, oid);
	if (!class) {
		return CODB_RET_UNKCLASS;
	}

	/* verify it through classconf */
	ret =
	    codb_classconf_validate(h->classconf, class, namespace, NULL,
	    NULL);
	if (ret != CODB_RET_SUCCESS) {
		free(class);
		return ret;
	}

	if (!namespace)
		namespace = "";

	cl = codb_classconf_getclass (h->classconf, class, namespace);
	props = codb_class_getproperties (cl);

	/* for each property in the class... */
	propidx = 0;
	while (!g_hashwrap_index(props, propidx++, &k, &v) &&
	    ret == CODB_RET_SUCCESS) {
		cce_scalar *sc;
		char *key = k;
		GString *nsbuf;
		codb_property *p = (codb_property *) v;

		nsbuf = g_string_new (namespace);
		g_string_append_c (nsbuf, '.');
		g_string_append(nsbuf, key);

		if (flags & GFLAGS_CHANGED) {
			/* skip this attribute unless it changes in this txn */
			if (!odb_txn_is_changed (h->txn, &myoid, nsbuf->str)) {
				g_string_free(nsbuf, 1);
				continue;
			}
		}

		sc = cce_scalar_new_undef ();
		if (flags & GFLAGS_OLD) {
			ret = odb_txn_get_old (h->txn, &myoid, nsbuf->str, sc);
		} else {
			ret = odb_txn_get (h->txn, &myoid, nsbuf->str, sc);
		}
		/* if we couldn't find the object, that simply means we
		 * backed up into a transaction layer that doesn't contain
		 * the (newly created) object.  In this case, we SUCCEEDED,
		 * because we KNOW the object exists (see the first checks
		 * in this function), and this simply means we couldn't
		 * find any modification of the property from it's default
		 * value.  Since sc will be undefined in this case, we'll
		 * use the default value of the property which is... the
		 * correct thing to do.
		*/
		if (ret == CODB_RET_UNKOBJ) {
			ret = CODB_RET_SUCCESS;
		}
		if (cce_scalar_isdefined (sc)) {
			g_hashwrap_insert (attribs, strdup(key), strdup(sc->data));
		} else {
			g_hashwrap_insert (attribs, strdup(key),
			strdup (codb_property_get_def_val (p)));
		}
		cce_scalar_destroy (sc);
		g_string_free(nsbuf, 1);
	}

	/* Magic properties.  CLASS, NAMESPACE, CLASSVER, OID */
	if (!(flags & GFLAGS_CHANGED)) {
		const char *ver;

		/* If we're looking at the default namespace, add the "CLASS" 
		* and "OID" properties */
		if (!*namespace) {
			char p[30];

			g_hashwrap_insert (attribs, strdup ("CLASS"), strdup(class));
			sprintf(p, "%ld", (long)oid);
			g_hashwrap_insert (attribs, strdup ("OID"), strdup(p));
		}

		/* have to have a magic NAMESPACE property - spec says so */
		g_hashwrap_insert (attribs, strdup ("NAMESPACE"),
		strdup (namespace));

		/* have to have a CLASSVER property */
		ver = codb_class_get_version (cl);
		g_hashwrap_insert (attribs, strdup("CLASSVER"), strdup(ver));
	}

	/* filter out properties the client is not allowed to read */
	codb_security_read_filter (h, oid, class, namespace, attribs);

	free (class);


	return ret;
}

/* 
 * codb_set()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *  CODB_RET_UNKCLASS
 *  CODB_RET_UNKNSPACE
 *  CODB_RET_UNKOBJ
 *  CODB_RET_BADDATA
 *  CODB_RET_PERMDENIED
 */
codb_ret
codb_set(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs, GHashWrap *data_errs, GHashWrap *perm_errs)
{
	codb_ret ret;
	char *classname;

	struct timeval start;

	DPROFILE_START(PROF_CODB, &start, "codb_set()");

	/* verify the object exists */
	if (!codb_objexists(h, oid)) {
		return CODB_RET_UNKOBJ;
	}

	if (!attribs) {
		/* well, they asked us to set nothing... */
		return CODB_RET_SUCCESS;
	}

	if (!namespace)
		namespace = "";

	/* get the class of the oid */
	classname = codb_get_classname(h, oid);
	if (!classname) {
		return CODB_RET_UNKCLASS;
	}

	/* verify it through classconf */
	ret = codb_classconf_validate(h->classconf, classname, namespace,
	    attribs, data_errs);
	if (ret != CODB_RET_SUCCESS) {
		free(classname);
		return ret;
	}

	/* security check it */
	ret = codb_security_write_filter(h, oid, classname, namespace,
	    attribs, perm_errs);

	if (ret != CODB_RET_SUCCESS) {
		return ret;
	}

	/* 
	 * everything seems OK - let's go
	 */

	ret = codb_set_core(h, oid, namespace, attribs, data_errs);

	if (ret == CODB_RET_SUCCESS) {
		ret =
		    codb_indexing_core(h, oid, classname, namespace,
		    attribs);
	}

	free(classname);

	DPROFILE(PROF_CODB, start, "codb_set() is done");

	return ret;
}


static codb_ret
codb_set_core(codb_handle *h, oid_t oid, const char *namespace,
    GHashWrap *attribs, GHashWrap *attriberrs)
{
	codb_ret ret;
	odb_oid myoid;
	int hashi, hashn;

	myoid.oid = oid;

	hashn = g_hashwrap_size(attribs);
	for (hashi = 0; hashi < hashn; hashi++) {
		gpointer key, val;

		g_hashwrap_index(attribs, hashi, &key, &val);

		/* can't set CLASS, OID, or NAMESPACE */
		if (codb_is_magic_prop(key)) {
			/* silently ignore */
			continue;
		}

		ret = set_prop(h, &myoid, namespace, key, val);
		if (ret != CODB_RET_SUCCESS) {
			return ret;
		}
	}

	return CODB_RET_SUCCESS;
}

/** remove oid from all properties of this namespace
 */
static codb_ret
codb_indexing_remove_oid_namespace(codb_handle *h, oid_t oid,
    codb_class *class)
{
	GHashWrap *prophash;
	odb_oid myoid;
	cce_scalar *oldval;
	int hashi, hashn;

	oldval = cce_scalar_new_undef();
	myoid.oid = oid;

	prophash = codb_class_getproperties(class);

	hashn = g_hashwrap_size(prophash);
	for (hashi = 0; hashi < hashn; hashi++) {
		GSList *indexes = NULL;
		gpointer key, val;

		g_hashwrap_index(prophash, hashi, &key, &val);

		indexes = codb_property_getindexes(val);
		if (!indexes)
			continue;

		get_prop_old(h, &myoid, codb_class_get_namespace(class),
		    (char *)key, oldval);
		if (!cce_scalar_isdefined(oldval))
			continue;

		while (indexes) {
			codb_index *index = indexes->data;

			odb_indexing_update(h->txn,
			    codb_index_get_name(index),
			    &myoid, oldval->data, 0);
			indexes = g_slist_next(indexes);
		}
	}

	cce_scalar_free(oldval);
	return CODB_RET_SUCCESS;
}

/** find all indexes of a class, and remove this oid from all of them
 */
static codb_ret
codb_indexing_remove_oid(codb_handle *h, oid_t oid)
{
	codb_ret ret;
	char *classname;
	codb_class *class;
	GSList *namespaces = NULL;
	GSList *ns = NULL;

	classname = codb_get_classname(h, oid);
	if ((ret = codb_names(h, classname, &namespaces)))
		return ret;

	/* add the default namespace */
	namespaces = g_slist_append(namespaces, strdup(""));

	ns = namespaces;
	while (ns) {
		class =
		    codb_classconf_getclass(h->classconf, classname,
		    ns->data);
		codb_indexing_remove_oid_namespace(h, oid, class);

		/* free the strings as we go */
		free(ns->data);
		ns = g_slist_next(ns);
	}
	/* all the data was freed.  now free the list */
	g_slist_free(namespaces);
	free(classname);

	return CODB_RET_SUCCESS;
}

/** core to do indexing updates for lists of property changes
 */
static codb_ret
codb_indexing_core(codb_handle *h, oid_t oid, const char *classname,
    const char *namespace, GHashWrap *attribs)
{
	/* and update the indexing! */
	GSList *indexes = NULL;
	cce_scalar *oldval;
	int hashi, hashn;

	oldval = cce_scalar_new_undef();

	/* foreach prop in attribs */
	hashn = g_hashwrap_size(attribs);
	for (hashi = 0; hashi < hashn; hashi++) {
		odb_oid myoid;
		gpointer key, val;

		g_hashwrap_index(attribs, hashi, &key, &val);

		myoid.oid = oid;
		get_prop_old(h, &myoid, namespace, (char *)key, oldval);

		codb_classconf_get_indexes(h->classconf, classname, "",
		    (char *)key, &indexes);
		while (indexes) {
			codb_index *index = indexes->data;

			if (cce_scalar_isdefined(oldval)) {
				odb_indexing_update(h->txn,
				    codb_index_get_name(index), &myoid,
				    oldval->data, 0);
			}
			odb_indexing_update(h->txn,
			    codb_index_get_name(index), &myoid, val, 1);
			indexes = g_slist_next(indexes);
		}
	}
	cce_scalar_free(oldval);
	return CODB_RET_SUCCESS;
}

/* 
 * codb_names()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *  CODB_RET_UNKCLASS
 */
codb_ret
codb_names(codb_handle *h, const char *class, GSList ** namespaces)
{
	*namespaces = NULL;

	/* check classconf for the class */
	if (!codb_classconf_getclass(h->classconf, class, "")) {
		return CODB_RET_UNKCLASS;
	}

	*namespaces = codb_classconf_getnamespaces(h->classconf, class);

	return CODB_RET_SUCCESS;
}

/* 
 * codb_classlist()
 *
 * returns:
 *  CODB_RET_SUCCESS
 */
codb_ret
codb_classlist(codb_handle *h, GSList ** classes)
{
	*classes = codb_classconf_getclasses(h->classconf);

	return CODB_RET_SUCCESS;
}

/* 
 * codb_find() in codb_find.c
 */

codb_ret
codb_commit(codb_handle *h)
{
	codb_ret ret, ret2;
	struct timeval start;

	DPROFILE_START(PROF_CODB, &start, "codb_commit()");

	/* FIXME: filter out nosave properties */
	if (!codb_is_ro) {
		ret = odb_txn_commit(h->txn);
	} else {
		ret = CODB_RET_SUCCESS;
		DPRINTF(DBG_CODB, "CODB is read-only - skipping commit\n");
	}
	ret2 = odb_txn_flush(h->txn);

	DPRINTF(DBG_CODB, "CODB commited h(0x%lx)\n", (long)h);
	DPROFILE(PROF_CODB, start, "codb_commit() is done");

	return (ret == CODB_RET_SUCCESS ? ret2 : ret);
}

codb_ret
codb_flush(codb_handle *h)
{
	codb_ret ret;
	struct timeval start;

	DPROFILE_START(PROF_CODB, &start, "codb_flush()");

	DPRINTF(DBG_CODB, "CODB flushing h(0x%lx)\n", (long)h);

	ret = odb_txn_flush(h->txn);

	DPROFILE(PROF_CODB, start, "codb_flush() is done");
	return ret;
}

void
codb_dump_events(codb_handle *h)
{
	odb_txn txn;
	GSList *events, *ptr;
	int count;

	txn = h->txn;
	events = NULL;
	count = odb_txn_inspect_listevents(txn, &events);
	DPRINTF(DBG_CODB, "transaction contains %d events\n", count);
	ptr = events;
	while (ptr) {
		odb_event_dump((odb_event *) ptr->data, stderr);
		ptr = ptr->next;
	}
	DPRINTF(DBG_CODB, "\n");
}

codb_ret
codb_list_events(codb_handle *h, GSList ** eventlist)
{
	return odb_txn_inspect_codbevents(h->txn, eventlist);
}

void
codb_free_events(GSList ** eventlist)
{
	odb_txn_free_codbevents(eventlist);
}

int
codb_is_magic_prop(char *str)
{
	if (!strcmp(str, "CLASS")
	    || !strcmp(str, "CLASSVER")
	    || !strcmp(str, "OID") || !strcmp(str, "NAMESPACE")) {
		return 1;
	}

	return 0;
}

int
codb_init(const char *schemadir)
{
	int ret = 0;

	if (!classconf) {
		classconf = codb_classconf_init(schemadir);
		if (!classconf) {
			CCE_SYSLOG("error loading class configuration");
			ret = -1;
		}
	}

	return ret;
}

void
codb_uninit()
{
	if (classconf)
		codb_classconf_destroy(classconf);
}

int
codb_set_ro(int val)
{
	int old = codb_is_ro;

	codb_is_ro = val;
	return old;
}

int
codb_is_sysadmin(codb_handle *h)
{
	return rule_sysadmin(h, 0, NULL);
}

codb_ret
codb_getdefval(codb_handle *h, odb_oid *oid, const char *prop,
    cce_scalar **val)
{
	codb_property *sprop;
	const char *defval;
	property_t *p;

	cce_scalar_destroy(*val);
	p = property_from_str(prop);
	sprop = codb_class_get_property(h->classconf, 
	    codb_get_classname(h, oid->oid), p);
	property_destroy(p);
	defval = codb_property_get_def_val(sprop);
	*val = cce_scalar_new_from_str(defval);

	return CODB_RET_SUCCESS;
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
