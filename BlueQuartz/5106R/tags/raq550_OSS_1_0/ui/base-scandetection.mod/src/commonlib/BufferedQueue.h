/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/BufferedQueue.h,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  25-Jul-2000
    Originating Author :  Brian Adkins & Sam Napolitano

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

#ifndef _BUFFEREDQUEUE_H_
#define _BUFFEREDQUEUE_H_

#include <deque>
#include <utility>
#include "Exception.h"
#include "Thread.h"

using namespace std;

//-----------------------------------------------------------------------------
//  QueueBlock class
//-----------------------------------------------------------------------------

class QueueBlock
{
private:
    char * buffer;
    unsigned int    length;     // how much data is in block
    unsigned int    size;       // size of block

    void init (const QueueBlock&);

protected:

public:
    //-------------------------------------------------------------------------
    //  Canonical methods
    //-------------------------------------------------------------------------

    QueueBlock (unsigned int size);
    QueueBlock (const QueueBlock&);
    ~QueueBlock ();
    QueueBlock& operator=(const QueueBlock&);

    //-------------------------------------------------------------------------
    //  Public interface
    //-------------------------------------------------------------------------

    //  Append an array of chars to the end of the block
    //  Returns number of characters written
    unsigned int append (const char * chars, unsigned int length);

    const char *            getBuffer   () const { return buffer;   }
    unsigned int            getLength   () const { return length;   }
    unsigned int            getSize     () const { return size;     }
    void                    reset       ()       { length = 0;      }
};

//-----------------------------------------------------------------------------
//  BufferedQueue class
//-----------------------------------------------------------------------------

class BufferedQueue
{
private:
    deque<QueueBlock *> freeList;
    deque<QueueBlock *> queue;
    QueueBlock *queueBlock;            // ptr to writable block
    unsigned int size;                 // QueueBlock size
    Mutex mutex;
    Condition condition;

    void createBlock();
    void deleteQueue(deque<QueueBlock *> &que);

    BufferedQueue (const BufferedQueue&);
    BufferedQueue& operator=(const BufferedQueue&);

protected:

public:
    //-------------------------------------------------------------------------
    //  Canonical methods
    //-------------------------------------------------------------------------

    BufferedQueue (unsigned int size = 4096);
    ~BufferedQueue ();

    //-------------------------------------------------------------------------
    //  Public interface
    //-------------------------------------------------------------------------

    //  Append an array of chars to the end of the queue
    void append (const char * chars, unsigned int length);

    //  Cause the QueuBlock currently being written to be added to the queue
    void flush ();

    //  Get a pointer to the first block in the queue and prevent further
    //  writes to this block.  freeHeadBlock() should be called to release
    //  block.  Blocks until a QueueBlock is ready, or until the time
    //  expires.  If the time expires and no block is ready, NULL is
    //  returned.  If 0 is specified for the timeout -> block indefinitely.
    const QueueBlock* getHeadBlock (int milliseconds = 0);

    //  Free the first block in the queue.  BufferedQueue handles the memory
    //  management.
    void freeHeadBlock ();
};

#endif /* #ifndef _BUFFEREDQUEUE_H_ */
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
