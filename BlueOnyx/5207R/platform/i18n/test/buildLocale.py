#!/usr/bin/env python
from globalvars import Vars
import posixpath
import os

def podir2modir(lang):
	for domain in os.listdir('%s/%s/' %(Vars.MessageDir,lang) ):
		if domain[-3:] != '.po':
			continue
		domain = domain[:-3]

		result = os.popen(
			'%s /%s/%s/%s.po -o %s/%s/LC_MESSAGES/%s.mo 2>&1' % (
				Vars.MsgFmtBin,
				Vars.MessageDir,
				lang,
				domain,
				Vars.MoDir,
				lang,
				domain ),'r' ).readlines()
		if result:
			print 'Error: %s' % result[0]

for lang in os.listdir(Vars.MessageDir):
	if lang == 'prop':
		continue

	if not os.path.isdir('%s/%s/' %(Vars.MoDir, lang)):
		os.mkdir('%s/%s/' %(Vars.MoDir, lang))
	if not os.path.isdir('%s/%s/LC_MESSAGES'%(Vars.MoDir, lang)):
		os.mkdir('%s/%s/LC_MESSAGES' %(Vars.MoDir, lang))

	podir2modir(lang)


