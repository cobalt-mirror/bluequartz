/* $Id: txn.c 3 2003-07-17 15:19:15Z will $
 */

#include <odb_transaction.h>
#include <odb_txn_inspect.h>
#include <odb_txn_events.h>
#include <stdio.h>
#include <stdlib.h>
#include <glib.h>
#include <libdebug.h>

#define IDENT "$Id: txn.c 3 2003-07-17 15:19:15Z will $"
#define tet_infoline(a...) fprintf(stderr, "\n*** " ##a );

#include "debug.h"

static codb_ret ret;
      

static void startup(), cleanup();
void (*tet_startup)() = startup;
void (*tet_cleanup)() = cleanup;

static void tp1();

static void 
TRY_OLIST(odb_txn t, odb_oid *o, char *p, char *v)
{ 
  char *s;
  char buf[70];
  odb_oidlist *olist;
  tests++; 
  olist = odb_oidlist_new();
  snprintf(buf, 70, "%08lx.%s == %s", o->oid, p, v);
  fprintf(stderr, "\ntest%03d: %-60.60s  -> ", 
    tests, buf);
  if (odb_txn_list(t,o,p,olist) != CODB_RET_SUCCESS) {
    odb_oidlist_destroy(olist);
    errors++; fprintf(stderr, "FAILED list"); return;
  }
  s = odb_oidlist_to_str(olist);
  if (!s) {
    odb_oidlist_destroy(olist);
    errors++; fprintf(stderr, "FAILED tostr"); return;
  }
  if (strcmp(s, v)!=0) {
    errors++; fprintf(stderr, "FAILED \"%s\"", s); 
  } else {
    fprintf (stderr, "ok.");
  }
  odb_oidlist_destroy(olist);
  free(s);
}

static void
startup()
{
  tet_infoline(IDENT );
  tet_infoline("starting...");
  system("/bin/rm -rf codb");
  errors= 0;
  tests = 0;
}

static void
cleanup()
{
  tet_infoline("cleaning up...");
}

void
dump_events(odb_txn txn)
{
  GSList *events, *ptr;
  int count;

  events = NULL;
  
  count = odb_txn_inspect_listevents ( txn, &events );
  
  fprintf(stderr, "transaction contains %d events\n", count);
  ptr = events;
  while (ptr) {
    odb_event_dump( (odb_event*) ptr->data, stderr );
    ptr = ptr->next;
  }
  
  fprintf(stderr, "\n");
}

static void
test_odblist()
{
  odb_oidlist * olist;
  odb_oid oid1, oid2, oid3, oid4, oid5;
  oid1.oid = 1;
  oid2.oid = 2;
  oid3.oid = 3;
  oid4.oid = 4;
  oid5.oid = 5;
  
  tet_infoline ("Testing oidlist object");
  
  TRY ( (olist = odb_oidlist_new()) != NULL, "");

  TRY (odb_oidlist_has(olist, &oid1) == 0, "has not thinks it has");
  TRY (odb_oidlist_add(olist, &oid1, 1, NULL) == 0, "");
  TRY (odb_oidlist_has(olist, &oid1) == 1, "has thinks it has not");
  TRY (odb_oidlist_add(olist, &oid2, 1, &oid1) == 0, "");
  TRY (odb_oidlist_add(olist, &oid3, 1, &oid1) == 0, "");
  TRY (odb_oidlist_add(olist, &oid4, 1, &oid2) == 0, "");
  TRY (odb_oidlist_has(olist, &oid1) == 1, "has thinks it has not");
  TRY (odb_oidlist_has(olist, &oid3) == 1, "has thinks it has not");
  TRY (odb_oidlist_has(olist, &oid5) == 0, "has not thinks it has");
  TRY (odb_oidlist_rm(olist, &oid3) == 0, "");
  TRY (odb_oidlist_has(olist, &oid3) == 0, "has not thinks it has");

  odb_oidlist_flush(olist);
  
  odb_oidlist_destroy(olist);
}

static void
test_destroy()
{
  odb_impl_handle * impl;
  odb_txn txn;
  odb_oid oid;

  tet_infoline("Testing transaction object");
  
  impl = impl_handle_new();
  TRY (impl, "Could not construct impl");
  txn = odb_txn_new(impl);
  TRY (txn, "Could not construct txn");

  TRY(impl_create_class(impl, "ClassZ", "") == CODB_RET_SUCCESS, 
    "Could not create ClassA (%d)", ret);
  
  oid.oid = 50;
  TRY ( !odb_txn_objexists(txn, &oid), "Thought that oid5 exists");
  TRY ( odb_txn_createobj(txn, &oid, "ClassZ") == CODB_RET_SUCCESS,
    "Could not create object");
  TRY ( odb_txn_objexists(txn, &oid), "Creation of oid5 did not register.");

	TRY ( odb_txn_destroyobj(txn, &oid) == CODB_RET_SUCCESS,
  	"Could not destroy object 50");
  TRY ( !odb_txn_objexists(txn, &oid), "Destruction of oid50 did not register.");

  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");

  TRY ( !odb_txn_objexists(txn, &oid), "Thought that oid50 exists");
  TRY ( odb_txn_createobj(txn, &oid, "ClassZ") == CODB_RET_SUCCESS,
    "Could not create object");
  TRY ( odb_txn_objexists(txn, &oid), "Creation of oid50 did not register.");

  dump_events(txn); 

	TRY ( odb_txn_commit(txn) == CODB_RET_SUCCESS, "");
  TRY ( odb_txn_flush(txn) == CODB_RET_SUCCESS, "");
  TRY ( odb_txn_objexists(txn, &oid), "Creation of oid50 did not commit.");

	TRY ( odb_txn_destroyobj(txn, &oid) == CODB_RET_SUCCESS,
  	"Could not destroy object 50");
  TRY ( !odb_txn_objexists(txn, &oid), "Destruction of oid50 did not register.");

	TRY ( odb_txn_commit(txn) == CODB_RET_SUCCESS, "");
  TRY ( odb_txn_flush(txn) == CODB_RET_SUCCESS, "");
  TRY ( !odb_txn_objexists(txn, &oid), "Destruction of oid50 did not commit.");

  odb_txn_destroy(txn);
  impl_handle_destroy(impl);
}

static void
tp1()
{
  odb_impl_handle * impl;
  odb_txn txn;

  impl = impl_handle_new();
  if (!impl) { errors++; }
  txn = odb_txn_new(impl);
  if (!txn) { errors++; }
    
  odb_txn_destroy(txn);
  impl_handle_destroy(impl);


  
}

static void
tp2()
{
  odb_impl_handle * impl;
  odb_txn txn, txn2;
  odb_oid oid, oid2, oid3, oid4, oid6, oid7, oid8, oid9;
  cce_scalar * sc1, * sc2;
  odb_oidlist *olist;

  tet_infoline("Testing transaction object");
  
  impl = impl_handle_new();
  TRY (impl, "Could not construct impl");
  txn = odb_txn_new(impl);
  TRY (txn, "Could not construct txn");

  TRY(impl_create_class(impl, "ClassA", "") == CODB_RET_SUCCESS, 
    "Could not create ClassA (%d)", ret);
  
  oid.oid = 5;
  TRY ( !odb_txn_objexists(txn, &oid), "Thought that oid5 exists");

  TRY ( odb_txn_createobj(txn, &oid, "ClassA") == CODB_RET_SUCCESS,
    "Could not create object");
  TRY ( odb_txn_objexists(txn, &oid), "Creation of oid5 did not register.");

  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");
  
  TRY ( !odb_txn_objexists(txn, &oid), "Thought that oid5 exists");

  TRY ( odb_txn_createobj(txn, &oid, "ClassA") == CODB_RET_SUCCESS,
    "Could not create object");
  TRY ( odb_txn_objexists(txn, &oid), "Creation of oid5 did not register.");
  
  TRY ( odb_txn_commit (txn) == CODB_RET_SUCCESS, "Commit failed.");
  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");
  TRY ( odb_txn_objexists(txn, &oid), "Couldn't find oid5.");

  sc1 = cce_scalar_new_from_str("first scalar value");
  sc2 = cce_scalar_new_undef();
  TRY ( 
    odb_txn_set(txn, &oid, "prop1", sc1) == CODB_RET_SUCCESS, 
    "Set failed.");
  TRY (
	odb_txn_set(txn, &oid, "propZ", sc1) == CODB_RET_SUCCESS, "");
  TRY (
    (odb_txn_get(txn, &oid, "prop1", sc2) == CODB_RET_SUCCESS)
    && cce_scalar_isdefined(sc2) && (strcmp(sc1->data, sc2->data) == 0),
    "mismatch: sc2 = %s", (char*)sc2->data );

  TRY (
    (odb_txn_get(txn, &oid, "prop2", sc2) == CODB_RET_SUCCESS)
    && !cce_scalar_isdefined(sc2),
    "sc2 wasn't undefined");
   
  dump_events(txn); 
  TRY ( odb_txn_commit (txn) == CODB_RET_SUCCESS, "Commit failed.");
  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");
    
  TRY (
    (odb_txn_get(txn, &oid, "prop1", sc2) == CODB_RET_SUCCESS)
    && cce_scalar_isdefined(sc2) && (strcmp(sc1->data, sc2->data) == 0),
    "mismatch: sc2 = %s", (char*)sc2->data );

  TRY (
    (odb_txn_get(txn, &oid, "prop2", sc2) == CODB_RET_SUCCESS)
    && !cce_scalar_isdefined(sc2),
    "sc2 wasn't undefined");

  tet_infoline("Testing transaction list handling");

  oid2.oid = 2;
  oid3.oid = 3;
  oid4.oid = 4;
  oid6.oid = 6;
  oid7.oid = 7;
  oid8.oid = 8;
  oid9.oid = 9;
  TRY (
    (odb_txn_createobj(txn, &oid2, "ClassA") == CODB_RET_SUCCESS) &&
    (odb_txn_createobj(txn, &oid3, "ClassA") == CODB_RET_SUCCESS) &&
    (odb_txn_createobj(txn, &oid4, "ClassA") == CODB_RET_SUCCESS) &&
    (odb_txn_createobj(txn, &oid6, "ClassA") == CODB_RET_SUCCESS) &&
    (odb_txn_createobj(txn, &oid7, "ClassA") == CODB_RET_SUCCESS) &&
    (odb_txn_createobj(txn, &oid8, "ClassA") == CODB_RET_SUCCESS) &&
    (odb_txn_createobj(txn, &oid9, "ClassA") == CODB_RET_SUCCESS) ,
    "Could not create objects to play with." );
  olist = odb_oidlist_new();
  TRY (
    (odb_txn_listadd(txn, &oid, "reflist", &oid2, 1, NULL ) == CODB_RET_SUCCESS),
    "Could not add to reflist property" );
  TRY_OLIST (txn, &oid, "reflist", "00000002,");

  TRY (
    (odb_txn_listadd(txn, &oid, "reflist", &oid3, 1, &oid2 ) == CODB_RET_SUCCESS),
    "Could not add to reflist property" );
  TRY_OLIST (txn, &oid, "reflist", "00000003,00000002,");
  TRY (
    (odb_txn_listadd(txn, &oid, "reflist", &oid4, 1, &oid2 ) == CODB_RET_SUCCESS),
    "Could not add to reflist property" );
  TRY_OLIST (txn, &oid, "reflist", "00000003,00000004,00000002,");
    
  TRY ( odb_txn_commit (txn) == CODB_RET_SUCCESS, "Commit failed.");
  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");

  TRY_OLIST (txn, &oid, "reflist", "00000003,00000004,00000002,");
  
  TRY ( 
    odb_txn_listrm(txn, &oid, "reflist", &oid9) != CODB_RET_SUCCESS,
    "Removal of non-existance oid should have failed.");
  
  TRY( odb_txn_listhas(txn, &oid, "reflist", &oid4) == 1, "");
  TRY( odb_txn_listrm(txn, &oid, "reflist", &oid4 ) == CODB_RET_SUCCESS,"");
  TRY( odb_txn_listhas(txn, &oid, "reflist", &oid4) == 0, "");

  TRY_OLIST (txn, &oid, "reflist", "00000003,00000002,");

  TRY ( odb_txn_commit (txn) == CODB_RET_SUCCESS, "Commit failed.");
  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");

  TRY( odb_txn_listhas(txn, &oid, "reflist", &oid4) == 0, "");
  TRY_OLIST (txn, &oid, "reflist", "00000003,00000002,");

  TRY ( odb_txn_listrm(txn, &oid, "reflist", &oid2) == CODB_RET_SUCCESS,"");
  TRY_OLIST (txn, &oid, "reflist", "00000003,");

  TRY ( odb_txn_listrm(txn, &oid, "reflist", &oid3) == CODB_RET_SUCCESS,"");
  TRY_OLIST (txn, &oid, "reflist", "");

  TRY ( odb_txn_commit (txn) == CODB_RET_SUCCESS, "Commit failed.");
  TRY ( odb_txn_flush (txn) == CODB_RET_SUCCESS, "Flush failed.");
  
  TRY_OLIST (txn, &oid, "reflist", "");

  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid2,0,NULL)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid3,0,NULL)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid4,0,NULL)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid6,0,&oid2)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid7,0,NULL)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listrm (txn,&oid,"reflist",&oid4)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid8,0,NULL)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listrm (txn,&oid,"reflist",&oid3)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listrm (txn,&oid,"reflist",&oid2)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid4,1,&oid7)==CODB_RET_SUCCESS, "");
  TRY(odb_txn_listadd(txn,&oid,"reflist",&oid2,1,NULL)==CODB_RET_SUCCESS, "");
  TRY_OLIST (txn, &oid, "reflist",
    "00000002,00000006,00000004,00000007,00000008,");

	#if 0
  {
    GSList *scalars, *lists;
    scalars = NULL; lists = NULL;
    TRY(odb_txn_get_properties(txn,&oid,&scalars,&lists)==CODB_RET_SUCCESS, "");
    TRY(strcmp((char*)lists->data,"reflist")==0, "");
    TRY(!lists->next, "list should only contain one elment");
    TRY(strcmp((char*)scalars->data,"prop1")==0, "");
    TRY(!scalars->next, "list should only contain one elment, next=%s",
      (char*)scalars->next->data);
  }
	#endif

  dump_events(txn);
  
  {
    FILE *f;
    f = fopen("/tmp/txn.test", "w");
    TRY(odb_txn_serialize(txn, f) == CODB_RET_SUCCESS, "");
    fclose(f);
  };

  TRY (txn2 = odb_txn_new(impl), "Could not construct txn2");
  
  {
    FILE *f;
    f = fopen("/tmp/txn.test", "r");
    TRY(odb_txn_unserialize(txn2, f) == CODB_RET_SUCCESS,
      "Unserialization failed (parser error?)");
    fclose(f);
  }
  TRY_OLIST (txn2, &oid, "reflist",
    "00000002,00000006,00000004,00000007,00000008,");

  odb_txn_destroy(txn2);
  
  odb_oidlist_destroy(olist);
  odb_txn_destroy(txn);
  impl_handle_destroy(impl);
  
}

int
main()
{
  startup();

  test_odblist();
  
  test_destroy();
  
  tp1();
  
  tp2();
  
  cleanup();

  memdebug_dump();

  END_MAIN ;
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
