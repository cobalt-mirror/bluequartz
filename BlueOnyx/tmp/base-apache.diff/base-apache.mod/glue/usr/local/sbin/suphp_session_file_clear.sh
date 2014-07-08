#!/bin/sh
for file in `find /home/sites/*/.tmp/* -cmin +24`; do rm -rf $file; done
