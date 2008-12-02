/* $Id: test1.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * Test1 for event dispatcher
 */

#include <cce_common.h>
#include <codb.h>
#include <cce_conf.h>
#include <cce_ed.h>
#include "debug.h"

void tp1()
{
	cce_conf *conf;
  codb_handle *odb;
  cce_ed *ed;

	fprintf(stderr,"\ntp1:\n");
  
  TRY(conf = cce_conf_get_configuration("./conf1", ""), "");
  TRY(odb = codb_handle_new("tmp", conf), "");
  TRY(ed = cce_ed_new(conf), "");

  cce_ed_destroy(ed);
  codb_handle_destroy(odb);
  cce_conf_destroy(conf);
}

void tp2()
{
	cce_conf *conf;
  codb_handle *odb;
  cce_ed *ed;
  
  GHashTable *attr;
  GHashTable *attrerrs = g_hash_table_new(g_str_hash, g_str_equal);
  oid_t oid;

	fprintf(stderr,"\ntp2:\n");
  
  TRY(conf = cce_conf_get_configuration("./conf1", ""), "");
  TRY(odb = codb_handle_new("tmp", conf), "");
  TRY(ed = cce_ed_new(conf), "");

	attr = codb_attr_hash_new();
  codb_attr_hash_assign(attr, "alpha", "beta");
  codb_attr_hash_assign(attr, "dog", "cat");
  codb_attr_hash_assign(attr, "linux", "penguin");
  
  codb_create(odb, "Testicle", attr, attrerrs, &oid);
  cce_ed_dispatch(ed, odb);
  
  codb_attr_hash_destroy(attr);

  cce_ed_destroy(ed);
  codb_handle_destroy(odb);
  cce_conf_destroy(conf);
}

int main()
{
	nologflag = 1;
	vflag = 1;
	tp1();
  tp2();
  
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
