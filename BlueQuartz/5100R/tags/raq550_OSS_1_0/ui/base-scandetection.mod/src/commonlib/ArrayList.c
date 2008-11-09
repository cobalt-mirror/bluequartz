/*

$Header: /home/cvs/base-scandetection.mod/src/commonlib/ArrayList.c,v 1.1 2001/09/18 16:59:36 jthrowe Exp $

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/ArrayList.c,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  04-May-2000
    Originating Author :  Brian Adkins

      Last Modified by :  $Author: jthrowe $
    Date Last Modified :  $Date: 2001/09/18 16:59:36 $

   **********************************************************************

   Copyright (c) 2000 Progressive Systems Inc.
   All rights reserved.

   This code is confidential property of Progressive Systems Inc.  The
   algorithms, methods and software used herein may not be duplicated or
   disclosed to any party without the express written consent from
   Progressive Systems Inc.

   Progressive Systems Inc. makes no representations concerning either
   the merchantability of this software or the suitability of this
   software for any particular purpose.

   These notices must be retained in any copies of any part of this
   documentation and/or software.

   ********************************************************************** */

//--------------------------------------------------------------------
// Include statements
//--------------------------------------------------------------------

#include <stdio.h>
#include <stdlib.h>
#include "ArrayList.h"

//--------------------------------------------------------------------
// Constants
//--------------------------------------------------------------------

static int INITIAL_CAPACITY = 32;
static int CAPACITY_INCREMENT = 32;

//--------------------------------------------------------------------
// Prototypes
//--------------------------------------------------------------------

static void ensureCapacity (ArrayList * obj, int newCount);
static inline int validIndex (ArrayList * obj, int idx);

//--------------------------------------------------------------------
// Public Interface
//--------------------------------------------------------------------

/*
 * Append an object pointer to the end of the list.
 */

void arrayListAppend (ArrayList * obj, void * element)
{
    ensureCapacity (obj, obj->count + 1);
    obj->list[obj->count++] = element;
}

/*
 * Remove all the elements from the list.  This does
 * not free the objects themselves.
 */

void arrayListClear (ArrayList * obj)
{
    obj->count = 0;
}

/*
 * Remove all the elements from the list and free the
 * memory associated with each object.
 */

void arrayListClearDeep (ArrayList * obj)
{
    int i;

    for (i = 0; i < obj->count; i++)
    {
        free (obj->list[i]);
    }

    arrayListClear (obj);
}

/*
 * Create a new ArrayList object.
 */

ArrayList * arrayListCreate (int initialCapacity, int capacityIncrement)
{
    ArrayList * obj = (ArrayList *) malloc (sizeof(ArrayList));
    obj->increment = capacityIncrement;
    obj->count = 0;

    if (initialCapacity < 1)
    {
        initialCapacity = INITIAL_CAPACITY;
    }

    obj->size = initialCapacity;
    obj->list = (void **) malloc (sizeof(char*) * obj->size);
    return obj;
}

/*
 * Destroy an ArrayList object.  This does not free the memory
 * assocated with each object in the list.
 */

void arrayListDestroy (ArrayList * obj)
{
    if (obj->list != NULL)
    {
        free (obj->list);
    }

    free (obj);
}

/*
 * Retrieve the object pointer at the specified index.
 */

void * arrayListGet (ArrayList * obj, int idx)
{
    if (validIndex (obj, idx))
    {
        return obj->list[idx];
    }
    else
    {
        return NULL;
    }
}

/*
 * Return the number of objects in the list.
 */

int arrayListGetCount (ArrayList * obj)
{
    return obj->count;
}

//--------------------------------------------------------------------
// Private Interface
//--------------------------------------------------------------------

/*
 * Ensure the list array has sufficient capacity; otherwise, allocate
 * a larger array and copy the elements to it.
 */

static void ensureCapacity (ArrayList * obj, int newCount)
{
    void ** newList;

    if (newCount <= obj->size)
    {
        return;
    }

    obj->size += obj->increment;
    newList = (void **) malloc (sizeof(char*) * obj->size);
    memcpy (newList, obj->list, sizeof(char*) * obj->count);
    free (obj->list);
    obj->list = newList;
}

/*
 * Returns true if the index is valid.
 */

static inline int validIndex (ArrayList * obj, int idx)
{
    if (idx >= 0 && idx < obj->count)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

//--------------------------------------------------------------------
// Main function
//--------------------------------------------------------------------

#if 0
int main (int argc, char * argv[])
{
    char * cptr;
    ArrayList * list;

    printf ("ArrayList Test Begin\n");

    list = arrayListCreate ();

    printf ("ArrayList created\n");

    arrayListAppend (list, "Hello");
    arrayListAppend (list, "There");

    cptr = arrayListGet (list, 1);
    printf ("cptr = %s\n", cptr);

    cptr = arrayListGet (list, 0);
    printf ("cptr = %s\n", cptr);

    arrayListClear (list);

    arrayListDestroy (list);

    printf ("ArrayList destroyed\n");

    printf ("ArrayList Test End\n");

    return 0;
}
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
