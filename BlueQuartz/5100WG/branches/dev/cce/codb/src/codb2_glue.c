/*************************************************************************
 * $Id: codb2_glue.c 3 2003-07-17 15:19:15Z will $
 *************************************************************************
 * Glues the odb_txn block into the codb2 (CSCP-Lite) interface.
 * author: jmayer@cobalt.com
 *************************************************************************/
#include <cce_common.h>
#include <codb_debug.h>

/* implement this api: */
#include <codb.h>

/* implement in terms of: */
#include "codb_handle.h"
#include <odb_transaction.h>
#include <odb_txn_inspect.h>
#include <odb_txn_events.h>
#include <odb_helpers.h>
#include <codb_classconf.h>
#include <codb_security.h>
#include <ctype.h>
#include <string.h>

#include "compares.h"

/* hardcoded paths FIXME */
#define OBJECT_ID_FILE  CCEDBDIR "/codb.oids"

/* this is the global class configuration */
static codb_classconf *classconf = NULL;

/* global RO flag */
static int codb_is_ro = 0;

static codb_ret set_prop (codb_handle * h, odb_oid * oid, char *ns,
                          char *key, char *val);
static codb_ret codb_set_core (codb_handle * h, oid_t oid,
                               const char *namespace, GHashTable * attribs,
                               GHashTable * attriberrs);

/* constructor and destructor code */
codb_handle *
codb_handle_new (cce_conf * conf)
{
  codb_handle *h;

  h = malloc (sizeof (codb_handle));
  if (!h) {
    return NULL;
  }

  h->flags = 0;
  h->conf = conf;
  h->impl = NULL;
  h->txn = NULL;
  h->root = NULL;
  h->branch_count = 0;

  h->impl = impl_handle_new ();
  if (!h->impl) {
    free (h);
    return NULL;
  }

  h->txn = odb_txn_new (h->impl);
  if (!h->txn) {
    impl_handle_destroy (h->impl);
    free (h);
    return NULL;
  }

  /* get the global class info */
  h->classconf = classconf;

  h->cur_oid = 0;

  DPRINTF( DBG_CODB, "CODB created h(0x%lx)\n",
    (long)h);

  return (h);
}

codb_handle *
codb_handle_branch (codb_handle * h)
{
  codb_handle *h2;

  h2 = malloc (sizeof (codb_handle));
  if (!h2) {
    return NULL;
  }

  h2->flags = h->flags;
  h2->conf = h->conf;
  h2->classconf = h->classconf; /* booya! */
  h2->impl = NULL;
  h2->txn = NULL;
  h2->root = h;
  h2->branch_count = h->branch_count + 1; /* ya silly */

  h2->impl = impl_handle_new ();
  if (!h2->impl) {
    free (h2);
    return NULL;
  }

  h2->txn = odb_txn_new_meta (h2->impl, h->txn);
  if (!h2->txn) {
    impl_handle_destroy (h2->impl);
    free (h2);
    return NULL;
  }

  h2->cur_oid = h->cur_oid;

  DPRINTF( DBG_CODB, "CODB branched h2(0x%lx) off of h1(0x%lx)\n",
    (long)h2, (long)h);

  return (h2);
}

int
codb_handle_branch_level (codb_handle * h)
{
  return h->branch_count;
}

codb_handle *
codb_handle_rootref (codb_handle * h)
{
  if (h->root)
    return h->root;
  else
    return h;
}

void
codb_handle_destroy (codb_handle * h)
{
  odb_txn_destroy (h->txn);
  impl_handle_destroy (h->impl);
  free (h);                     /* free the handle itself */
  
  DPRINTF(DBG_CODB, "CODB destroyed h(0x%lx)\n", (long)h);
}

oid_t 
codb_handle_setoid (codb_handle * h, oid_t oid)
{
  // sets the oid that represents the current authenticated user.
  
  oid_t old;

  old = h->cur_oid;
  h->cur_oid = oid;

  return old;
}

oid_t
codb_handle_getoid(codb_handle * h)
{
	return h->cur_oid;
}

void
codb_handle_setflags (codb_handle * h, unsigned int flags)
{
  h->flags = flags;
}

void
codb_handle_addflags (codb_handle * h, unsigned int flags)
{
  h->flags |= flags;
}

void
codb_handle_rmflags (codb_handle * h, unsigned int flags)
{
  h->flags &= (~flags);
}

unsigned int
codb_handle_getflags (codb_handle * h)
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
codb_create (codb_handle * h, const char *class, GHashTable * attribs,
             GHashTable * attriberrs, oid_t * oid)
{
  codb_ret ret;
  odb_oid odboid;
  struct timeval start;

  DPROFILE_START(PROF_CODB, &start, "codb_create()");

  /* make sure the class requested exists, and verify data */
  ret = codb_classconf_validate (h->classconf, (char *) class, NULL,
                                 attribs, attriberrs);
  if (ret != CODB_RET_SUCCESS) {
    return ret;
  }

  /* make sure the user is allowed to do this */
  ret = codb_security_can_create (h, (char *) class);
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

  ret = odb_txn_createobj (h->txn, &odboid, (char *) class);
  if (ret != CODB_RET_SUCCESS) {
    odb_txn_oid_release(h->txn, odboid);
    return ret;
  }
  *oid = odboid.oid;            /* bleh */

  /* 
   * set all magic attributes - NAMESPACE is a read-only value
   */

  /* CLASS */
  ret = set_prop (h, &odboid, NULL, "CLASS", (char *) class);

  /* CLASSVER */
  if (ret == CODB_RET_SUCCESS) {
    codb_class *cc_class;
    char *ver;

    cc_class = codb_classconf_getclass (h->classconf, (char *) class, "");
    if (!cc_class) {
      ver = "";
    } else {
      ver = codb_class_get_version (cc_class);
    }

    ret = set_prop (h, &odboid, NULL, "CLASSVER", ver);
  }

  /* OID */
  if (ret == CODB_RET_SUCCESS) {
    char p[30];
    sprintf (p, "%ld", (long) odboid.oid);
    ret |= set_prop (h, &odboid, NULL, "OID", p);
  }

  if (ret == CODB_RET_SUCCESS) {
    ret = codb_set_core (h, *oid, "", attribs, attriberrs);
  }

  DPROFILE(PROF_CODB, start, "codb_create() is done");

  if (ret != CODB_RET_SUCCESS) {
    odb_txn_destroyobj (h->txn, &odboid);
    /* FIXME: I'd like to free the oid here, but I'm afraid of what
     * may happen if the same oid is re-used within the txn.  I'd rather
     * tolerate an oid leak, for now. */
    return ret;
  }

  return CODB_RET_SUCCESS;
}

/* helper fn to set properties */
static codb_ret
set_prop (codb_handle * h, odb_oid * oid, char *ns, char *key, char *val)
{
  GString *keybuf;
  cce_scalar *scbuf;
  int ret;

  /* JIC */
  if (!ns) {
    ns = "";
  }

  /* FIXME: a bit superflouous at this point, n'est ce pas ? */
  if (!isalpha (*key) && *key != '_') {
    /* must start with [A-Za-z_] */
    return CODB_RET_BADDATA;
  }

  /* build the key */
  keybuf = g_string_new (ns);
  g_string_append_c (keybuf, '.');
  g_string_append (keybuf, key);

  /* value */
  scbuf = cce_scalar_new_from_str (val);

  /* do it */
  ret = odb_txn_set (h->txn, oid, keybuf->str, scbuf);

  /* clean up */
  cce_scalar_destroy (scbuf);
  g_string_free (keybuf, 1);

  return ret;
}

/*
 * codb_destroy()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *    CODB_RET_UNKOBJ
 */
codb_ret 
codb_destroy (codb_handle * h, oid_t oid)
{
  codb_ret ret;
  odb_oid odboid;
  struct timeval start;
  
  DPROFILE_START(PROF_CODB, &start, "codb_destroy()");

  if (!codb_objexists (h, oid)) {
    return CODB_RET_UNKOBJ;
  }

  /* make sure the user is allowed to do this */
  ret = codb_security_can_destroy (h, oid);
  if (ret != CODB_RET_SUCCESS) {
    return ret;
  }

  odboid.oid = oid;

  ret = odb_txn_destroyobj (h->txn, &odboid);
  DPRINTF(DBG_CODB, "odb_txn_destroyobj ret=%d\n",ret);

  // tag an oid for eventual release back into the oid pool
  odb_txn_oid_release(h->txn, odboid);
  
  DPROFILE(PROF_CODB, start, "codb_destroy() is done");

  return ret;
}

int
codb_objexists (codb_handle * h, oid_t oid)
{
  odb_oid myoid;
  myoid.oid = oid;
  return odb_txn_objexists (h->txn, &myoid);
}

char *
codb_get_classname (codb_handle * h, oid_t oid)
{
  char *buf = NULL;
  odb_oid myoid;
  cce_scalar *sc;
  codb_ret ret;

  myoid.oid = oid;
  sc = cce_scalar_new_undef ();

  ret = odb_txn_get (h->txn, &myoid, ".CLASS", sc);

  if (ret == CODB_RET_SUCCESS) {
    if (cce_scalar_isdefined (sc)) {
      buf = strdup (sc->data);
    }
  } else {
    ret = odb_txn_get_old (h->txn, &myoid, ".CLASS", sc);
    if (ret == CODB_RET_SUCCESS) {
      if (cce_scalar_isdefined (sc)) {
        buf = strdup (sc->data);
      }
    } else {
      buf = NULL;
    }
  }
  cce_scalar_destroy (sc);
  return buf;
}

#define GFLAGS_OLD  (0x1)
#define GFLAGS_CHANGED  (0x2)
inline static codb_ret
odb_get_general (codb_handle * h, oid_t oid, const char *namespace,
                 GHashTable * attribs, int flags);

codb_ret
codb_get (codb_handle * h, oid_t oid, const char *namespace,
          GHashTable * attribs)
{
  return odb_get_general (h, oid, namespace, attribs, 0);
}

codb_ret
codb_get_old (codb_handle * h, oid_t oid, const char *namespace,
              GHashTable * attribs)
{
  return odb_get_general (h, oid, namespace, attribs, GFLAGS_OLD);
}

codb_ret
codb_get_changed (codb_handle * h, oid_t oid, const char *namespace,
                  GHashTable * attribs)
{
  return odb_get_general (h, oid, namespace, attribs, GFLAGS_CHANGED);
}

codb_ret
odb_get_general (codb_handle * h, oid_t oid, const char *namespace,
                 GHashTable * attribs, int flags)
{
  odb_oid myoid;
  GSList *scalars, *lists, *cursor;
  GString *nsbuf;
  size_t n;
  codb_ret ret;
  char *class;

  myoid.oid = oid;

  /* check for the existence of the object */
  if (flags & GFLAGS_OLD) {
    if (!odb_txn_objexists_old (h->txn, &myoid)) {
      return CODB_RET_UNKOBJ;
    }
  } else {
    if (!odb_txn_objexists (h->txn, &myoid)) {
      return CODB_RET_UNKOBJ;
    }
  }

  /* make sure the namespace is valid */
  class = codb_get_classname (h, oid);
  if (!class) {
    return CODB_RET_UNKCLASS;
  }

  /* verify it through classconf */
  ret = codb_classconf_validate (h->classconf, class, (char *) namespace,
                                 NULL, NULL);
  if (ret != CODB_RET_SUCCESS) {
    free (class);
    return ret;
  }

  if (!namespace)
    namespace = "";

  nsbuf = g_string_new (namespace);
  g_string_append_c (nsbuf, '.');
  n = strlen (nsbuf->str);

  scalars = NULL;
  lists = NULL;

  if (flags & GFLAGS_OLD)
    ret = odb_txn_get_properties_old (h->txn, &myoid, &scalars, &lists);
  else
    ret = odb_txn_get_properties (h->txn, &myoid, &scalars, &lists);

  for (cursor = scalars; cursor && (ret == CODB_RET_SUCCESS);
       cursor = g_slist_next (cursor)) {
    if (strncmp (nsbuf->str, cursor->data, n) == 0) {
      char *key, *value;
      cce_scalar *sc;

      if (flags & GFLAGS_CHANGED) {
        /* skip this attribute unless it changes in this txn */
        if (!odb_txn_is_changed (h->txn, &myoid, cursor->data))
          continue;
      }

      sc = cce_scalar_new_undef ();
      if (flags & GFLAGS_OLD) {
        ret = odb_txn_get_old (h->txn, &myoid, cursor->data, sc);
      } else {
        ret = odb_txn_get (h->txn, &myoid, cursor->data, sc);
      }
      if (cce_scalar_isdefined (sc)) {
        key = strdup (cursor->data + n);  /* trim off namespace */
        value = strdup (sc->data);
        g_hash_table_insert (attribs, key, value);
      }
      cce_scalar_destroy (sc);
    } else {
      // DPRINTF(DBG_CODB, "skipped %s\n", (char *)cursor->data);
    }
  }

  odb_txn_proplist_free (&scalars);
  odb_txn_proplist_free (&lists);
  g_string_free (nsbuf, 1);

  /* do magic, load any default values */
  if (!(flags & GFLAGS_CHANGED)) {
    GHashIter *it;
    GHashTable *props;
    codb_class *cl;
    gpointer k, v;

    /* have to have a magic NAMESPACE property - spec says so */
    g_hash_table_insert (attribs, strdup ("NAMESPACE"),
                         strdup ((char *) namespace));

    /* have to have a CLASSVER property */
    cl = codb_classconf_getclass (h->classconf, (char *) class, "");
    if (!cl) {
      g_hash_table_insert (attribs, strdup ("CLASSVER"), strdup (""));
    } else {
      char *ver = codb_class_get_version (cl);
      g_hash_table_insert (attribs, strdup ("CLASSVER"), strdup (ver));
    }

    /* now do default values */
    cl = codb_classconf_getclass (h->classconf, class, (char *) namespace);
    props = codb_class_getproperties (cl);

    /* for each property in the class... */
    it = g_hash_iter_new (props);
    g_hash_iter_first (it, &k, &v);
    while (k) {
      codb_property *p = (codb_property *) v;

      /* if property is not in attribs -- add it */
      if (!g_hash_table_lookup (attribs, k)) {
        g_hash_table_insert (attribs, strdup (k),
                             strdup (codb_property_get_def_val (p)));
      }
      g_hash_iter_next (it, &k, &v);
    }

    g_hash_iter_destroy (it);

    /* FIXME: filter bad data, destroy the bad properties */
  }

  /* filter out properties the client is not allowed to read */
  codb_security_read_filter (h, oid, class, (char *) namespace, attribs);

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
codb_set (codb_handle * h, oid_t oid, const char *namespace, 
	GHashTable *attribs, GHashTable *data_errs, GHashTable *perm_errs)
{
  codb_ret ret;
  char *classname;
  
  struct timeval start;
  DPROFILE_START(PROF_CODB, &start, "codb_set()");

  /* verify the object exists */
  if (!codb_objexists (h, oid)) {
    return CODB_RET_UNKOBJ;
  }

  if (!attribs) {
    /* well, they asked us to set nothing... */
    return CODB_RET_SUCCESS;
  }

  if (!namespace)
    namespace = "";

  /* get the class of the oid */
  classname = codb_get_classname (h, oid);
  if (!classname) {
    return CODB_RET_UNKCLASS;
  }

  /* verify it through classconf */
  ret = codb_classconf_validate (h->classconf, classname, (char *) namespace,
                                 attribs, data_errs);
  if (ret != CODB_RET_SUCCESS) {
    free (classname);
    return ret;
  }

  /* security check it */
  ret = codb_security_write_filter (h, oid, classname, (char *) namespace,
                                    attribs, perm_errs);

  free (classname);

  if (ret != CODB_RET_SUCCESS) {
    return ret;
  }

  /* 
   * everything seems OK - let's go
   */

  ret = codb_set_core (h, oid, namespace, attribs, data_errs);
  
  DPROFILE(PROF_CODB, start, "codb_set() is done");
  
  return ret;
}


static codb_ret
codb_set_core (codb_handle * h, oid_t oid, const char *namespace,
               GHashTable * attribs, GHashTable * attriberrs)
{
  codb_ret ret;
  GHashIter *it;
  gpointer key, val;
  odb_oid myoid;

  myoid.oid = oid;

  it = g_hash_iter_new (attribs);
  for (g_hash_iter_first (it, &key, &val);
       key; g_hash_iter_next (it, &key, &val)) {
    /* can't set CLASS, OID, or NAMESPACE */
    if (codb_is_magic_prop (key)) {
      /* silently ignore */
      continue;
    }

    ret = set_prop (h, &myoid, (char *) namespace, key, val);
    if (ret != CODB_RET_SUCCESS) {
      g_hash_iter_destroy (it);
      return ret;
    }
  }

  g_hash_iter_destroy (it);

  return CODB_RET_SUCCESS;
}

/*
 * codb_names()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *  CODB_RET_UNKCLASS
 */
codb_ret codb_names (codb_handle * h, const char *class, GSList ** namespaces)
{
  *namespaces = NULL;

  /* check classconf for the class */
  if (!codb_classconf_getclass (h->classconf, (char *) class, "")) {
    return CODB_RET_UNKCLASS;
  }

  *namespaces = codb_classconf_getnamespaces (h->classconf, (char *) class);

  return CODB_RET_SUCCESS;
}

/*
 * codb_classlist()
 *
 * returns:
 *  CODB_RET_SUCCESS
 */
codb_ret codb_classlist (codb_handle * h, GSList ** classes)
{
  *classes = codb_classconf_getclasses (h->classconf);

  return CODB_RET_SUCCESS;
}

static int 
hex2int(char c)
{
  if (c >= '0' && c <= '9') { return (int)(c-'0'); }
  if (c >= 'a' && c <= 'f') { return (int)(10+c-'a'); }
  if (c >= 'A' && c <= 'F') { return (int)(10+c-'A'); }
  return -1;
}

/*
 * arraycmp: looks for a match of a string in a cce array.
 * returns: 0 if match, !0 if no match.
 */
static int
arraycmp(char *str, char *criteria)
{
  int found;
  GString *buffer;
  found = 0;
  buffer = g_string_new("");
  
  if (*str == '&') str++; 
  
  while ((!found) && (*str)) {
    if ( (*str == '%')
      && (hex2int(*(str+1)) >= 0)
      && (hex2int(*(str+2)) >= 0) )
    {
      // is an escaped special character:
      int i;
      i = 16*hex2int(*(str+1)) + hex2int(*(str+2));
      g_string_append_c(buffer, (gchar)i);
      str+=3;
    } else if (*str == '&') {
      // separates the men from the boys:
      if (strcmp(buffer->str, criteria) == 0)
      	found = 1;
      g_string_assign(buffer, "");
      str+=1;
    } else {
      // is an ordinary character:
      g_string_append_c(buffer, (gchar)(*str));
      str+=1;
    }
  }
  if (*(buffer->str) != '\0') {
    if (strcmp(buffer->str, criteria) == 0)
      found = 1;
  }
  g_string_free(buffer,1);
  
  return (found) ? 0 : -1;  
}

/*
 * codb_find()
 *
 * returns:
 *  CODB_RET_SUCCESS
 *  CODB_RET_UNKCLASS
 */
codb_ret
codb_find(codb_handle *h, const char *class, const GHashTable *criteria,
           GSList **oids, const char *sort_by, int sorttype)
{
  codb_ret ret;
  struct timeval start;
  DPROFILE_START(PROF_CODB, &start, "codb_find()");
  ret = codb_find_n(h, class, criteria, 0, oids, sort_by, sorttype);
  DPROFILE(PROF_CODB, start, "codb_find() is done");
  return ret;
}

/////////////////////////////////////////////////////////////////////////
// codb_find_n helper functions:
/////////////////////////////////////////////////////////////////////////

static GHashTable *sorthash;

// return -1 if a < b, 0 if a = b, +1 if a > b
gint oid_sort_compare_func (gconstpointer a, gconstpointer b)
{
  char *value_a, *value_b;
  value_a = (char *)g_hash_table_lookup(sorthash, a);
  value_b = (char *)g_hash_table_lookup(sorthash, b);
  
  if (!value_a || !value_b) { return 0; }
  
  return strcmp(value_a, value_b);
}

// return -1 if a < b, 0 if a = b, +1 if a > b
gint oid_sort_numeric_compare_func (gconstpointer a, gconstpointer b)
{
  char *value_a, *value_b;
  value_a = (char *)g_hash_table_lookup(sorthash, a);
  value_b = (char *)g_hash_table_lookup(sorthash, b);
  
  if (!value_a || !value_b) { return 0; }

  return compare_float(value_a, value_b);  
}

gboolean oid_remove_func(gpointer key, gpointer value, gpointer user_data)
{
  free(value);
  return TRUE;
}

// return TRUE if match, FALSE otherwise
gint codb_match_against_object(codb_handle *h, 
  const GHashTable *criteria, const char *class, odb_oid *odboid)
{
  GHashIter *it;
  gpointer key, val;
  int flag;

  DPRINTF(DBG_CODB, "codb_match_against_object: %ld(%s)\n",
    (long)(odboid->oid), (char*)class);
  
  flag = 1;
  it = g_hash_iter_new((GHashTable *)criteria);
  g_hash_iter_first(it, &key, &val);
  while (key && flag) {
    char *namespace;
    char *propname;
    char *propval;
    GString *expanded_key;
    char *keybuf;
    cce_scalar *sc;
    int is_array_prop;
    
    sc = cce_scalar_new_undef();

    /* working copy */
    keybuf = strdup ((char *) key);

    /* pre-process the key */
    if ((propname = strchr (keybuf, '.'))) {
      *propname = '\0';
      namespace = keybuf;
      propname++;
    } else {
      namespace = "";
      propname = keybuf;
    }

    expanded_key = g_string_new (namespace);
    expanded_key = g_string_append_c (expanded_key, '.');
    expanded_key = g_string_append (expanded_key, propname);

    /* try to get the object attribute */
    odb_txn_get (h->txn, odboid, expanded_key->str, sc);
    DPRINTF(DBG_CODB, "-- key=%s val=%s\n", 
      expanded_key->str, (char*)sc->data);

    {
      codb_class *cl;
      GHashTable *props;
      codb_property *p;
      codb_ret ret;

      cl = codb_classconf_getclass (h->classconf,
                                    (char *) class, (char *) namespace);
      props = codb_class_getproperties (cl);
      p = (codb_property *) g_hash_table_lookup (props, propname);

      /* check that we can read it */
      ret = codb_security_can_read_prop (h, odboid->oid, p);
      if (ret != CODB_RET_SUCCESS) {
        propval = NULL;
      } else if (sc->data) {
        /* we can read it, and it exists */
        propval = (char *) sc->data;
      } else {
        /* try a default value */
        propval = codb_property_get_def_val (p);
      }
      is_array_prop = codb_property_get_array(p);
    }

      /* FIXME: ARRAY: deal with array properties */
    /* compare the object attribute with the criteria */
    DPRINTF(DBG_CODB, "    comparing: %ld.%s \"%s\" =? \"%s\": ",
             odboid->oid, expanded_key->str, propval, (char *) val);

    if (!propval) flag = 0;
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
    if (expanded_key) g_string_free (expanded_key, 1);
    free (keybuf);
    cce_scalar_destroy(sc);

    /* next attrib */
    g_hash_iter_next (it, &key, &val);
  } // end of for loop

  g_hash_iter_destroy(it);
  
  return (flag);
}

/////////////////////////////////////////////////////////////////////////
// codb_find_n
//
// arguements:
//   h - codb object
//   class - name of class to search within
//   criteria - ghash of tests (key is attribute to test, value is value
//      to compare against).
//   goalnum - stop searching after finding this many objects (0 for
//      unlimited).
//   oids - GSList to store oid_t object id's in.
//   sort_by - attribute to sort objects by (NULL if no sorted is needed)
//   sorttype - type of sort to perform
//    	0 == alphanumeric sort
//    	1 == numeric sort
/////////////////////////////////////////////////////////////////////////
codb_ret
codb_find_n(codb_handle *h, const char *class, const GHashTable *criteria,
           int goalnum, GSList **oids, const char *sort_by, int sorttype)
{
  codb_ret ret;
  odb_oidlist *oidlist;
  odb_oid *odboid;
  codb_class *cl;
  
  GHashTable *found_object_hash;
  GSList *found_object_list;
  int nfound;

  GString *expanded_sort_by;
  char *default_sort_value;
  GCompareFunc compare_func;

  /* check classconf for the class */
  if (!codb_classconf_getclass (h->classconf, (char *) class, "")) {
    return CODB_RET_UNKCLASS;
  }

  DPRINTF(DBG_CODB, "Finding a %s:\n", class);

  /* flag the oidlist as NULL, in case it wasn't initialized */
  *oids = NULL;

  /* check classconf for the class */
  if (!(cl = codb_classconf_getclass (h->classconf, (char *) class, ""))) {
    return CODB_RET_UNKCLASS;
  }

  /* get list of objects of this class */
  oidlist = odb_oidlist_new ();
  ret = odb_txn_list_instances (h->txn, (char *) class, oidlist);
  
  /* expand the sort_by key */
  default_sort_value = "";
  if (sort_by) {
    char *sort_propname, *sort_namespace;
    codb_class *sort_cl;
    GHashTable *sort_props;
    codb_property *sort_p;
    char *sort_by_buf;
    
    sort_by_buf = strdup(sort_by);
    if ((sort_propname = strchr (sort_by_buf, '.'))) {
        *sort_propname = '\0';
        sort_namespace = sort_by_buf;
        sort_propname++;
    } else {
        sort_namespace = "";
        sort_propname = sort_by_buf;
    }

    expanded_sort_by = g_string_new (sort_namespace);
    expanded_sort_by = g_string_append_c (expanded_sort_by, '.');
    expanded_sort_by = g_string_append (expanded_sort_by, sort_propname);
    
    sort_cl = codb_classconf_getclass(h->classconf, (char *)class, 
      sort_namespace);
    if (sort_cl) {
      sort_props = codb_class_getproperties(sort_cl);
      sort_p = (codb_property *) g_hash_table_lookup(sort_props, sort_propname);
      default_sort_value = codb_property_get_def_val(sort_p);
    }
    free (sort_by_buf);
  } else {
    expanded_sort_by = NULL;
  }
  if (!default_sort_value) {
    // DPRINTF(DBG_CODB, "sorting by default val for %s\n", (char *)class);
    default_sort_value="";
  }
  
  if (sorttype == 0) {
    DPRINTF(DBG_CODB, "Sorting %s alphabetically by %s (%d).\n",
      (char *)class, (char *)sort_by, sorttype);
    compare_func = oid_sort_compare_func;
  } else {
    DPRINTF(DBG_CODB, "Sorting %s numerically by %s (%d).\n",
      (char *)class, (char *)sort_by, sorttype);
    compare_func = oid_sort_numeric_compare_func;
  }
    
  /* initialize hash table: key is an odb_oid, value is the sort key */
  found_object_hash = g_hash_table_new ( NULL, NULL );
  found_object_list = NULL;
  nfound = 0;
  sorthash = found_object_hash;

  /* for every object of this class: check if it matches criteria */
  for (odboid = odb_oidlist_first (oidlist); 
       odboid && (nfound < goalnum || !goalnum);
       odboid = odb_oidlist_next (oidlist)) 
  {
    if (!criteria || codb_match_against_object(h, criteria, class, odboid)) {
      char *sortVal = NULL;
      // load the sort value:
      if (expanded_sort_by) {
      	cce_scalar *sc;
	sc = cce_scalar_new_undef();
	/* load the property: */
      	odb_txn_get (h->txn, odboid, expanded_sort_by->str, sc);
	if (sc->data) {
  	  sortVal = strdup(sc->data);
	}
	cce_scalar_destroy(sc);
      }
      // else, fall back to the default value from schema:
      if (!sortVal) {
      	sortVal = strdup(default_sort_value);
      }
      // insert the entries into the results hash:
      g_hash_table_insert(found_object_hash, odboid, sortVal);
      found_object_list = g_slist_insert_sorted(found_object_list, 
    	odboid, compare_func);
      nfound++;
    }
  } // end of for loop
  
  // copy found_object_list into oids list
  {
    GSList *cursor;
    cursor = found_object_list;
    while (cursor) {
      odb_oid *ooid;
      oid_t *oid_copy;
      ooid = (odb_oid *)cursor->data;
      oid_copy = malloc(sizeof(oid_t));
      *oid_copy = (oid_t)(ooid->oid);
      *oids = g_slist_append(*oids, oid_copy);
      cursor = g_slist_next(cursor);
    }
  }
  
  // free found_object_hash
  g_hash_table_foreach_remove(found_object_hash, oid_remove_func, NULL);
  g_hash_table_destroy(found_object_hash);
  
  // free found_object_list
  g_slist_free(found_object_list);
  // (no need to free list data, that's part of the oidlist)
  
  // cleanup everything else
  odb_oidlist_destroy(oidlist);
  if (expanded_sort_by) g_string_free(expanded_sort_by, 1);
  
  // return success
  return CODB_RET_SUCCESS;
}
  
#if 0
codb_ret
old_codb_find_n(codb_handle *h, const char *class, const GHashTable *criteria,
           int goalnum, GSList **oids)
{
  GHashIter *it;
  odb_oidlist *oidlist;
  odb_oid *odboid;
  oid_t *ext_oid;
  codb_ret ret;
  cce_scalar *sc;
  gpointer key, val;
  int flag;
  int is_array_prop;
  int nfound = 0;

  /* check classconf for the class */
  if (!codb_classconf_getclass (h->classconf, (char *) class, "")) {
    return CODB_RET_UNKCLASS;
  }

  DPRINTF(DBG_CODB, "Finding a %s:\n", class);

  /* flag the oidlist as NULL, in case it wasn't initialized */
  *oids = NULL;

  oidlist = odb_oidlist_new ();
  ret = odb_txn_list_instances (h->txn, (char *) class, oidlist);

  /* if criteria == NULL, assume all matches */
  if (!criteria) {
    for (odboid = odb_oidlist_first (oidlist); odboid;
         odboid = odb_oidlist_next (oidlist)) {
      ext_oid = malloc (sizeof (oid_t));
      *ext_oid = odboid->oid;
      *oids = g_slist_append (*oids, ext_oid);
    }
    odb_oidlist_destroy (oidlist);
    return CODB_RET_SUCCESS;
  }

  sc = cce_scalar_new_undef ();
  it = g_hash_iter_new ((GHashTable *) criteria);

  /* for every object of this class: check if it matches criteria */
  for (odboid = odb_oidlist_first (oidlist); 
       odboid && (nfound < goalnum || !goalnum);
       odboid = odb_oidlist_next (oidlist)) {
    flag = 1;

    DPRINTF(DBG_CODB, "-- find testing oid %ld\n", (long)odboid->oid);

    /* for each criteria in the criteria hash */
    g_hash_iter_first (it, &key, &val);
    while (key && flag) {
      char *namespace;
      char *propname;
      char *propval;
      GString *expanded_key;
      char *keybuf;

      /* working copy */
      keybuf = strdup ((char *) key);

      /* pre-process the key */
      if ((propname = strchr (keybuf, '.'))) {
        *propname = '\0';
        namespace = keybuf;
        propname++;
      } else {
        namespace = "";
        propname = keybuf;
      }

      expanded_key = g_string_new (namespace);
      expanded_key = g_string_append_c (expanded_key, '.');
      expanded_key = g_string_append (expanded_key, propname);

      /* try to get the object attribute */
      odb_txn_get (h->txn, odboid, expanded_key->str, sc);
      DPRINTF(DBG_CODB, "-- key=%s val=%s\n", 
	expanded_key->str, (char*)sc->data);

      {
        codb_class *cl;
        GHashTable *props;
        codb_property *p;
        codb_ret ret;

        cl = codb_classconf_getclass (h->classconf,
                                      (char *) class, (char *) namespace);
        props = codb_class_getproperties (cl);
        p = (codb_property *) g_hash_table_lookup (props, propname);

        /* check that we can read it */
        ret = codb_security_can_read_prop (h, odboid->oid, p);
        if (ret != CODB_RET_SUCCESS) {
          propval = NULL;
        } else if (sc->data) {
          /* we can read it, and it exists */
          propval = (char *) sc->data;
        } else {
          /* try a default value */
          propval = codb_property_get_def_val (p);
        }
	is_array_prop = codb_property_get_array(p);
      }

	/* FIXME: ARRAY: deal with array properties */
      /* compare the object attribute with the criteria */
      DPRINTF(DBG_CODB, "    comparing: %ld.%s \"%s\" =? \"%s\": ",
               odboid->oid, expanded_key->str, propval, (char *) val);

      if (!propval) flag = 0;
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
      g_string_free (expanded_key, 1);
      free (keybuf);

      /* next attrib */
      g_hash_iter_next (it, &key, &val);
    }

    if (flag) {
      /* ah, a match!  enlist the object */
      ext_oid = malloc (sizeof (oid_t));
      *ext_oid = odboid->oid;
      *oids = g_slist_append (*oids, ext_oid);
      nfound++;
    }
  }

  /* cleanup */
  g_hash_iter_destroy (it);
  cce_scalar_destroy (sc);
  odb_oidlist_destroy (oidlist);

  return CODB_RET_SUCCESS;
}
#endif

codb_ret 
codb_commit(codb_handle * h)
{
  codb_ret ret, ret2;
  struct timeval start;
  DPROFILE_START(PROF_CODB, &start, "codb_commit()");
  
  /* FIXME: filter out nosave properties */
  if (!codb_is_ro) {
  	ret = odb_txn_commit (h->txn);
  } else {
	ret = CODB_RET_SUCCESS;
  	DPRINTF(DBG_CODB, "CODB is read-only - skipping commit\n");
  }
  ret2 = odb_txn_flush (h->txn);
  
  DPRINTF(DBG_CODB, "CODB commited h(0x%lx)\n", (long)h);
  DPROFILE(PROF_CODB, start, "codb_commit() is done");

  return (ret == CODB_RET_SUCCESS ? ret2 : ret);
}

codb_ret 
codb_flush (codb_handle * h)
{
  codb_ret ret;
  struct timeval start;
  DPROFILE_START(PROF_CODB, &start, "codb_flush()");
  
  DPRINTF(DBG_CODB, "CODB flushing h(0x%lx)\n", (long)h);

  ret = odb_txn_flush (h->txn);
  
  DPROFILE(PROF_CODB, start, "codb_flush() is done");
  return ret;
}

void
codb_dump_events (codb_handle * h)
{
  odb_txn txn;
  GSList *events, *ptr;
  int count;
  txn = h->txn;
  events = NULL;
  count = odb_txn_inspect_listevents (txn, &events);
  DPRINTF(DBG_CODB, "transaction contains %d events\n", count);
  ptr = events;
  while (ptr) {
    odb_event_dump ((odb_event *) ptr->data, stderr);
    ptr = ptr->next;
  }
  DPRINTF(DBG_CODB, "\n");
}

codb_ret codb_list_events (codb_handle * h, GSList ** eventlist)
{
  return odb_txn_inspect_codbevents (h->txn, eventlist);
}

void
codb_free_events (GSList ** eventlist)
{
  odb_txn_free_codbevents (eventlist);
}

int
codb_is_magic_prop (char *str)
{
  if (!strcmp (str, "CLASS")
      || !strcmp (str, "CLASSVER")
      || !strcmp (str, "OID")
      || !strcmp (str, "NAMESPACE")) {
    return 1;
  }

  return 0;
}

int
codb_init(void)
{
	int ret = 0;

	if (!classconf) {
		classconf = codb_classconf_init();
		if (!classconf) {
			CCE_SYSLOG("error loading class configuration\n");
			ret = -1;
		}
	}

	return ret;
}

int
codb_set_ro(int val)
{
	int old = codb_is_ro;
	codb_is_ro = val;
	return old;
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
