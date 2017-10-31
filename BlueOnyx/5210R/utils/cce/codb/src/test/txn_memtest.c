/* $Id: txn_memtest.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#include <cce_common.h>
#include <odb_transaction.h>
#include <odb_txn_inspect.h>
#include <odb_txn_events.h>
#include <stdio.h>
#include <stdlib.h>
#include <glib.h>
#include <libdebug.h>

#define IDENT "$Id: txn_memtest.c 259 2004-01-03 06:28:40Z shibuya $"
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

int
main()
{
  startup();

  tp1();
  
  cleanup();

  dbg_cce_scalar_dump(stderr);
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
