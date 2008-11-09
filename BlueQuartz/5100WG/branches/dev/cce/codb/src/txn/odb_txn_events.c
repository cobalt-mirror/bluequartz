/* $Id: odb_txn_events.c 3 2003-07-17 15:19:15Z will $
 *
 * implements functions declared in odb_txn_events.h
 */

#include "odb_txn_internal.h"
#include <odb_txn_events.h>
#include <stdlib.h>
#include <string.h>

odb_event *
new_odb_event_new (odb_oid * oid, char *class)
{
  odb_event_new *eP;
  eP = (odb_event_new *) malloc (sizeof (odb_event_new));
  if (eP) {
    /* initialize object */
    eP->type = ODB_EVENT_NEW;
    eP->oid.oid = oid->oid;
    eP->class = strdup (class);
  }
  return ((odb_event *) eP);
}

void
free_odb_event_new (odb_event * eP)
{
  odb_event_new *realP = (odb_event_new *) eP;
  free (realP->class);
  free (realP);
}

odb_event *
new_odb_event_destroyed (odb_oid * oid)
{
  odb_event_new *eP;
  eP = (odb_event_new *) malloc (sizeof (odb_event_destroyed));
  if (eP) {
    /* initialize object */
    eP->type = ODB_EVENT_DESTROYED;
    eP->oid.oid = oid->oid;
  }
  return ((odb_event *) eP);
}

void
free_odb_event_destroyed (odb_event * eP)
{
  odb_event_destroyed *realP = (odb_event_destroyed *) eP;
  free (realP);
}

odb_event *
new_odb_event_set (odb_oid * oid, char *prop, cce_scalar * val)
{
  odb_event_set *eP;
  eP = (odb_event_set *) malloc (sizeof (odb_event_set));
  if (eP) {
    /* initialize object */
    eP->type = ODB_EVENT_SET;
    eP->oid.oid = oid->oid;
    eP->prop = strdup (prop);
    eP->val = cce_scalar_dup (val);

    /* check object integrity */
    if (!eP->prop || !eP->val) {
      free_odb_event_set ((odb_event *) eP);
      eP = NULL;
    }
  }
  return ((odb_event *) eP);
}

void
free_odb_event_set (odb_event * eP)
{
  odb_event_set *realP = (odb_event_set *) eP;
  if (realP->val)
    cce_scalar_destroy (realP->val);
  if (realP->prop)
    free (realP->prop);
  free (realP);
}

odb_event *
new_odb_event_listadd (odb_oid * oid, char *prop,
                       odb_oid * oidval, int before, odb_oid * other)
{
  odb_event_listadd *eP;
  eP = (odb_event_listadd *) malloc (sizeof (odb_event_listadd));
  if (eP) {
    /* initialize object */
    eP->type = ODB_EVENT_LISTADD;
    eP->oid.oid = oid->oid;
    eP->prop = strdup (prop);
    eP->oidval.oid = oidval->oid;
    eP->before = before;
    eP->other.oid = other ? other->oid : 0;

    /* check object integrity */
    if (!eP->prop) {
      free_odb_event_listadd ((odb_event *) eP);
      eP = NULL;
    }
  }
  return ((odb_event *) eP);
}

void
free_odb_event_listadd (odb_event * eP)
{
  odb_event_listadd *realP = (odb_event_listadd *) eP;
  if (realP->prop)
    free (realP->prop);
  free (realP);
}

odb_event *
new_odb_event_listrm (odb_oid * oid, char *prop, odb_oid * oidval)
{
  odb_event_listrm *eP;
  eP = (odb_event_listrm *) malloc (sizeof (odb_event_listrm));
  if (eP) {
    /* initialize object */
    eP->type = ODB_EVENT_LISTRM;
    eP->oid.oid = oid->oid;
    eP->prop = strdup (prop);
    eP->oidval.oid = oidval->oid;

    /* check object integrity */
    if (!eP->prop) {
      free_odb_event_listrm ((odb_event *) eP);
      eP = NULL;
    }
  }
  return ((odb_event *) eP);
}

void
free_odb_event_listrm (odb_event * eP)
{
  odb_event_listrm *realP = (odb_event_listrm *) eP;
  if (realP->prop)
    free (realP->prop);
  free (realP);
}

odb_event_type odb_event_type_read (odb_event * eP)
{
  return eP->type;
}

void
odb_event_destroy (odb_event * eP)
{
  /* not quite as efficient as a real thunk table: */
  switch (odb_event_type_read (eP)) {
  case ODB_EVENT_NEW:
    free_odb_event_new (eP);
    break;
  case ODB_EVENT_SET:
    free_odb_event_set (eP);
    break;
  case ODB_EVENT_LISTADD:
    free_odb_event_listadd (eP);
    break;
  case ODB_EVENT_LISTRM:
    free_odb_event_listrm (eP);
    break;
  case ODB_EVENT_DESTROYED:
    free_odb_event_destroyed (eP);
    break;
  default:
    free (eP);
    break;                      /* error! */
  };
}

odb_event *
odb_event_dup (odb_event * e)
{
  odb_event_new *e_new;
  odb_event_set *e_set;
  odb_event_listadd *e_listadd;
  odb_event_listrm *e_listrm;
  odb_event_destroyed *e_destroyed;

  odb_event *copy = NULL;
  switch (odb_event_type_read (e)) {
  case ODB_EVENT_NEW:
    e_new = (odb_event_new *) e;
    copy = new_odb_event_new (&e_new->oid, e_new->class);
    break;
  case ODB_EVENT_SET:
    e_set = (odb_event_set *) e;
    copy = new_odb_event_set (&e_set->oid, e_set->prop, e_set->val);
    break;
  case ODB_EVENT_LISTADD:
    e_listadd = (odb_event_listadd *) e;
    copy = new_odb_event_listadd (&e_listadd->oid, e_listadd->prop,
                                  &e_listadd->oidval, e_listadd->before,
                                  &e_listadd->other);
    break;
  case ODB_EVENT_LISTRM:
    e_listrm = (odb_event_listrm *) e;
    copy = new_odb_event_listrm (&e_listrm->oid, e_listrm->prop,
                                 &e_listrm->oidval);
    break;
  case ODB_EVENT_DESTROYED:
    e_destroyed = (odb_event_destroyed *) e;
    copy = new_odb_event_destroyed (&e_destroyed->oidval);
    break;
  default:
    break;                      /* error! */
  };
  return copy;
}

#include <stdio.h>

void
odb_eventlist_dump (GSList *eventlist, FILE *fh)
{
  if (!(cce_debug_mask & DBG_TXN)) return;
  while (eventlist) {
    odb_event_dump((odb_event*) eventlist->data, fh);
    eventlist = g_slist_next(eventlist);
  }
}

void
odb_event_dump (odb_event * e, FILE * fh)
{
  odb_event_new *e_new;
  odb_event_set *e_set;
  odb_event_listadd *e_listadd;
  odb_event_listrm *e_listrm;
  odb_event_destroyed *e_destroyed;

  if (!(cce_debug_mask & DBG_TXN)) return;

  e_new = (odb_event_new *) e;
  e_set = (odb_event_set *) e;
  e_listadd = (odb_event_listadd *) e;
  e_listrm = (odb_event_listrm *) e;
  e_destroyed = (odb_event_destroyed *) e;

  switch (odb_event_type_read (e)) {
  case ODB_EVENT_NEW:
    fprintf (fh, "\tNEW %08lx isa %s.\n", e_new->oid.oid, e_new->class);
    break;
  case ODB_EVENT_SET:
    fprintf (fh, "\tSET %08lx.%s = %s.\n",
             e_set->oid.oid, e_set->prop, (char *) e_set->val->data);
    break;
  case ODB_EVENT_LISTADD:
    fprintf (fh, "\tLISTADD %08lx.%s += %08lx ",
             e_listadd->oid.oid, e_listadd->prop, e_listadd->oidval.oid);
    if (e_listadd->other.oid) {
      if (e_listadd->before)
        fprintf (fh, "before %08lx", e_listadd->other.oid);
      else
        fprintf (fh, "after %08lx", e_listadd->other.oid);
    } else {
      if (e_listadd->before)
        fprintf (fh, "before first");
      else
        fprintf (fh, "after last");
    }
    fprintf (fh, "\n");
    break;
  case ODB_EVENT_LISTRM:
    fprintf (fh, "\tLISTRM %08lx.%s -= %08lx\n",
             e_listrm->oid.oid, e_listrm->prop, e_listrm->oidval.oid);
    break;
  case ODB_EVENT_DESTROYED:
    fprintf (fh, "\tDESTROY %08lx\n", e_destroyed->oidval.oid);
    break;
  default:
    fprintf (fh, "\tError: bad event!\n");
    break;                      /* error! */
  };
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
