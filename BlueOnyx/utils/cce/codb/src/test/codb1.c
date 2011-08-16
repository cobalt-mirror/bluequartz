/* $Id: codb1.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
#include <cce_common.h>
#include <stdlib.h>
#include <stdio.h>
#include "debug.h"
#include <codb.h>
#include <libdebug.h>

void dump_hash(GHashTable *hash)
{
	GHashIter *it;
  gpointer key, val;
  fprintf(stderr,"\ndumping hash\n");
  it = g_hash_iter_new(hash);
  for (g_hash_iter_first(it, &key, &val); key;
  	   g_hash_iter_next(it, &key, &val))
  {
  	fprintf(stderr,"\t\"%s\"=\"%s\"\n", 
    	key ? (char*)key : "*undef*",
      val ? (char*)val : "*undef*");
  }
  g_hash_iter_destroy(it);
}

void t1()
{
	odb_handle *h;
  GHashTable *attr, *attr2;
  oid_t oid[10];
  char *val;
  
  TRY (h = odb_handle_new(NULL), "");

	attr = odb_attr_hash_new(); 
  attr2 = odb_attr_hash_new();
  odb_attr_hash_assign(attr, "prop1", "value");
  odb_attr_hash_assign(attr, "proptwo", "othervalue");
  odb_attr_hash_assign(attr, "propthree", "yetanothervalue");
 
 	TRY (odb_create(h, "ClassFoo", attr, &oid[0]) == CODB_RET_SUCCESS, "");
  TRY (odb_objexists(h, oid[0]), "");
  odb_dump_events(h);
  
  TRY (odb_get(h, oid[0], "", attr2) == CODB_RET_SUCCESS, "");
  TRY (val = odb_attr_hash_lookup(attr2, "prop1"), "");
  TRY (val && (strcmp(val,"value")==0), "");
  TRY (val = odb_attr_hash_lookup(attr2, "proptwo"), "");
  TRY (val && (strcmp(val,"othervalue")==0), "");
  TRY (val = odb_attr_hash_lookup(attr2, "propthree"), "");
  TRY (val && (strcmp(val,"yetanothervalue")==0), "");
  TRY (!odb_attr_hash_lookup(attr2, "propbogus"), "");

	odb_attr_hash_flush(attr);
  odb_attr_hash_assign(attr, "prop2", "val2");
  
 	TRY (odb_create(h, "ClassFoo", attr, &oid[1]) == CODB_RET_SUCCESS, "");
  odb_dump_events(h);

	TRY (odb_commit(h) == CODB_RET_SUCCESS,"");
  
  TRY (odb_get(h, oid[0], "", attr2) == CODB_RET_SUCCESS, "");
  TRY (val = odb_attr_hash_lookup(attr2, "prop1"), "");
  TRY (val && (strcmp(val,"value")==0), "");
  TRY (val = odb_attr_hash_lookup(attr2, "proptwo"), "");
  TRY (val && (strcmp(val,"othervalue")==0), "");
  TRY (val = odb_attr_hash_lookup(attr2, "propthree"), "");
  TRY (val && (strcmp(val,"yetanothervalue")==0), "");
  TRY (!odb_attr_hash_lookup(attr2, "propbogus"), "");

 	TRY (odb_create(h, "ClassFoo", attr, &oid[2]) == CODB_RET_SUCCESS, "");
  TRY (odb_objexists(h, oid[2]), "");
  
 	TRY (odb_create(h, "ClassFoo", attr, &oid[3]) == CODB_RET_SUCCESS, "");
  TRY (odb_objexists(h, oid[3]), "");
  
  odb_dump_events(h);
	TRY (odb_destroy(h, oid[0]) == CODB_RET_SUCCESS, "");
  TRY (!odb_objexists(h, oid[0]), "");
  TRY (odb_get(h, oid[0], "", attr2) == CODB_RET_UNKOBJ, "");

  odb_dump_events(h);
	TRY (odb_destroy(h, oid[3]) == CODB_RET_SUCCESS, "");
  TRY (!odb_objexists(h, oid[3]), "");
  TRY (odb_get(h, oid[3], "", attr2) == CODB_RET_UNKOBJ, "");

  odb_dump_events(h);
	TRY (odb_commit(h) == CODB_RET_SUCCESS,"");
  odb_dump_events(h);

  TRY (!odb_objexists(h, oid[3]), "");
  TRY (odb_get(h, oid[3], "", attr2) == CODB_RET_UNKOBJ, "");

	odb_attr_hash_destroy(attr);
	odb_attr_hash_destroy(attr2);
  
  odb_handle_destroy(h);
}

int
main()
{
	system("/bin/rm -rf codb");
  system("/bin/rm -rf codb.oids");

	t1();
  
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
