/* $Id: codb2.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
#include <cce_common.h>
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
  GHashTable *attr1, *attr2, *attr3;
  GHashTable *crit;
  GSList *oidlist;
  oid_t oid[11];
  int i;

  TRY (h = odb_handle_new (NULL), "");

  attr1 = odb_attr_hash_new ();
  odb_attr_hash_assign (attr1, "prop1", "twinkie winkie");
  odb_attr_hash_assign (attr1, "proptwo", "po");
  odb_attr_hash_assign (attr1, "propthree", "dipsy-do");

  attr2 = odb_attr_hash_new ();
  odb_attr_hash_assign (attr2, "foo", "bar");
  odb_attr_hash_assign (attr2, "proptwo", "po");
  odb_attr_hash_assign (attr2, "propthree", "rock");

  attr3 = odb_attr_hash_new ();
  odb_attr_hash_assign (attr3, "prop1", "paper");
  odb_attr_hash_assign (attr3, "proptwo", "rock");
  odb_attr_hash_assign (attr3, "propthree", "scissors");

  for (i = 0; i < 5; i++) {
    QTRY (odb_create (h, "ClassFoo", attr1, &oid[i]) == CODB_RET_SUCCESS, "");
    QTRY (odb_objexists (h, oid[i]), "");
  }



  oidlist = NULL;
  crit = odb_attr_hash_new ();
  odb_attr_hash_assign (crit, "proptwo", "po");
  dump_hash (crit);
  TRY (odb_find (h, "ClassFoo", crit, &oidlist) == CODB_RET_SUCCESS, "");
  TRY (g_slist_length (oidlist) == 5, "");
  dump_oidlist (oidlist);

  QTRY (odb_commit (h) == CODB_RET_SUCCESS, "");

  oidlist = NULL;
  crit = odb_attr_hash_new ();
  odb_attr_hash_assign (crit, "proptwo", "po");
  dump_hash (crit);
  TRY (odb_find (h, "ClassFoo", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 5, "");

  TRY (odb_find (h, "ClassBogus", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 0, "");

  QTRY (odb_create (h, "ClassFoo", attr2, &oid[5]) == CODB_RET_SUCCESS, "");
  QTRY (odb_create (h, "ClassBar", attr3, &oid[6]) == CODB_RET_SUCCESS, "");
  QTRY (odb_create (h, "ClassBar", attr2, &oid[7]) == CODB_RET_SUCCESS, "");
  QTRY (odb_create (h, "ClassBar", attr3, &oid[8]) == CODB_RET_SUCCESS, "");
  QTRY (odb_create (h, "ClassBar", attr2, &oid[9]) == CODB_RET_SUCCESS, "");
  QTRY (odb_create (h, "ClassBar", attr3, &oid[10]) == CODB_RET_SUCCESS, "");

	{
  	char *cname;
    cname = odb_get_classname(h, oid[9]);
    TRY (cname && strcmp(cname, "ClassBar")==0, "");
    fprintf(stderr, " oid %ld is of class %s\n", oid[9], cname);
    free(cname);

    cname = odb_get_classname(h, 0);
    TRY (cname && strcmp(cname, "INDEX")==0, "");
    fprintf(stderr, " oid 0 is of class %s\n", cname);
    free(cname);
  }

  TRY (odb_find (h, "ClassFoo", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 6, "");

  odb_attr_hash_flush (crit);
  odb_attr_hash_assign (crit, "propthree", "rock");
  dump_hash (crit);
  TRY (odb_find (h, "ClassFoo", crit, &oidlist) == CODB_RET_SUCCESS, "");
  TRY (g_slist_length (oidlist) == 1, "");
  dump_oidlist (oidlist);

  QTRY (odb_commit (h) == CODB_RET_SUCCESS, "");

  TRY (odb_find (h, "ClassFoo", crit, &oidlist) == CODB_RET_SUCCESS, "");
  TRY (g_slist_length (oidlist) == 1, "");
  dump_oidlist (oidlist);

  odb_attr_hash_flush (crit);
  odb_attr_hash_assign (crit, "proptwo", "po");
  dump_hash (crit);
  oidlist = NULL;

  TRY (odb_find (h, "ClassFoo", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 6, "");

  TRY (odb_find (h, "ClassBar", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 2, "");

  odb_attr_hash_flush (crit);
  odb_attr_hash_assign (crit, "prop1", "paper");
  dump_hash (crit);
  TRY (odb_find (h, "ClassBar", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 3, "");

  fprintf (stderr, "\n------------------------------------------");

  odb_attr_hash_flush (crit);
  odb_attr_hash_assign (crit, "proptwo", "foo");
  dump_hash (crit);
  TRY (odb_find (h, "ClassBar", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 0, "");

  odb_attr_hash_flush (crit);
  odb_attr_hash_assign (crit, "propbogus", "foo");
  dump_hash (crit);
  TRY (odb_find (h, "ClassBar", crit, &oidlist) == CODB_RET_SUCCESS, "");
  dump_oidlist (oidlist);
  TRY (g_slist_length (oidlist) == 0, "");

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
