/*

$header$

                   Site:  Progressive Systems, Inc.

                Module :  $Source: /home/cvs/base-scandetection.mod/src/commonlib/BufferedQueue.cpp,v $
              Revision :  $Revision: 1.1 $

               Package :  Phoenix Adaptive Firewall for Unix

         Creation Date :  25-Jul-2000
    Originating Author :  Brian Adkins, Sam Napolitano

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

#include "BufferedQueue.h"

# include <stdio.h>
# include <string.h>


//-----------------------------------------------------------------------------
//  QueueBlock class
//-----------------------------------------------------------------------------

QueueBlock::QueueBlock (unsigned int size_p)
{
    size = size_p;
    buffer = new char[size];
    length = 0;
}

QueueBlock::QueueBlock (const QueueBlock& obj)
{
    init (obj);
}

QueueBlock::~QueueBlock ()
{
    delete[] buffer;
}

QueueBlock& QueueBlock::operator=(const QueueBlock& obj)
{
    if (this != &obj)
    {
        init (obj);
    }

    return *this;
}

void QueueBlock::init (const QueueBlock& obj)
{
    size        = obj.size;
    buffer      = new char[size];
    length      = obj.length;

    memcpy( buffer, obj.buffer, length );
}

//  Append an array of chars to the end of the queue.
//  If all of the chars won't fit in current block, write what's avail.
//  Returns number of characters written

unsigned int QueueBlock::append (const char * chars, unsigned int len)
{
    // Write len characters unless remaining buffer size is < len
    unsigned int n = (size - length) < len ? (size - length) : len;

    memcpy( (void *) (buffer + length), chars, n);
    length += n;

    return n;
}


//-----------------------------------------------------------------------------
//  BufferedQueue class
//-----------------------------------------------------------------------------

static const unsigned int QUEUE_LIMIT = 16;
static const int STR_SIZE = 256;


BufferedQueue::BufferedQueue (unsigned int size_p)
{
    size = size_p;
    queueBlock = 0;
}

void BufferedQueue::deleteQueue(deque<QueueBlock *> &que)
{
    //  The mutex is acquired by the caller of deleteQueue()
    while (que.size() > 0)
    {
        delete que.front();
        que.pop_front();
    }
}

BufferedQueue::~BufferedQueue ()
{
    delete queueBlock;

    MutexLock lock (mutex);
    deleteQueue(freeList);
    deleteQueue(queue);
}

void BufferedQueue::createBlock()
{
    queueBlock = new QueueBlock(size);

    if (queueBlock == 0)
    {
        throw RuntimeException("::BufferedQueue: no available memory");
    }
}

//  Append an array of chars to the end of the queue.
//  If all of the chars won't fit in current block, throw exception.

void BufferedQueue::append (const char * chars, unsigned int length)
{
    if (queueBlock == 0)
    {
        createBlock();
    }

    // For now, don't allow writes bigger than underlying QueueBlock size
    if (length > queueBlock->getSize())
    {
        char tmp[STR_SIZE];
	snprintf( tmp, sizeof(tmp), "::append: record too big: %d > %d",
		  length, queueBlock -> getSize() );
        throw RuntimeException( tmp );
    }

    // If the new data won't fit in the remaining space of queueBlock,
    // then put the queueBlock into the queue, allocate a new
    // queueBlock, and append to it.

    if ((queueBlock->getSize() - queueBlock->getLength()) < length)
    {
	MutexLock lock (mutex);
        queue.push_back (queueBlock);
	condition.signal ();

	if (! freeList.empty())           // blocks avail. from free list?
	{
	    queueBlock = freeList.front();
	    freeList.pop_front();
	    queueBlock -> reset();
	}
	else
	{
	    createBlock();
	}
    }

    queueBlock -> append( chars, length );
}

const QueueBlock* BufferedQueue::getHeadBlock (int milliseconds)
{
    MutexLock lock (mutex);

    while (queue.empty())
    {
	try
	{
	    condition.wait (mutex, milliseconds);
	}
	catch (ThreadTimeoutException&)
	{
	    return 0;
	}
    }

    return queue.front();
}

void BufferedQueue::flush ()
{
    if (queueBlock == 0 || queueBlock->getLength() < 1)
	return;

    MutexLock lock (mutex);
    queue.push_back (queueBlock);
    condition.signal ();

    if (! freeList.empty())           // blocks avail. from free list?
    {
	queueBlock = freeList.front();
	freeList.pop_front();
	queueBlock -> reset();
    }
    else
    {
	createBlock();
    }
}


//  Free the first block in the queue.  BufferedQueue handles the memory
//  management.

void BufferedQueue::freeHeadBlock ()
{
    MutexLock lock (mutex);

    if ( queue.empty() )
    {
        throw RuntimeException("::freeHeadBlock: queue empty");
    }

    QueueBlock *qb = queue.front();

    queue.pop_front();

    // Don't let freeList grow too big
    if (freeList.size() < QUEUE_LIMIT)
    {
        freeList.push_back(qb);
    }
    else
    {
	delete qb;
    }
}
