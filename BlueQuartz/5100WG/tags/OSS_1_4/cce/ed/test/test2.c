/* $Id: test2.c 3 2003-07-17 15:19:15Z will $
 *
 * Test1 for event dispatcher
 */

#include <codb.h>
#include <cce_conf.h>
#include <cce_ed.h>
#include "debug.h"
#include <libdebug.h>

void dump_handlers(GSList *list)
{
	GSList *cursor;
  int cnt = 0;
  for (cursor = list; cursor; cursor = g_slist_next(cursor))
  {
  	cnt++;
  	fprintf(stderr, "  %s:%s\n", 
    	cce_conf_handler_type((cce_conf_handler*)cursor->data),
    	cce_conf_handler_data((cce_conf_handler*)cursor->data) );
  }
  fprintf(stderr, "total handlers in list: %d\n", cnt);
}

void tp1(char *dir)
{
	cce_conf *conf;
  odb_handle *odb;
  cce_ed *ed;
  
  GHashTable *attr, *attr2;
  GSList *handlers;
  oid_t oid;

	fprintf(stderr,"\ntp2:\n");
  
  libdebug_open("./test2.memdump");
  
  TRY(conf = cce_conf_get_configuration(dir), "");
  TRY(odb = odb_handle_new(conf), "");
  TRY(ed = cce_ed_new(conf), "");

	TRY(handlers = cce_conf_get_handlers(conf, "Testicle", "", "_CREATE"), "");
  dump_handlers(handlers);

	attr = odb_attr_hash_new();
  odb_attr_hash_assign(attr, "alpha", "beta");
  odb_attr_hash_assign(attr, "dog", "cat");
  odb_attr_hash_assign(attr, "linux", "penguin");

	attr2 = odb_attr_hash_new();
  odb_attr_hash_assign(attr2, "alpha", "gamma");
  odb_attr_hash_assign(attr2, "dog", "kyra");
  odb_attr_hash_assign(attr2, "linux", "leenooks");
 
  fprintf(stderr,"*************** creating object **********\n"); 
  odb_create(odb, "Testicle", attr, &oid);
  cce_ed_dispatch(ed, odb);
 
  fprintf(stderr,"**************** modifying object *******\n"); 
  odb_set(odb, oid, "", attr2);
  cce_ed_dispatch(ed, odb);

	fprintf(stderr,"********** destroying object %ld *******\n", (long)oid);
	odb_destroy(odb, oid);
	cce_ed_dispatch(ed, odb);

	odb_attr_hash_destroy(attr2);  
  odb_attr_hash_destroy(attr);

  cce_ed_destroy(ed);
  odb_handle_destroy(odb);
  cce_conf_destroy(conf);
  
  memdebug_dump();
  libdebug_close();
}

int main(int argc, char *argv[] )
{
	char *dir;

	if (argc > 1) {
		dir = argv[1];
	} else {
		dir = "./conf2";
	}
	tp1(dir);
  
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
