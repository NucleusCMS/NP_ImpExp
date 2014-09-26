#!/bin/bash -x

FILES=`find . -name '*japanese-utf8*'`

for utf8file in $FILES
do
	eucfile=`echo $utf8file | sed 's/japanese-utf8/japanese-euc/'`
	nkf -e -W -d < $utf8file > $eucfile
done
