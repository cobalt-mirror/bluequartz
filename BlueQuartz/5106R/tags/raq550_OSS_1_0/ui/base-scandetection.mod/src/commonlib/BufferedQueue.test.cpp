# include <stdio.h>
# include <stdlib.h>

# include "BufferedQueue.h"

// g++ -g -o BufferedQueue BufferedQueue.test.cpp BufferedQueue.cpp

void test_queue_block()
{
  QueueBlock qb(3);
  QueueBlock qbs(3);
  QueueBlock qbz(0);
  QueueBlock qbl(12);

  char *data = "foo1234567890ooooooooooooooo";

  qb.append(data, 5);
  printf ("Buffer: %s\n", qb.getBuffer());
  qbs.append(data, 5);
  printf ("Buffer: %s\n", qbs.getBuffer());
  qbz.append(data, 5);
  printf ("Buffer: %s\n", qbz.getBuffer());

  qbl.append(data, 5);
  qbl.append(data, 5);
  qbl.append(data, 5);
  qbl.append(data, 5);

  char tmp[512];
  int s = qbl.getLength();
  snprintf(tmp, s+1, "%s", qbl.getBuffer());
  printf ("Buffer: %s\n", tmp);

  QueueBlock qb2(qbl);
  s = qbl.getLength();
  snprintf(tmp, s+1, "%s", qbl.getBuffer());
  printf ("Buffer2: %s\n", tmp);
}

void add_data(BufferedQueue& bq, char *data)
{
  int i = strlen(data);
  bq.append(data, i);
}

// Assumes QueueBlock size of 4 bytes
void bufqueue()
{
  char *data2 = "foo";
  BufferedQueue bq(4);
  const QueueBlock *bqp;

  add_data(bq, data2);
  add_data(bq, data2);

  bqp = bq.getHeadBlock();
  printf ("Data: %s\n", bqp->getBuffer());
  bq.freeHeadBlock();

  bqp = bq.getHeadBlock();
  printf ("Data: %s\n", bqp->getBuffer());
  bq.freeHeadBlock();

  bqp = bq.getHeadBlock();
  // printf ("Data: %s\n", bqp->getBuffer());  // seg fault: bqp == NULL
  bq.freeHeadBlock();

  bqp = bq.getHeadBlock();
  // printf ("Data: %s\n", bqp->getBuffer());
  bq.freeHeadBlock();
  // bq.freeHeadBlock();   // exception
}

void bufqueue2()
{
  BufferedQueue bq(4);

  bq.~BufferedQueue();
}

int main()
{
  test_queue_block();
  bufqueue();  
  bufqueue2();  
  return 0;
}
