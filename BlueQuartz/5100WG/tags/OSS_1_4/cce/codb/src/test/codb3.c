/* $Id: codb3.c 3 2003-07-17 15:19:15Z will $ 
 *
 * this test verifies the odb_get_old and odb_get_changed fns 
 */
#include <stdlib.h>
#include <stdio.h>
#include "debug.h"
#include <codb.h>
#include <libdebug.h>

void
dump_hash (GHashTable * hash)
{
  GHashIter *it;
  gpointer key, val;
  fprintf (stderr, "\ndumping hash\n");
  it = g_hash_iter_new (hash);
  for (g_hash_iter_first (it, &key, &val); key;
       g_hash_iter_next (it, &key, &val)) {
    fprintf (stderr, "\t\"%s\"=\"%s\"\n",
             key ? (char *) key : "*undef*", val ? (char *) val : "*undef*");
  }
  g_hash_iter_destroy (it);
}

void
dump_oidlist (GSList * list)
{
  fprintf (stderr, "\n  [");
  while (list) {
    fprintf (stderr, "%ld,", *((oid_t *) list->data));
    list = g_slist_next (list);
  }
  fprintf (stderr, "]  ");
}

void
t1 ()
{
  odb_handle *h;
  GHashTable *attr1, *attr2;
  oid_t oid[11];

  TRY (h = odb_handle_new (NULL), "");

  attr1 = odb_attr_hash_new ();
  attr2 = odb_attr_hash_new ();
  odb_attr_hash_assign (attr1, "prop1", "twinkie winkie");
  odb_attr_hash_assign (attr1, "proptwo", "po");
  odb_attr_hash_assign (attr1, "propthree", "dipsy-do");

  QTRY (odb_create (h, "ClassFoo", attr1, &oid[0]) == CODB_RET_SUCCESS, "");
  QTRY (odb_objexists (h, oid[0]), "");
  QTRY (odb_commit (h) == CODB_RET_SUCCESS, "");

	odb_attr_hash_flush(attr2);  
  TRY(odb_get_changed(h, oid[0], "", attr2)==CODB_RET_SUCCESS, "");
  fprintf(stderr,"empty set of changes:\n");
  dump_hash(attr2);

	odb_attr_hash_flush(attr1);  
  odb_attr_hash_assign (attr1, "proptwo", "moomoo");
  QTRY (odb_set(h, oid[0], "", attr1)==CODB_RET_SUCCESS,"");
  
	odb_attr_hash_flush(attr2);  
  TRY(odb_get_changed(h, oid[0], "", attr2)==CODB_RET_SUCCESS, "");
  fprintf(stderr,"should contain one change:\n");
  dump_hash(attr2);
  
	odb_attr_hash_flush(attr2);  
  TRY(odb_get_old(h, oid[0], "", attr2)==CODB_RET_SUCCESS, "");
  fprintf(stderr,"here is the old data:\n");
  dump_hash(attr2);
  
  QTRY (odb_commit (h) == CODB_RET_SUCCESS, "");

	odb_attr_hash_flush(attr2);  
  TRY(odb_get_changed(h, oid[0], "", attr2)==CODB_RET_SUCCESS, "");
  fprintf(stderr,"empty set of changes:\n");
  dump_hash(attr2);
  
  QTRY (odb_create (h, "ClassFoo", attr1, &oid[1]) == CODB_RET_SUCCESS, "");
  QTRY (odb_objexists (h, oid[1]), "");
  
  odb_attr_hash_flush(attr2);
  TRY(odb_get_old(h, oid[1], "", attr2)==CODB_RET_UNKOBJ, "a");
  odb_attr_hash_flush(attr2);
  TRY(odb_get_changed(h, oid[1], "", attr2)==CODB_RET_SUCCESS, "b");
  
  QTRY (odb_commit (h) == CODB_RET_SUCCESS, "");

	QTRY (odb_destroy(h, oid[1]) == CODB_RET_SUCCESS, "");
  
  odb_attr_hash_flush(attr2);
  TRY(odb_get_old(h, oid[1], "", attr2)==CODB_RET_SUCCESS, "c");
  odb_attr_hash_flush(attr2);
  TRY(odb_get_changed(h, oid[1], "", attr2)==CODB_RET_UNKOBJ, "d");
  
  QTRY (odb_commit (h) == CODB_RET_SUCCESS, "");

  odb_attr_hash_destroy(attr2);
  odb_attr_hash_destroy(attr1);
  odb_handle_destroy (h);
}

int
main ()
{
  system ("/bin/rm -rf codb");
  system ("/bin/rm -rf codb.oids");

  t1 ();
  memdebug_dump ();

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
