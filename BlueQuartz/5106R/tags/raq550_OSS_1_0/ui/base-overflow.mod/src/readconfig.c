#include <stdio.h>
#include <stddef.h>
#include <stdlib.h>
#include <stdint.h>
#include <stdarg.h>
#include <string.h>
#include <ctype.h>

#include "readconfig.h"

/*
 * GNU C inline directive
 */
#define INLINE static __inline__

/*
 * Scanner forward declaration
 */
typedef struct scanner *PSCAN;

/*
 * Error reporting routine
 */
static void ScanError (PSCAN ps, const char *, ...);

/*
 * the different types of token in the input
 */
typedef enum {
     TOK_EOF,			/* EOF */
     TOK_ERROR,			/* error token. Used to stop further parsing */
     TOK_NEWLINE,		/* newlines are tokens */
     TOK_NUMBER,		/* positive numbers */
     TOK_STRING,		/* string values. strings are not quoted in the input */
     TOK_EQUALS,		/* the '=' character */
     TOK_KEYWORD,		/* any keyword */
     TOK_FALSE,			/* boolean value */
     TOK_TRUE,			/* boolean value */
     TOK_JUNK			/* anything else */
} token_t;

/*
 * Keywords are looked up in a hash table, just in case we have a lot of them.
 * Entries in this table contain all the information about a keyword
 */
typedef struct kw_entry *PKWENTRY;

struct kw_entry
{
     PKWENTRY next;		/* next entry in hash chain */
     token_t tokentype;		/* token type, implements special keywords */
     valtype_t valtype;		/* type of keyword (expects string or number */
     handler_set_t handlers;	/* the value handlers */
     char string[1];		/* the actual literal */
};

/*
 * The size of the hash table.
 */
enum { HASHMODULUS = 31 };

/*
 * Create a new (keyword) hash table entry
 */
static PKWENTRY CreateHashNode (PSCAN ps, token_t tokentype, valtype_t valtype, 
				handler_set_t handlers, const char *literal)
{
     unsigned len = strlen(literal);
     PKWENTRY ph = (PKWENTRY)malloc(sizeof(struct kw_entry)+len);
     if(ph == NULL){
	  ScanError(ps, "Out of memory");
	  return NULL;
     };
     ph->next = NULL;
     ph->tokentype = tokentype;
     ph->valtype = valtype;
     ph->handlers = handlers;
     strcpy(ph->string, literal); /* for once this is safe... */
     return ph;
}

/*
 * Hash a literal string to a number h,  0 <= h < HASHMODULUS
 */
static unsigned HashLiteral (const char *l)
{
     uint32_t h = 0;
     char c;
     while((c = *l++) != '\0')
	  h =  ((h << 4) ^ (uint32_t)(c & 0xff));
     return h % HASHMODULUS;
}

/*
 * The data structure used by the scanner
 */
struct scanner
{
     FILE *input;		/* input file */
     int nextchar;		/* unused character */
     unsigned long lex_cpos, lex_lpos; /* the lexical position of nextchar */

     char *token;		/* buffer containing last-scanned token */
     size_t tokbuflen, toklen;	/* length of token buffer and actual token */
     unsigned long token_cpos, token_lpos; /* lexical position of last-scanned token */
     token_t tokentype;		/* type of token */
     unsigned long numvalue;	/* numeric value (tokentype == TOK_NUMBER) */
     PKWENTRY kwref;		/* reference to hash table entry (tokentype == TOK_KEYWORD) */
     PKWENTRY hashtable[HASHMODULUS]; /* the hash table */
     char *messagebuf;		/* error message buffer */
     unsigned messagebuflen;	/* and length */
};

/*
 * Read next char, and update lex_cpos and lex_lpos
 */
static int ReadNextChar (PSCAN ps)
{
     switch(ps->nextchar){
     case '\t':
	  ps->lex_cpos = (ps->lex_cpos + 7) / 8 * 8+1;
	  break;
     case '\r':
	  ps->lex_cpos = 1;
	  break;
     case '\n':
	  ps->lex_cpos = 1;
	  ps->lex_lpos++;
	  break;
     case EOF:
	  break;
     default:
	  ps->lex_cpos++;
     };
     return (ps->nextchar = getc(ps->input));
}

/*
 * Expand the token buffer
 */
static int GrowToken (PSCAN ps, size_t newlen)
{
     if(newlen > ps->tokbuflen){
	  char *newbuf = (char *)malloc(newlen);
	  if(newbuf == NULL){
	       ScanError(ps, "Out of memory");
	       return -1;
	  };
	  memcpy(newbuf, ps->token, ps->toklen);
	  if(ps->token != NULL)
	       free(ps->token);
	  ps->token = newbuf;
	  ps->tokbuflen = newlen;
     };
     return 0;
}

/*
 * Add a character to the token buffer
 */
INLINE void Add2Token (PSCAN ps, char c)
{
     if(ps->toklen < ps->toklen || GrowToken(ps, 2*ps->toklen+100) >= 0)
	  ps->token[ps->toklen++] = c;
}

/*
 * Insert a new keyword in the hash table
 */
static void InsertKeyword (PSCAN ps, const char *keyword, token_t tokentype, valtype_t valtype, 
			   handler_set_t handlers)
{
     unsigned h = HashLiteral(keyword);
     PKWENTRY p, *pp = &ps->hashtable[h];
     while((p = *pp) != NULL)
	  pp = &p->next;
     *pp = CreateHashNode(ps, tokentype, valtype, handlers, keyword);
}

/*
 * Skip white space (and comments if skip_comments is true)
 */
static int SkipWhite (PSCAN ps, int skip_comments)
{
     int nextchar = ps->nextchar;

     /* skip whitespace */
     for(;;){
	  switch(nextchar){
	  case ' ':
	       break;
	  case '\t':
	       break;
	  case '\r':
	       break;
	  case '\f': case '\v':
	       break;
	  case '#':
	       if(skip_comments){
		    /* skip comments */
		    do {
			 nextchar = ReadNextChar(ps);
		    }while(nextchar != EOF && nextchar != '\n');
	       };
	       /* fall-thru */
	  default:
	       return nextchar;
	  };
	  nextchar = ReadNextChar(ps);
     }
}

/*
 * Scan the next token
 */
static void Scan (PSCAN ps)
{
     int nextchar;

     /* if we've seen an error we immediately quit */
     if(ps->tokentype == TOK_ERROR)
	  return;

     /* skip whitespace */
     nextchar = SkipWhite(ps, 1);

     /* we have a non-whitespace character (or EOF) */
     ps->token_cpos = ps->lex_cpos;
     ps->token_lpos = ps->lex_lpos;
     ps->toklen = 0;

     
     switch(nextchar){
     case EOF:
	  ps->tokentype = TOK_EOF;
	  Add2Token(ps, '\0');
	  return;
     case '\n':
	  ps->tokentype = TOK_NEWLINE;
	  (void)ReadNextChar(ps);
	  Add2Token(ps, '\0');
	  return;
     case '=':
	  ps->tokentype = TOK_EQUALS;
	  nextchar = ReadNextChar(ps);
	  Add2Token(ps, '=');
	  Add2Token(ps, '\0');
	  break;
     default:
	  if(isalpha(nextchar)){
	       unsigned hash;
	       PKWENTRY p, *pp;
	       do {
		    Add2Token(ps, nextchar);
		    nextchar = ReadNextChar(ps);
	       }while(nextchar != EOF && (isalnum(nextchar) || nextchar == '_'));
	       Add2Token(ps, '\0');
	       hash = HashLiteral(ps->token);
	       pp = &ps->hashtable[hash];
	       while((p = *pp) != NULL && strcmp(ps->token, p->string) != 0)
		    pp = &p->next;
	       if(p == NULL){
		    ps->tokentype = TOK_JUNK;
	       }else{
		    ps->tokentype = p->tokentype;
		    ps->kwref = p;
	       }
	  }else if(isdigit(nextchar)){
	       unsigned long v = 0;
	       ps->tokentype = TOK_NUMBER;
	       do {
		    v = v * 10 + (unsigned)(nextchar - '0');
		    Add2Token(ps, nextchar);
		    nextchar = ReadNextChar(ps);
	       }while(nextchar != EOF && isdigit(nextchar));
	       ps->numvalue = v;
	       Add2Token(ps, '\0');
	  }else{
	       ps->tokentype = TOK_JUNK;
	       Add2Token(ps, nextchar);
	       Add2Token(ps, '\0');
	       nextchar = ReadNextChar(ps);
	  }
     }
}

/*
 * Read a string
 * This routine is only called to read the right-hand side of 
 * a string-valued configuration parameter
 */
static void ScanString (PSCAN ps)
{
     int nextchar;

     /* if we've seen an error we immediately quit */
     if(ps->tokentype == TOK_ERROR)
	  return;
     
     /* skip whitespace */
     nextchar = SkipWhite(ps, 0);

     /* we have a non-whitespace character (or EOF) */
     ps->token_cpos = ps->lex_cpos;
     ps->token_lpos = ps->lex_lpos;
     ps->toklen = 0;

     ps->tokentype = TOK_STRING;
     while(nextchar != EOF && nextchar != '\n'){
	  Add2Token(ps, nextchar);
	  nextchar = ReadNextChar(ps);
     };
     while(ps->toklen > 0 && isspace(ps->token[ps->toklen-1]))
	  ps->toklen--;
     Add2Token(ps, '\0');
}

/*
 * Initialize the scanner
 */     
static int InitScanner (PSCAN ps, FILE *input, keyword_t kwtable[], unsigned size, char messagebuf[], unsigned messagebuflen)
{
     unsigned i;
     static handler_set_t dummy_handlers = {default_string_handler, default_number_handler, default_bool_handler};
     ps->input = input;
     ps->nextchar = '\n';
     ps->tokentype = TOK_NEWLINE;
     ps->token = 0;
     ps->tokbuflen = 0;
     ps->toklen = 0;
     ps->lex_cpos = ps->lex_lpos = 0;
     ps->messagebuf = messagebuf;
     ps->messagebuflen = messagebuflen;
     memset(ps->hashtable, 0, sizeof(ps->hashtable));
     for(i = 0; i < size; i++){
	  keyword_t *kp = &kwtable[i];
	  InsertKeyword(ps, kp->keyword, TOK_KEYWORD, kp->valtype, kp->handlers);
     };
     InsertKeyword(ps, "no", TOK_FALSE, VT_NUMBER, dummy_handlers);
     InsertKeyword(ps, "false", TOK_FALSE, VT_NUMBER, dummy_handlers);
     InsertKeyword(ps, "yes", TOK_TRUE, VT_NUMBER, dummy_handlers);
     InsertKeyword(ps, "true", TOK_TRUE, VT_NUMBER, dummy_handlers);
     (void)ReadNextChar(ps);
     Scan(ps);
     return 0;
}

/*
 * Free the dynamic memory used by the scanner
 */
static int CloseScanner (PSCAN ps)
{
     unsigned i;
     if(ps->token != NULL)
	  free(ps->token);
     for(i = 0; i < HASHMODULUS; i++){
	  PKWENTRY p = ps->hashtable[i];
	  while(p != NULL){
	       PKWENTRY n  = p->next;
	       free(p);
	       p = n;
	  }
     };
     return 0;
}

/*
 * Scanner error routine
 */
static void ScanError (PSCAN ps, const char *fmt, ...)
{
     if(ps->tokentype != TOK_ERROR){
	  va_list vl;
	  size_t l;
	  snprintf(ps->messagebuf, ps->messagebuflen, "line %lu: ", ps->token_lpos);
	  l = strlen(ps->messagebuf);
	  va_start(vl, fmt);
	  (void)vsnprintf(&ps->messagebuf[l], ps->messagebuflen-l, fmt, vl);
	  va_end(vl);
	  ps->tokentype = TOK_ERROR;
     }
}

/* pointer to current scanner */
static PSCAN current_ps;

/* 
 * Config file parser
 */
int ReadConfig (FILE *input, keyword_t kwtable[], unsigned tablesize, char *errormessage, unsigned messagelen)
{
     struct scanner scan;
     PKWENTRY kwref;
     PSCAN old_ps = current_ps;	/* save the old global scanner pointer */
     current_ps = &scan;	/* store the new pointer */

     /* set up the scanner */
     InitScanner(&scan, input, kwtable, tablesize, errormessage, messagelen);
     for(;;){
	  /* read away all the newlines */
	  while(scan.tokentype == TOK_NEWLINE)
	       Scan(&scan);

	  /* if we're at an EOF or ERROR we're done */
	  if(scan.tokentype == TOK_EOF || scan.tokentype == TOK_ERROR)
	       break;

	  /* we need a keyword here */
	  if(scan.tokentype != TOK_KEYWORD){
	       ScanError(&scan, "expected a keyword, got \"%s\"", scan.token);
	       break;
	  };
	  kwref = scan.kwref;	/* save the keyword reference */
	  Scan(&scan);		/* next token */

	  /* this token must be an '=' */
	  if(scan.tokentype != TOK_EQUALS){
	       ScanError(&scan, "expected an equals sign (=), got \"%s\"", scan.token);
	       break;
	  };

	  /* depending on the keyword we're going to use different scanners to 
	     parse the right hand side. This is necessary so we can use random unquoted strings */
	  switch(kwref->valtype){
	  case VT_STRING:	/* string-valued RHS */
	       ScanString(&scan);
	       if(scan.tokentype == TOK_STRING){
		    kwref->handlers.string_handler(kwref->string, scan.token);
	       }else{
		    ScanError(&scan, "only string values are legal for \"%s\"", kwref->string);
	       };
	       break;

	  case VT_NUMBER:	/* number-valued RHS */
	       Scan(&scan);
	       switch(scan.tokentype){
	       case TOK_NUMBER:
		    kwref->handlers.number_handler(kwref->string, scan.numvalue);
		    break;
	       default:
		    ScanError(&scan, "only number values are legal for \"%s\"", kwref->string);
	       };
	       break;

	  case VT_BOOL:
	       Scan(&scan);
	       switch(scan.tokentype){
	       case TOK_TRUE:
		    kwref->handlers.bool_handler(kwref->string, 1);
		    break;
	       case TOK_FALSE:
		    kwref->handlers.bool_handler(kwref->string, 0);
		    break;
	       case TOK_NUMBER:
		    if(scan.numvalue == 0 || scan.numvalue == 1){
			 kwref->handlers.bool_handler(kwref->string, scan.numvalue > 0);
			 break;
		    };
	       default:
		    ScanError(&scan, "only boolean values (0, 1, no, yes, false and true) are legal for \"%s\"", kwref->string);
	       };
	       break;
	       
	  default:		/* anything else is wrong */
	       ScanError(&scan, "bad keyword type detected in keyword %s", kwref->string);
	  };
	  Scan(&scan);		/* skip the token */
     };

     /* cleanup */
     CloseScanner(&scan);	/* release all the dynamic memory */
     current_ps = old_ps;	/* restore the scanner pointer */

     /* return -1 if error */
     return (scan.tokentype == TOK_ERROR ? -1 : 0);
}

/*
 * Default handers. They should not be called unless there's a bug.
 */
void default_string_handler (const char *keyword, const char *string)
{
     ScanError(current_ps, "attempt to assign string value to %s", keyword);
}

void default_number_handler (const char *keyword, unsigned long value)
{
     ScanError(current_ps, "attempt to assign numeric value to %s", keyword);
}

void default_bool_handler (const char *keyword, int flag)
{
     ScanError(current_ps, "attempt to assign boolean value to %s", keyword);
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
