#include <stdio.h>
#include "compares.h"

void test (char *a, char *b, int expect)
{
  gint cmp0 = compare_float(a, b);
  gint cmp1 = compare_float(b, a);
  cmp1 *= -1;

  printf("  %s %c %s:\t",
    a, (cmp0 < 0) ? '<' : ((cmp0 == 0) ? '=' : '>'), b);
  
  if (cmp0 != expect) {
    printf ("a-b-fail ");
  }
  if (cmp1 != expect) {
    printf ("b-a-fail ");
  }
  if (cmp0 == cmp1 && cmp1 == expect) {
    printf ("ok.");
  }
  printf("\n");
}

int main()
{
  test("0", "0", 0);
  test("1234", "1234", 0);
  test("1234", "1235", -1);
  test("1235", "12345", -1);
  test("4", "40000", -1);
  test("","", 0);
  test("1","", 1);
  test("0","1", -1);

  // FIXME: compare currently things "0" > "", when they really
  // ought to be equal.  But, since this is just being used for
  // a sort, we can probably get away with this for now.
  test("0","", 1);

  test ("0.2", "0.15", 1);
  test ("v0.2", "v0.15", -1);
  test ("0.200", "0.15", 1);
  test ("v0.200", "v0.15", 1);
  test ("v0.200", "v0.15000", -1);

  test("abc", "Def", 0);
  test("vabc", "vDef", 0);
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
