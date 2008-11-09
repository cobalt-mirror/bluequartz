/* $Id: odb_txn_indexes.c 3 2003-07-17 15:19:15Z will $
 *
 * Herein lie functions that deal with returning lists of object ids,
 * based on the fast-lookup indexes stored in the special "indexer"
 * object (OID 0).
 *
 * Yes, it's a hack, but if I dress it up real pretty, it'll look
 * like code, no?
 */

#include "odb_txn_internal.h"
#include <odb_transaction.h>

codb_ret
odb_txn_list_instances ( odb_txn txn, char *class, odb_oidlist *oidlist )
{
	char *indexname;
  odb_oid indexer;
  codb_ret ret;
  
  indexer.oid = 0; /* the indexer object is always oid 0 */
  
  indexname = malloc(6 + strlen(class) + 1);
  if (!indexname) { return CODB_RET_NOMEM; }

	strcpy (indexname, "CLASS=");
  strcat (indexname, class);  
  
  ret = odb_txn_list (txn, &indexer, indexname, oidlist);
  
  free(indexname);
  return ret;
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
