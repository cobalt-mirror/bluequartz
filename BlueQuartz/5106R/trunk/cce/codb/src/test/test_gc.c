/* $Id: test_gc.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * test_gc
 *
 * tests garbage collection features in transaction object.  Creates
 * a bunch of object trees, and then unlinks parts of them, and makes
 * sure that the g.c. behaves appropriately.
 *
 * author: jmayer
 */

#include <cce_common.h>
#include <odb_transaction.h>
#include <odb_txn_inspect.h>
#include <odb_txn_events.h>
#include <glib.h>

#include "debug.h"

#define IDENT "$Id: test_gc.c 259 2004-01-03 06:28:40Z shibuya $"

#define NUMOFOBJS   8

odb_impl_handle * impl = NULL;
odb_txn txn = NULL;
odb_oid o[NUMOFOBJS];

#define ATRY

static void
startup()
{
  int i;

  system("/bin/rm -rf codb");
  
  fprintf (stderr, "\n** reset object database **");

  QTRY(impl=impl_handle_new(), "");
  QTRY(txn=odb_txn_new(impl), "");

  QTRY(impl_create_class(impl,"ClassA", "") == CODB_RET_SUCCESS, "");
  
  for (i = 0; i < NUMOFOBJS; i++)
  {
    o[i].oid = 1+i;
    QTRY (odb_txn_createobj(txn, &o[i], "ClassA") == CODB_RET_SUCCESS, "");
  }
}
static void
cleanup()
{
  if (txn) odb_txn_destroy(txn);
  if (impl) impl_handle_destroy(impl);
}

void
dbg_dump_events (GSList * events); /* defined in odb_txn_inspect */

void gfunc_freedata(gpointer data, gpointer user_data)
{
  free((void*)data);
}

gint gcomp_oids(gconstpointer b, gconstpointer a)
{
  odb_event_destroyed *e;
  odb_oid *ob;
  e = (odb_event_destroyed*)a;
  ob = (odb_oid*)b;
  return ((e->type == ODB_EVENT_DESTROYED) && (e->oidval.oid == ob->oid)) ? 0 : 1 ;
}

void 
test_destroylist(odb_txn txn, char *str)
{
  GSList *events;
  GSList *expected;
  int expected_event_count;
  int tmp;
    
  /* parse str and build expected object list */
  {
    char *cursor, *token;
    expected_event_count = 2;
    expected = NULL;
    cursor = str;
    token=str;
    while (*cursor != '\0') {
      if (*cursor == ',') {
        odb_oid *oid;
        oid =malloc(sizeof(odb_oid));
        oid->oid = strtoul(token, NULL, 16);
        expected = g_slist_append(expected, oid);
        expected_event_count++;
        token = cursor+1;
      }
      cursor++;
    }
    if (token != cursor) {
      odb_oid *oid;
      oid =malloc(sizeof(odb_oid));
      oid->oid = strtoul(token, NULL, 16);
      expected = g_slist_append(expected, oid);
      expected_event_count++;
    }
  }

  /* extract list of destroyed objects */
  events = NULL;
  TRY(odb_txn_garbagecollect(txn) == CODB_RET_SUCCESS, "");
  TRY((tmp=odb_txn_inspect_listevents(txn, &events)) >= 0, 
    "\n\tgot %d events",tmp);
  dbg_dump_events(events);
  /* throw away first two events */
  //events = g_slist_remove(events, events->data);
  //events = g_slist_remove(events, events->data);

  /* compare events with objects specified in str */  
  tests++;
  {
    GString *errstr;
    GSList *cursor, *found;
    int errflag;
    errflag = 0;
    errstr = g_string_new("");
    fprintf(stderr,"\ntest%03d: DESTROYED=%-50.50s -> ", tests, str );
    for (cursor = events;
      cursor; cursor = g_slist_next(cursor)) 
    {
      odb_event_destroyed *ed = (odb_event_destroyed*)cursor->data;
      if (ed->type != ODB_EVENT_DESTROYED) continue;
      
      /* find deleted object in the expected deletions list */
      found = g_slist_find_custom(expected, cursor->data, gcomp_oids);
      
      if (!found) {
        /* foo, i wasn't supposed to delete this, but I did. */
        g_string_sprintfa(errstr,"extra %lX, ", ed->oidval.oid);
        errflag = 1;
      } else {
        /* if i found it, i no longer need to expect it, do i? */
        expected = g_slist_remove(expected, found->data);
      }
    }
    
    /* expected list should be empty now. */
    if (expected) 
    {
      /* gasp!  unexpected! */
      errflag = 1;
      g_string_sprintfa(errstr,"expected not empty: ");
      for (cursor = expected; cursor; cursor = g_slist_next(cursor)) 
      {
        g_string_sprintfa(errstr,"missed %lX, ", ((odb_oid*)cursor->data)->oid);
      }
    }
    
    /* style is all about how you handle failure */
    if (errflag) 
    {
      errors++;
      fprintf(stderr, "FAILED\n%s", errstr->str);
    } else {
      fprintf(stderr, "ok.");
    }
    g_string_free(errstr, TRUE);
  }
    
  /* clean up destroyed objects */
  odb_txn_inspect_freelist(&events);
  g_slist_foreach(expected, gfunc_freedata, NULL);
  g_slist_free(expected);
    
}

void test1()
{
  int i;
  
  startup();

  /* build a simple, linked list of objects */
  fprintf(stderr, "\n** building a linear list of objects **");
  for (i = 0; i < NUMOFOBJS-1; i++)
  {
    QTRY(odb_txn_listadd(txn,&o[i],"kid",&o[i+1],0,NULL)==CODB_RET_SUCCESS, "");
  }
  QTRY(odb_txn_commit(txn)==CODB_RET_SUCCESS && odb_txn_flush(txn)==CODB_RET_SUCCESS,
    "");
  
  /* unlink the last element */
  fprintf(stderr, "\n** unlinking last object **");
  QTRY(odb_txn_listrm(txn, &o[6], "kid", &o[7]) == CODB_RET_SUCCESS, "");
  test_destroylist(txn, "8");

  fprintf(stderr, "\n** unlinking objects 5 from 4 **");  
  QTRY(odb_txn_flush(txn) == CODB_RET_SUCCESS, "");
  QTRY(odb_txn_listrm(txn, &o[4], "kid", &o[5]) == CODB_RET_SUCCESS, "");
  test_destroylist(txn, "6,7,8");
  
    
  cleanup();  
}

void test2()
{
  
  startup();

  /* don't link any objects */
  fprintf(stderr, "\n** all objects unlinked **");  
  test_destroylist(txn, "2,3,4,5,6,7,8");
    
  cleanup();  
}

void test3()
{
  
  startup();

  /* link a few objects */
  QTRY(odb_txn_listadd(txn,&o[4],"kid",&o[5],0,NULL)==CODB_RET_SUCCESS, "");
  QTRY(odb_txn_listadd(txn,&o[2],"kid",&o[3],0,NULL)==CODB_RET_SUCCESS, "");
  
  fprintf(stderr, "\n** linkage: 1  2  3-4  5-6  7  8 **");  
  test_destroylist(txn, "2,3,4,5,6,7,8");
  
  cleanup();  
}

void test4()
{
  startup();

  QTRY(odb_txn_listadd(txn,&o[0],"kid",&o[1],0,NULL)==CODB_RET_SUCCESS, "");
  fprintf(stderr, "\n** linkage: 1-2  3-4  5-6  7  8 **");  
  test_destroylist(txn, "3,4,5,6,7,8");
    
  cleanup();  
}

void setup_tree()
{
  int i;
  int links[] = {
    1,2,
    1,3,
    2,4,
    2,5,
    3,6,
    3,7,
    5,8,
    0,0 };
  i = 0;
  while (links[i]) {
    int p, k;
    p = links[i] - 1;
    k = links[i+1] - 1;
    QTRY(odb_txn_listadd(txn,&o[p],"kid",&o[k],0,NULL)==CODB_RET_SUCCESS, "");
    i+=2;
  }
  QTRY(odb_txn_commit(txn)==CODB_RET_SUCCESS,"");
  QTRY(odb_txn_flush(txn)==CODB_RET_SUCCESS,"");
}
  

void test5()
{
  startup();

  fprintf(stderr, "\n** setting up tree configuration **");
  setup_tree();

  test_destroylist(txn, "");
  
  QTRY(odb_txn_listrm(txn,&o[2],"kid",&o[6])==CODB_RET_SUCCESS,"");
  test_destroylist(txn, "7");
  QTRY(odb_txn_flush(txn)==CODB_RET_SUCCESS,"");
    
  QTRY(odb_txn_listrm(txn,&o[0],"kid",&o[2])==CODB_RET_SUCCESS,"");
  test_destroylist(txn, "3,6,7");
  QTRY(odb_txn_flush(txn)==CODB_RET_SUCCESS,"");
    
  QTRY(odb_txn_listrm(txn,&o[0],"kid",&o[2])==CODB_RET_SUCCESS,"");
  QTRY(odb_txn_listrm(txn,&o[4],"kid",&o[7])==CODB_RET_SUCCESS,"");
  test_destroylist(txn, "3,6,7,8");
  QTRY(odb_txn_flush(txn)==CODB_RET_SUCCESS,"");
    
  QTRY(odb_txn_listrm(txn,&o[0],"kid",&o[2])==CODB_RET_SUCCESS,"");
  QTRY(odb_txn_listrm(txn,&o[0],"kid",&o[1])==CODB_RET_SUCCESS,"");
  test_destroylist(txn, "2,3,4,5,6,7,8");
  QTRY(odb_txn_flush(txn)==CODB_RET_SUCCESS,"");
    
  cleanup();  
}


int main()
{
  fprintf (stderr, IDENT "\n");

  test1();
  test2();
  test3();
  test4();
  test5();
  memdebug_dump();

  END_MAIN;
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
