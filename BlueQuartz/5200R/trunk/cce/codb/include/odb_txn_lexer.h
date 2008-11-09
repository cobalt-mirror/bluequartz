/* $Id: odb_txn_lexer.h 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */

#ifndef _CCE_TXN_PARSER_TOKENS_H_
#define _CCE_TXN_PARSER_TOKENS_H_ 1

#include <stdio.h>

typedef enum {
  TXN_TOK_BINSTR,
  TXN_TOK_QSTR,
  TXN_TOK_OPENTAG, /* 2 */
  TXN_TOK_CLOSETAGEND,
  TXN_TOK_CLOSETAG,
  TXN_TOK_EQUALS,
  TXN_TOK_TRANSACTION, /* 6 */
  TXN_TOK_ETRANSACTION,
  TXN_TOK_STATE,
  TXN_TOK_OBJECT,
  TXN_TOK_CLASS,
  TXN_TOK_SCALAR,
  TXN_TOK_ESCALAR,
  TXN_TOK_OID,
  TXN_TOK_PROP,
  TXN_TOK_LIST,
  TXN_TOK_ELIST,
  TXN_TOK_ADD,
  TXN_TOK_BEFORE,
  TXN_TOK_AFTER,
  TXN_TOK_RM,
  TXN_TOK_OTHER,
  TXN_TOK_WHITESPACE,
} txn_parser_token;

typedef struct txn_scanner_struct * txn_scanner;

txn_scanner      txn_scanner_new( FILE *f);
void             txn_scanner_destroy( txn_scanner s);
char *           txn_scanner_duptoken( txn_scanner s);
unsigned long	 txn_scanner_toktoul( txn_scanner s, int base);
txn_parser_token txn_scanner_scan(txn_scanner s);

#endif
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
