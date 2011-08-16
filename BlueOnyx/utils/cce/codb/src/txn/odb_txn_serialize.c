/* $Id: odb_txn_serialize.c 259 2004-01-03 06:28:40Z shibuya $ */
/* Copyright 2001 Sun Microsystems, Inc.  All rights reserved. */
/*
 * serialize / unserialize a transaction object via a file descriptor
 */

#include <cce_common.h>
#include "odb_txn_internal.h"

void
GHFunc_serialize_object (gpointer key, gpointer val, gpointer data)
{
  char *oidstr;
  char *class;
  FILE *fd = (FILE *) data;
  oidstr = (char *) key;
  class = (char *) val;

  fprintf (fd, "\t<OBJECT OID=\"%s\" CLASS=\"%s\"/>\n", oidstr, class);
}

void
GHFunc_serialize_scalar (gpointer key, gpointer val, gpointer data)
{
  char *objprop_str;
  cce_scalar *sc;
  FILE *fd;
  odb_oid oid;
  char *prop;
  char *binstr;

  fd = (FILE *) data;
  objprop_str = (char *) key;
  sc = (cce_scalar *) val;

  str_to_objprop (objprop_str, &oid, &prop);
  binstr = cce_scalar_to_binstr (sc);
  if (!binstr) {
    binstr = "Error!";
  }

  fprintf (fd, "\t<SCALAR OID=\"%08lx\" PROP=\"%s\">\n\t\t%s\n\t</SCALAR>\n",
           oid.oid, prop, binstr);

  free (binstr);
}

void
serialize_listadd_event (FILE * fd, odb_event_listadd * event)
{
  fprintf (fd, "\t\t<ADD OID=\"%08lx\" %s=\"%08lx\"/>\n",
           event->oidval.oid,
           event->before ? "BEFORE" : "AFTER", event->other.oid);
}

void
serialize_listrm_event (FILE * fd, odb_event_listrm * event)
{
  fprintf (fd, "\t\t<RM OID=\"%08lx\"/>\n", event->oidval.oid);
}

void
GHFunc_serialize_list (gpointer key, gpointer val, gpointer data)
{
  FILE *fd;
  char *objprop_str;
  odb_oid oid;
  char *prop;
  GSList *eventlist;

  fd = (FILE *) data;

  objprop_str = (char *) key;
  str_to_objprop (objprop_str, &oid, &prop);

  eventlist = (GSList *) val;

  fprintf (fd, "\t<LIST OID=\"%08lx\" PROP=\"%s\">\n", oid.oid, prop);
  while (eventlist) {
    odb_event *event;
    event = (odb_event *) eventlist->data;
    switch (odb_event_type_read (event)) {
    case ODB_EVENT_LISTADD:
      serialize_listadd_event (fd, (odb_event_listadd *) event);
      break;
    case ODB_EVENT_LISTRM:
      serialize_listrm_event (fd, (odb_event_listrm *) event);
      break;
    case ODB_EVENT_NEW:
    case ODB_EVENT_SET:
    default:
      /* ignore */
      break;
    }
    eventlist = eventlist->next;
  }
  fprintf (fd, "\t</LIST>\n");
}

codb_ret odb_txn_serialize (odb_txn txn, FILE * write_fd)
{
  fprintf (write_fd, "<TRANSACTION STATE=\"%d\">\n", txn->state);

  /* serialize object creation events */
  g_hash_table_foreach (txn->objects, GHFunc_serialize_object, write_fd);

  /* serialize scalar change events */
  g_hash_table_foreach (txn->scalars, GHFunc_serialize_scalar, write_fd);

  /* serialize list change events */
  g_hash_table_foreach (txn->lists, GHFunc_serialize_list, write_fd);

  fprintf (write_fd, "</TRANSACTION>\n");
  return CODB_RET_SUCCESS;
}

/* macros to implement laziness in the unserialize fn */
#define FREESTR(x)   { if (x) { free(x); x=NULL; } }
#define FREESCALAR(x) { if (x) { cce_scalar_destroy(x); x=NULL; } }
#define SUC(x) ( x == CODB_RET_SUCCESS )

/* quicky "unquote string" fn to support laziness in the unserialize fn */
void
stripquotes (char *str)
{
  char *read, *write;
  read = str;
  write = str;
  if (*read == '\"')
    read++;
  while (*read != '\0') {
    if (*read == '\"') {
      break;
    }
    if (*read == '\\') {
      read++;
      if (*read == 'n')
        *read = '\n';
      if (*read == 't')
        *read = '\t';
      if (*read == 'r')
        *read = '\r';
      if (*read == '\0')
        break;
    }
    *write = *read;
    write++;
    read++;
  }
  *write = '\0';
}

/* odb_txn_unserialize
 *
 * Unserializes a transaction object from a file descriptor.
 *
 * Uses odb_txn_scanner to tokenize the bytestream.
 * Implements a simple state machine to parse the grammar (too simple
 * to bother with yacc).
 */
codb_ret odb_txn_unserialize (odb_txn txn, FILE * read_fd)
{
  txn_scanner scanner;
  txn_parser_token tok;
  odb_oid oid, oid2, other;
  char *str;
  cce_scalar *scalar;
  int before;
  enum
  {
    INIT,
    SUCCESS,
    FAILURE,
    TRAN1, TRAN2, TRAN3, TRAN4, TRAN5,
    EVENT1, EVENT2, ETRAN1,
    OBJECT1, OBJECT2, OBJECT3, OBJECT4, OBJECT5, OBJECT6, OBJECT7,
    SCALAR1, SCALAR2, SCALAR3, SCALAR4, SCALAR5, SCALAR6, SCALAR7,
    SCALAR8, SCALAR9, SCALAR10, SCALAR11,
    LIST1, LIST2, LIST3, LIST4, LIST5, LIST6, LIST7, LIST8,
    INLIST1, INLIST2, INLIST3,
    LISTADD1, LISTADD2, LISTADD3, LISTADD4, LISTADD5, LISTADD6, LISTADD7,
    LISTRM1, LISTRM2, LISTRM3, LISTRM4,
  }
  state, next;

  scanner = txn_scanner_new (read_fd);
  state = INIT;
  str = NULL;
  scalar = NULL;
  before = 0;

  while (state != SUCCESS && state != FAILURE) {
    tok = txn_scanner_scan (scanner);
    if (tok == TXN_TOK_WHITESPACE)
      continue;

/* just for debugging */
#ifdef DEBUG_CODB
    fprintf (stderr, "\nstate=%d=", state);
    switch (state) {
    case INIT:
      fprintf (stderr, "INIT");
      break;
    case SUCCESS:
      fprintf (stderr, "SUCCESS");
      break;
    case FAILURE:
      fprintf (stderr, "FAILURE");
      break;
    case TRAN1:
      fprintf (stderr, "TRAN1");
      break;
    case TRAN2:
      fprintf (stderr, "TRAN2");
      break;
    case TRAN3:
      fprintf (stderr, "TRAN3");
      break;
    case TRAN4:
      fprintf (stderr, "TRAN4");
      break;
    case TRAN5:
      fprintf (stderr, "TRAN5");
      break;
    case EVENT1:
      fprintf (stderr, "EVENT1");
      break;
    case EVENT2:
      fprintf (stderr, "EVENT2");
      break;
    case ETRAN1:
      fprintf (stderr, "ETRAN1");
      break;
    case OBJECT1:
      fprintf (stderr, "OBJECT1");
      break;
    case OBJECT2:
      fprintf (stderr, "OBJECT2");
      break;
    case OBJECT3:
      fprintf (stderr, "OBJECT3");
      break;
    case OBJECT4:
      fprintf (stderr, "OBJECT4");
      break;
    case OBJECT5:
      fprintf (stderr, "OBJECT5");
      break;
    case OBJECT6:
      fprintf (stderr, "OBJECT6");
      break;
    case OBJECT7:
      fprintf (stderr, "OBJECT7");
      break;
    case SCALAR1:
      fprintf (stderr, "SCALAR1");
      break;
    case SCALAR2:
      fprintf (stderr, "SCALAR2");
      break;
    case SCALAR3:
      fprintf (stderr, "SCALAR3");
      break;
    case SCALAR4:
      fprintf (stderr, "SCALAR4");
      break;
    case SCALAR5:
      fprintf (stderr, "SCALAR5");
      break;
    case SCALAR6:
      fprintf (stderr, "SCALAR6");
      break;
    case SCALAR7:
      fprintf (stderr, "SCALAR7");
      break;
    case SCALAR8:
      fprintf (stderr, "SCALAR8");
      break;
    case SCALAR9:
      fprintf (stderr, "SCALAR9");
      break;
    case SCALAR10:
      fprintf (stderr, "SCALAR10");
      break;
    case SCALAR11:
      fprintf (stderr, "SCALAR11");
      break;
    case LIST1:
      fprintf (stderr, "LIST1");
      break;
    case LIST2:
      fprintf (stderr, "LIST2");
      break;
    case LIST3:
      fprintf (stderr, "LIST3");
      break;
    case LIST4:
      fprintf (stderr, "LIST4");
      break;
    case LIST5:
      fprintf (stderr, "LIST5");
      break;
    case LIST6:
      fprintf (stderr, "LIST6");
      break;
    case LIST7:
      fprintf (stderr, "LIST7");
      break;
    case LIST8:
      fprintf (stderr, "LIST8");
      break;
    case INLIST1:
      fprintf (stderr, "INLIST1");
      break;
    case INLIST2:
      fprintf (stderr, "INLIST2");
      break;
    case INLIST3:
      fprintf (stderr, "INLIST3");
      break;
    case LISTADD1:
      fprintf (stderr, "LISTADD1");
      break;
    case LISTADD2:
      fprintf (stderr, "LISTADD2");
      break;
    case LISTADD3:
      fprintf (stderr, "LISTADD3");
      break;
    case LISTADD4:
      fprintf (stderr, "LISTADD4");
      break;
    case LISTADD5:
      fprintf (stderr, "LISTADD5");
      break;
    case LISTADD6:
      fprintf (stderr, "LISTADD6");
      break;
    case LISTADD7:
      fprintf (stderr, "LISTADD7");
      break;
    case LISTRM1:
      fprintf (stderr, "LISTRM1");
      break;
    case LISTRM2:
      fprintf (stderr, "LISTRM2");
      break;
    case LISTRM3:
      fprintf (stderr, "LISTRM3");
      break;
    case LISTRM4:
      fprintf (stderr, "LISTRM4");
      break;
    default:
      fprintf (stderr, "error");
      break;
    }
    fprintf (stderr, " tok=%d=", tok);
    switch (tok) {
    case TXN_TOK_BINSTR:
      fprintf (stderr, "TXN_TOK_BINSTR");
      break;
    case TXN_TOK_QSTR:
      fprintf (stderr, "TXN_TOK_QSTR");
      break;
    case TXN_TOK_OPENTAG:
      fprintf (stderr, "TXN_TOK_OPENTAG");
      break;
    case TXN_TOK_CLOSETAGEND:
      fprintf (stderr, "TXN_TOK_CLOSETAGEND");
      break;
    case TXN_TOK_CLOSETAG:
      fprintf (stderr, "TXN_TOK_CLOSETAG");
      break;
    case TXN_TOK_EQUALS:
      fprintf (stderr, "TXN_TOK_EQUALS");
      break;
    case TXN_TOK_TRANSACTION:
      fprintf (stderr, "TXN_TOK_TRANSACTION");
      break;
    case TXN_TOK_ETRANSACTION:
      fprintf (stderr, "TXN_TOK_ETRANSACTION");
      break;
    case TXN_TOK_STATE:
      fprintf (stderr, "TXN_TOK_STATE");
      break;
    case TXN_TOK_OBJECT:
      fprintf (stderr, "TXN_TOK_OBJECT");
      break;
    case TXN_TOK_CLASS:
      fprintf (stderr, "TXN_TOK_CLASS");
      break;
    case TXN_TOK_SCALAR:
      fprintf (stderr, "TXN_TOK_SCALAR");
      break;
    case TXN_TOK_ESCALAR:
      fprintf (stderr, "TXN_TOK_ESCALAR");
      break;
    case TXN_TOK_OID:
      fprintf (stderr, "TXN_TOK_OID");
      break;
    case TXN_TOK_PROP:
      fprintf (stderr, "TXN_TOK_PROP");
      break;
    case TXN_TOK_LIST:
      fprintf (stderr, "TXN_TOK_LIST");
      break;
    case TXN_TOK_ELIST:
      fprintf (stderr, "TXN_TOK_ELIST");
      break;
    case TXN_TOK_ADD:
      fprintf (stderr, "TXN_TOK_ADD");
      break;
    case TXN_TOK_BEFORE:
      fprintf (stderr, "TXN_TOK_BEFORE");
      break;
    case TXN_TOK_AFTER:
      fprintf (stderr, "TXN_TOK_AFTER");
      break;
    case TXN_TOK_RM:
      fprintf (stderr, "TXN_TOK_RM");
      break;
    case TXN_TOK_OTHER:
      fprintf (stderr, "TXN_TOK_OTHER");
      break;
    case TXN_TOK_WHITESPACE:
      fprintf (stderr, "TXN_TOK_WHITESPACE");
      break;
    default:
      fprintf (stderr, "error");
      break;
    }
#endif

    next = FAILURE;              /* default for every state */
    switch (state) {
      /* < */
    case INIT:
      if (tok == TXN_TOK_OPENTAG)
        next = TRAN1;
      break;

      /* TRANSACTION STATE="0"> */
    case TRAN1:
      if (tok == TXN_TOK_TRANSACTION)
        next = TRAN2;
      break;
    case TRAN2:
      if (tok == TXN_TOK_STATE)
        next = TRAN3;
      break;
    case TRAN3:
      if (tok == TXN_TOK_EQUALS)
        next = TRAN4;
      break;
    case TRAN4:
      if (tok == TXN_TOK_QSTR) {
        txn->state = txn_scanner_toktoul (scanner, 10);
        next = TRAN5;
      }
      break;
    case TRAN5:
      if (tok == TXN_TOK_CLOSETAG)
        next = EVENT1;
      break;

      /* <OBJECT       */
      /* <SCALAR       */
      /* <LIST         */
      /* </TRANSACTION */
    case EVENT1:
      if (tok == TXN_TOK_OPENTAG)
        next = EVENT2;
      break;
    case EVENT2:
      if (tok == TXN_TOK_OBJECT)
        next = OBJECT1;
      if (tok == TXN_TOK_SCALAR)
        next = SCALAR1;
      if (tok == TXN_TOK_LIST)
        next = LIST1;
      if (tok == TXN_TOK_ETRANSACTION)
        next = ETRAN1;
      break;

      /* > */
    case ETRAN1:
      if (tok == TXN_TOK_CLOSETAG)
        next = SUCCESS;
      break;

      /* OID="0000001" CLASS="classname"> */
    case OBJECT1:
      if (tok == TXN_TOK_OID)
        next = OBJECT2;
      break;
    case OBJECT2:
      if (tok == TXN_TOK_EQUALS)
        next = OBJECT3;
      break;
    case OBJECT3:
      if (tok == TXN_TOK_QSTR) {
        oid.oid = txn_scanner_toktoul (scanner, 16);
        next = OBJECT4;
      }
      break;
    case OBJECT4:
      if (tok == TXN_TOK_CLASS)
        next = OBJECT5;
      break;
    case OBJECT5:
      if (tok == TXN_TOK_EQUALS)
        next = OBJECT6;
      break;
    case OBJECT6:
      if (tok == TXN_TOK_QSTR) {
        FREESTR (str);
        str = txn_scanner_duptoken (scanner);
        stripquotes (str);
        next = OBJECT7;
      }
      break;
    case OBJECT7:
      if (tok == TXN_TOK_CLOSETAGEND) {
        if SUC
          (odb_txn_createobj (txn, &oid, str))
            next = EVENT1;
        FREESTR (str);
      }
      break;

      /* OID="00000001" PROP="property"> #4#binstr== </SCALAR> */
    case SCALAR1:
      if (tok == TXN_TOK_OID)
        next = SCALAR2;
      break;
    case SCALAR2:
      if (tok == TXN_TOK_EQUALS)
        next = SCALAR3;
      break;
    case SCALAR3:
      if (tok == TXN_TOK_QSTR) {
        oid.oid = txn_scanner_toktoul (scanner, 16);
        next = SCALAR4;
      }
      break;
    case SCALAR4:
      if (tok == TXN_TOK_PROP)
        next = SCALAR5;
      break;
    case SCALAR5:
      if (tok == TXN_TOK_EQUALS)
        next = SCALAR6;
      break;
    case SCALAR6:
      if (tok == TXN_TOK_QSTR) {
        FREESTR (str);
        str = txn_scanner_duptoken (scanner);
        stripquotes (str);
        next = SCALAR7;
      }
      break;
    case SCALAR7:
      if (tok == TXN_TOK_CLOSETAG)
        next = SCALAR8;
      break;
    case SCALAR8:
      if (tok == TXN_TOK_BINSTR) {
        char *tempstr;
        FREESCALAR (scalar);
        tempstr = txn_scanner_duptoken (scanner);
        scalar = cce_scalar_new_from_binstr (tempstr);
        free (tempstr);
        next = SCALAR9;
      }
      break;
    case SCALAR9:
      if (tok == TXN_TOK_OPENTAG)
        next = SCALAR10;
      break;
    case SCALAR10:
      if (tok == TXN_TOK_ESCALAR)
        next = SCALAR11;
      break;
    case SCALAR11:
      if (tok == TXN_TOK_CLOSETAG) {
        if SUC
          (odb_txn_set (txn, &oid, str, scalar))
            next = EVENT1;
        FREESTR (str);
        FREESCALAR (scalar);
      }
      break;

      /* OID="00000003" PROP="property"> */
    case LIST1:
    case LIST2:
      if (tok == TXN_TOK_OID)
        next = LIST3;
      break;
    case LIST3:
      if (tok == TXN_TOK_EQUALS)
        next = LIST4;
      break;
    case LIST4:
      if (tok == TXN_TOK_QSTR) {
        oid.oid = txn_scanner_toktoul (scanner, 16);
        next = LIST5;
      }
      break;
    case LIST5:
      if (tok == TXN_TOK_PROP)
        next = LIST6;
      break;
    case LIST6:
      if (tok == TXN_TOK_EQUALS)
        next = LIST7;
      break;
    case LIST7:
      if (tok == TXN_TOK_QSTR) {
        FREESTR (str);
        str = txn_scanner_duptoken (scanner);
        stripquotes (str);
        next = LIST8;
      }
      break;
    case LIST8:
      if (tok == TXN_TOK_CLOSETAG)
        next = INLIST1;
      break;

      /* <ADD ... > */
      /* <RM ... > */
      /* </LIST> */
    case INLIST1:
      if (tok == TXN_TOK_OPENTAG)
        next = INLIST2;
      break;
    case INLIST2:
      if (tok == TXN_TOK_ADD)
        next = LISTADD1;
      if (tok == TXN_TOK_RM)
        next = LISTRM1;
      if (tok == TXN_TOK_ELIST)
        next = INLIST3;
      break;
    case INLIST3:
      if (tok == TXN_TOK_CLOSETAG)
        next = EVENT1;
      break;

      /* OID="00000005" BEFORE="00000006"/> */
      /* OID="00000005" AFTER="00000006"/> */
    case LISTADD1:
      if (tok == TXN_TOK_OID)
        next = LISTADD2;
      break;
    case LISTADD2:
      if (tok == TXN_TOK_EQUALS)
        next = LISTADD3;
      break;
    case LISTADD3:
      if (tok == TXN_TOK_QSTR) {
        oid2.oid = txn_scanner_toktoul (scanner, 16);
        next = LISTADD4;
      }
      break;
    case LISTADD4:
      if (tok == TXN_TOK_AFTER) {
        before = 0;
        next = LISTADD5;
      }
      if (tok == TXN_TOK_BEFORE) {
        before = 1;
        next = LISTADD5;
      }
      break;
    case LISTADD5:
      if (tok == TXN_TOK_EQUALS)
        next = LISTADD6;
      break;
    case LISTADD6:
      if (tok == TXN_TOK_QSTR) {
        other.oid = txn_scanner_toktoul (scanner, 16);
        next = LISTADD7;
      }
      break;
    case LISTADD7:
      if (tok == TXN_TOK_CLOSETAGEND) {
        if SUC
          (odb_txn_listadd (txn, &oid, str, &oid2, before, &other))
            next = INLIST1;
      }
      break;

      /* OID="00000005"> */
    case LISTRM1:
      if (tok == TXN_TOK_OID)
        next = LISTRM2;
      break;
    case LISTRM2:
      if (tok == TXN_TOK_EQUALS)
        next = LISTRM3;
      break;
    case LISTRM3:
      if (tok == TXN_TOK_QSTR) {
        oid2.oid = txn_scanner_toktoul (scanner, 16);
        next = LISTRM4;
      }
      break;
    case LISTRM4:
      if (tok == TXN_TOK_CLOSETAGEND) {
        if SUC
          (odb_txn_listrm (txn, &oid, str, &oid2))
            next = INLIST1;
      }
      break;

    default:
      /* foo! */
      break;
    }
    state = next;
  }

  /* cleanup */
  FREESTR (str);
  FREESCALAR (scalar);

  return (state == SUCCESS) ? CODB_RET_SUCCESS : CODB_RET_OTHER;
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
