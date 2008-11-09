#ifndef READCONFIG_H
#define READCONFIG_H

/*
 * Types of user handlers for config file assignments
 */
typedef void (*string_handler_t)(const char *keyword, const char *value);
typedef void (*number_handler_t)(const char *keyword, unsigned long value);
typedef void (*bool_handler_t)(const char *keyword, int flag);
  
/*
 * types of 'variables'
 */
typedef enum {
     VT_NUMBER,
     VT_STRING,
     VT_BOOL
} valtype_t;

/*
 * Set of handlers
 */

typedef struct {
     string_handler_t string_handler;
     number_handler_t number_handler;
     bool_handler_t bool_handler;
} handler_set_t;

/*
 * Data structure for storing information about config file keyword
 */
typedef struct {
     const char *keyword;
     valtype_t valtype;
     handler_set_t handlers;
} keyword_t;

/*
 * default handlers. They should never be called unless the software is buggy.
 */
extern void default_string_handler (const char *, const char *);
extern void default_number_handler (const char *, unsigned long);
extern void default_bool_handler (const char *, int);

/*
 * Macros used to specify a keyword table
 */
#define KW_STRING(KEYWORD,HANDLER) { KEYWORD, VT_STRING, {HANDLER, default_number_handler, default_bool_handler} }
#define KW_NUMBER(KEYWORD,HANDLER) { KEYWORD, VT_NUMBER, {default_string_handler, HANDLER, default_bool_handler} }
#define KW_BOOL(KEYWORD,HANDLER) { KEYWORD, VT_BOOL, {default_string_handler, default_number_handler, HANDLER} }

/*
 * The config file reader.
 * Parameters:
 *   input:        file to read configuration data from
 *   kwtable:      table specifying the legal keywords and their types
 *   tablesize:    number of entries in kwtable
 *   errormessage: buffer for error messages
 *   msgbuflen:    size of 'errormessage'
 */
int ReadConfig (FILE *input, keyword_t kwtable[], unsigned tablesize, char errormessage[], unsigned msgbuflen);

#endif /*READCONFIG_H*/
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
