/* $Id: odb_txn_new_inspect.c 3 2003-07-17 15:19:15Z will $
 * odb_txn_new_inspect.c
 *
 * Well, I'm transitioning from the old event system to the new.  However,
 * some of my regression tests still rely on the old event system to
 * fully test the listadd/listrm functionality.  So, I've decided to
 * leave the old interface alone (for generating old events), and add
 * a separate interface for generating new-style events (codb_event.h).
 *
 * This is the interface that returns lists of codb_event objects.
 */

#include "odb_txn_internal.h"

/***************************************************************************8
 * odb_txn_inspect fns below
 ****************************************************************************/

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

  if (!oid.oid) return;
  
  *eventsP = g_slist_append (*eventsP, codb_event_new(CREATE, oid.oid, class));
}

static void
ghfunc_push_destroy_event (gpointer key, gpointer value, gpointer user_data)
{
  char *objstr, *class;
  GSList **eventsP;
  odb_oid oid;

  objstr = key;
  class = value;
  eventsP = user_data;
  str_to_oid (objstr, &oid);

  if (!oid.oid) return;
  
  *eventsP = g_slist_append (*eventsP, codb_event_new(DESTROY, oid.oid, class));
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

  if (!oid.oid) return;
  if (strcmp(prop, PROPNAME_LISTS)==0) return;
  if (strcmp(prop, PROPNAME_SCALARS)==0) return;

  *eventsP = g_slist_append (*eventsP, codb_event_new(MODIFY, oid.oid, prop));
}

int
odb_txn_inspect_codbevents (odb_txn txn, GSList ** eventsP)
{
  int num_of_events;
  
  *eventsP = NULL; /* gee, hope the user didn't allocate something here */
  
  /* generate events */
  g_hash_table_foreach (txn->objects, ghfunc_push_create_event, eventsP);
  g_hash_table_foreach (txn->scalars, ghfunc_push_set_event, eventsP);
  g_hash_table_foreach (txn->destroyed, ghfunc_push_destroy_event, eventsP);

  /* count events */
  num_of_events = 0;
  g_slist_foreach (*eventsP, gfunc_count_links, &num_of_events);

  return num_of_events;
}

void
odb_txn_free_codbevents (GSList ** eventsP)
{
  while (*eventsP) {
    codb_event_destroy((codb_event*)((*eventsP)->data));
    *eventsP = g_slist_remove (*eventsP, (*eventsP)->data);
  }
  g_slist_free(*eventsP);
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
