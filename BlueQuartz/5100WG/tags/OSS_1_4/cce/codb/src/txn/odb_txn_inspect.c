/* $Id: odb_txn_inspect.c 3 2003-07-17 15:19:15Z will $
 * odb_txn_inspect.c
 *
 * fns associated with inspecting the set of events that
 * comprises the transaction.
 */

#include "odb_txn_internal.h"

/***************************************************************************8
 * odb_txn_inspect fns below
 ****************************************************************************/

void dbg_dump_events (GSList *);

static void
gfunc_count_links (gpointer data, gpointer user_data)
{
  int *iP = user_data;
  (*iP)++;
}

static void
ghfunc_push_create_event (gpointer key, gpointer value, gpointer user_data)
{
  char *objstr, *class;
  GSList **eventsP;
  odb_oid oid;

  objstr = key;
  class = value;
  eventsP = user_data;
  str_to_oid (objstr, &oid);

  *eventsP = g_slist_append (*eventsP, new_odb_event_new (&oid, class));
}

static void
ghfunc_push_set_event (gpointer key, gpointer value, gpointer user_data)
{
  char *objprop_str;
  cce_scalar *val;
  GSList **eventsP;
  char *prop;
  odb_oid oid;

  objprop_str = key;
  val = value;
  eventsP = user_data;
  str_to_objprop (objprop_str, &oid, &prop);

  if (strcmp (prop, PROPNAME_LISTS) != 0
      && strcmp (prop, PROPNAME_SCALARS) != 0) {
    *eventsP = g_slist_append (*eventsP, new_odb_event_set (&oid, prop, val));
  }
}

static void
ghfunc_push_list_event (gpointer key, gpointer value, gpointer user_data)
{
  char *objprop_str;
  GSList *list;
  GSList **eventsP;
  char *prop;
  odb_oid oid;

  objprop_str = key;
  list = value;
  eventsP = user_data;
  str_to_objprop (objprop_str, &oid, &prop);

/*
 *   dbg_dump_events(*eventsP);
 *   dbg_dump_events(list);
 */

  while (list) {
    odb_event *copy = odb_event_dup (list->data);
    *eventsP = g_slist_append (*eventsP, copy);
    list = list->next;
    /* dbg_dump_events(*eventsP); */
  }
}

int
odb_txn_inspect_listevents (odb_txn txn, GSList ** eventsP)
{
  int num_of_events;


  /* 1. create list out of remaining events */
  g_hash_table_foreach (txn->objects, ghfunc_push_create_event, eventsP);
  g_hash_table_foreach (txn->scalars, ghfunc_push_set_event, eventsP);
  g_hash_table_foreach (txn->lists, ghfunc_push_list_event, eventsP);

  /* 2. count events */
  num_of_events = 0;
  g_slist_foreach (*eventsP, gfunc_count_links, &num_of_events);

  return num_of_events;
}

void
odb_txn_inspect_freelist (GSList ** eventsP)
{
  while (*eventsP) {
    odb_event_destroy ((odb_event *) ((*eventsP)->data));
    *eventsP = g_slist_remove (*eventsP, (*eventsP)->data);
  }
}

/************************ debug stuff *****************/

#include <stdio.h>

void
dbg_dump_events (GSList * events)
{
#ifdef DEBUG_TXN
  GSList *ptr;
  fprintf (stderr, "\ntransaction:\n");
  ptr = events;
  while (ptr) {
    if (ptr->data)
      odb_event_dump ((odb_event *) ptr->data, stderr);
    else
      fprintf (stderr, "*null event*\n");
    ptr = ptr->next;
  }
#endif
}

#ifdef DEBUG_TXN
void
dbg_dump_hash_elem (gpointer key, gpointer val, gpointer data)
{
  fprintf(stderr,"\t\"%s\" => \"%s\"\n",
    (char *)key, (char *)val);
}
#endif

void
dbg_dump_hash (char *label, GHashTable *hash)
{
#ifdef DEBUG_TXN
  fprintf(stderr,"\n%s hash:\n", label);
  g_hash_table_foreach(hash, dbg_dump_hash_elem, NULL);
  fprintf(stderr,"\n");
#endif
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
