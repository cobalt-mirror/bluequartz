/* $Id: odb_txn_lists.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include "odb_txn_internal.h"

/* odb_txn_listevent
 *
 * adds a list-modify event to the event list for an obj-property
 */
codb_ret
odb_txn_listevent (odb_txn txn, odb_oid * oid, char *prop, odb_event * event)
{
  char *objprop_str;
  gpointer orig_key;
  GSList *eventlist;

  DPRINTF(DBG_TXN, "txn(0x%lx) %ld.%s getting event:\n", 
    (long)txn,
    oid->oid, prop);
  odb_event_dump(event, stderr);

  /* get the current list */
  objprop_str = objprop_to_str (oid, prop);
  if (g_hash_table_lookup_extended (txn->lists, objprop_str, &orig_key,
                                    (gpointer *) & eventlist)) {
    /* already in table */
    free (objprop_str);          /* don't need this anymore */
    objprop_str = orig_key;      /* reuse existing key */
  } else {
    /* not already in table, we still need objprop_str */
    orig_key = objprop_str;      /* use new key */
    eventlist = NULL;            /* create a new list */
  }

  /* appent event to list of events */
  eventlist = g_slist_append (eventlist, event);

  /* update value in the hash table (g_slist_append may change things) */
  g_hash_table_insert (txn->lists, orig_key, eventlist);
  
  DPRINTF(DBG_TXN, "txn(0x%lx) %ld.%s event list:\n", 
    (long)txn,
    oid->oid, prop);
  odb_eventlist_dump(eventlist, stderr);

  return CODB_RET_SUCCESS;
}

/* odb_txn_listadd
 *
 * adds an add-to-list event to the event list for an obj-property
 */
codb_ret
odb_txn_listadd (odb_txn txn, odb_oid * oid, char *prop,
                 odb_oid * oidval, int before, odb_oid * other)
{
  odb_event *event;

  /* FIXME: make sure object exists */

  /* FIXME: make sure property exists and is a reflist */

  /* make sure the oid we're adding isn't already there */
  if (odb_txn_listhas (txn, oid, prop, oidval)) {
    /* FIXME: what to do?  Fail?  Add twice?  Reorder? */
  }

  /* add forward reference: */
  event = new_odb_event_listadd (oid, prop, oidval, before, other);
  odb_txn_listevent (txn, oid, prop, event);

  return CODB_RET_SUCCESS;
}

/* odb_txn_listrm
 *
 * adds a remove-from-list event to the event list for an obj-property
 */
codb_ret
odb_txn_listrm (odb_txn txn, odb_oid * oid, char *prop, odb_oid * oidval)
{
  odb_event *event;

  /* FIXME: make sure object exists */

  /* FIXME: make sure property exists and is a reflist */

  /* make sure the oid we're removing exists */
  if (!odb_txn_listhas (txn, oid, prop, oidval)) {
    return CODB_RET_ALREADY;
  }

  /* remove forward reference: */
  event = new_odb_event_listrm (oid, prop, oidval);
  odb_txn_listevent (txn, oid, prop, event);

  return CODB_RET_SUCCESS;
}

/* odb_txn_list
 *
 * returns the object list associated with an obj-listprop
 */
codb_ret
odb_txn_list (odb_txn txn, odb_oid * oid, char *prop, odb_oidlist * oidlist)
{
  codb_ret ret;

  /* FIXME: make sure object exists */

  /* FIXME: make sure property exists and is a reflist */

  /* read the real oidlist */
  if (txn->txn) {
    ret = odb_txn_list (txn->txn, oid, prop, oidlist);
  } else {
    ret = impl_listget (txn->impl, oid, prop, oidlist);
  }
  if (ret != CODB_RET_SUCCESS) {
    return ret;
  }

  /* apply diffs */
  {
    char *objprop_str;
    GSList *eventlist;
    odb_event *event;
    odb_event_listadd *e_listadd;
    odb_event_listrm *e_listrm;

    objprop_str = objprop_to_str (oid, prop);
    eventlist = (GSList *) g_hash_table_lookup (txn->lists, objprop_str);
    free (objprop_str);
    while (eventlist) {
      event = (odb_event *) eventlist->data;
      switch (odb_event_type_read (event)) {
      case ODB_EVENT_LISTADD:
        e_listadd = (odb_event_listadd *) event;
        odb_oidlist_add (oidlist,
                         &(e_listadd->oidval),
                         e_listadd->before, &(e_listadd->other));
        break;
      case ODB_EVENT_LISTRM:
        e_listrm = (odb_event_listrm *) event;
        odb_oidlist_rm (oidlist, &(e_listrm->oidval));
        break;
      case ODB_EVENT_NEW:
      case ODB_EVENT_SET:
      default:
        /* ignore */
        break;
      };
      eventlist = eventlist->next;
    }
  }

  return CODB_RET_SUCCESS;
}

/* odb_txn_listhas
 *
 * checks to see if object "other" is a member of the obj-listprop 
 */
int
odb_txn_listhas (odb_txn txn, odb_oid * oid, char *prop, odb_oid * other)
{
  odb_oidlist *olist;
  codb_ret ret;
  int has;

  olist = odb_oidlist_new ();
  ret = odb_txn_list (txn, oid, prop, olist);
  has = odb_oidlist_has (olist, other);
  odb_oidlist_destroy (olist);

  return has;
}

/* odb_txn_listlen
 *
 * counts the number of objects in an obj-listprop
 */
int
odb_txn_listlen (odb_txn txn, odb_oid * oid, char *prop)
{
  odb_oidlist *olist;
  codb_ret ret;
  int n;

  olist = odb_oidlist_new ();
  ret = odb_txn_list (txn, oid, prop, olist);
  n = 0;
  if (odb_oidlist_first (olist)) {
    n++;
    while (odb_oidlist_next (olist))
      n++;
  }
  return n;
}

/* odb_txn_setadd
 *
 * adds an add-to-list event to the event list for an obj-property, but
 * only if the object hasn't already been added.
 */
codb_ret
odb_txn_setadd (odb_txn txn, odb_oid * oid, char *prop, odb_oid * oidval)
{
  if (odb_txn_listhas (txn, oid, prop, oidval)) {
    return CODB_RET_SUCCESS;
  }

  return (odb_txn_listadd (txn, oid, prop, oidval, 0, NULL));
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
