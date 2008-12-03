#!/bin/sh

services="httpd iptables"
for service in $services; do
  /sbin/chkconfig $service on
done
