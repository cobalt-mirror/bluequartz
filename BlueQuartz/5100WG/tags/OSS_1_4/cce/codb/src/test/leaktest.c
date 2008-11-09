/* $Id: leaktest.c 3 2003-07-17 15:19:15Z will $
 *
 * Simple usage of odb object, for tracking down memory leaks.
 */
 
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <stdlib.h>
#include <stdio.h>
#include "debug.h"
#include <codb.h>

#include <libdebug.h>

void test1(void)
{
  
  fprintf(stderr,"test1 starting.\n");

  libdebug_open("./test1.mem");
  
  {
  	odb_handle *odb;
	GHashTable *attr;
	oid_t oid;
    TRY(odb = odb_handle_new(NULL), "");

	attr = g_hash_table_new(g_str_hash, g_str_equal);
	g_hash_table_insert(attr, "foo", "bar");
	g_hash_table_insert(attr, "pooh", "piglet");
	g_hash_table_insert(attr, "charlie", "snoopy");

	odb_create(odb, "Test", attr, &oid);

	odb_commit(odb);

	g_hash_table_destroy(attr);

    odb_handle_destroy(odb);
  }
  
  memdebug_dump();
  libdebug_close();

  fprintf(stderr,"test1 done.\n");
}

int
main ()
{
	test1();
  return 0;
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
