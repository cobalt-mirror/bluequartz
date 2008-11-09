#include <libdebug.h>

#include <stdio.h>
#include <cce_scalar.h>
#include <sys/stat.h>
#include <unistd.h>
#include <stdlib.h>


int errors = 0;
int tests = 0;

#define TRY(expr, f, a...)                                  \
  { tests++;                                                \
    fprintf(stderr, "\ntest%03d: %-60.60s  -> ", tests, #expr);    \
    if (!(expr)) { errors++;                                \
               fprintf(stderr, "FAILED " f , ##a ); }   \
    else { fprintf(stderr, "ok."); }                      \
  }


int test1()
{
  cce_scalar *s1, *s2, *s3, *s4;
  char *str1, *str2, *str3, *str4;
  char binptr[] = {0, 1, 2, 3, 4};

  /* initialize */  
  s1 = cce_scalar_new_from_str("alpha4num");
  s2 = cce_scalar_new_from_str("another \"string\"\nwith quotes");
  s3 = cce_scalar_new_from_bin(binptr, 5);
  s4 = cce_scalar_new_from_binstr("#7#aabbcc================");
  TRY ( s1 && s2 && s3 && s4, "couldn't create scalars" );
  str1 = cce_scalar_to_str(s1);
  str2 = cce_scalar_to_str(s2);
  str3 = cce_scalar_to_str(s3);
  str4 = cce_scalar_to_str(s4);
  TRY ( str1 && str2 && str3 && str4, "couldn't create strings" );
  
  /* compare */
  TRY(strcmp(str1, "\"alpha4num\"") == 0, "");
  TRY(strcmp(str2, "\"another \\\"string\\\"\\nwith quotes\"") == 0, "");
  TRY(strcmp(str3, "#5#AAECAwQA") == 0, "broken binstr");
  TRY(strcmp(str4, "#7#aabbccAAAAAA") == 0, "broken binstr");

  /* cleanup */  
  free(str1);
  free(str2);
  free(str3);
  free(str4);
  cce_scalar_destroy(s1);
  cce_scalar_destroy(s2);
  cce_scalar_destroy(s3);
  cce_scalar_destroy(s4);

  return 0;
}  

int main(int argc, char *argv[0])
{
	cce_scalar *s;
	cce_scalar *s2;
	char binptr[] = {0, 1, 2, 3, 4};
	struct stat buf;
	char buffer[1024];
	char *b64str;
	
	TRY(s = cce_scalar_new_undef(), "");
  TRY(!cce_scalar_isdefined(s), "isn't undef");

	TRY(s = cce_scalar_new(10), "");

	sprintf((char *)s->data, "This is 10");
  TRY(strcmp("This is 10", (char *)s->data) == 0, "strings do not match");

	TRY(s = cce_scalar_resize(s, 11), "");

	sprintf((char *)s->data, "This is 100");
  TRY(strcmp("This is 100", (char *)s->data)==0,"");

	cce_scalar_undefine(s);
  TRY (!s->data, "undefine didn't");

	/* can't test this, really */
	cce_scalar_destroy(s);

	TRY (s = cce_scalar_new_from_str("blah"), "");
  TRY (strcmp("blah", (char *)s->data) == 0, "");
		
	TRY(s = cce_scalar_new_from_qstr("\"blah\n\""), "");
  TRY (strcmp("blah\n", (char *)s->data) == 0, "");

	TRY(s = cce_scalar_new_from_qstr("\"blah\\n\""), "");
  TRY (strcmp("blah\n", (char *)s->data) == 0, "");

	TRY(s = cce_scalar_new_from_bin(binptr, 5), "");

	TRY (((char *)s->data)[0] == 0
	 && ((char *)s->data)[1] == 1
	 && ((char *)s->data)[2] == 2
	 && ((char *)s->data)[3] == 3
	 && ((char *)s->data)[4] == 4, "wrong data");

	TRY (s2 = cce_scalar_dup(s), "");
  TRY (s->length == s2->length && memcmp(s->data, s2->data, s->length) == 0,
    "wrong data");
	
	stat(argv[0], &buf);
	TRY (s = cce_scalar_new_from_file(argv[0]), "");
  TRY (s->length == buf.st_size, "wrong data size");

	snprintf(buffer, 1023, "%s.testwrite", argv[0]);
  TRY (cce_scalar_to_file(s, buffer) == 0, "");

	stat(buffer, &buf);
	unlink(buffer);

  TRY (s->length == buf.st_size, "wrote wrong-sized data");
	
  cce_scalar_destroy(s);
	TRY (s = cce_scalar_new_from_binstr("#11#aGVsbG8gd29ybGQA"), "");
  TRY (strcmp(s->data, "hello world") == 0, "wrong data");

  cce_scalar_destroy(s);
	TRY (s = cce_scalar_new_from_str("hello world"), "");
	TRY (b64str = cce_scalar_to_binstr(s), "");
  TRY (strcmp(b64str, "#11#aGVsbG8gd29ybGQA")==0, "bad b64 encoding");
  free(b64str);
  	
	cce_scalar_destroy(s);

  test1();

  fprintf(stderr, "\n%d errors, %d tests.\n", errors, tests);
  memdebug_dump();
  return errors;
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
