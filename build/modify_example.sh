#!/bin/sh

files=`ls release/example | grep bat`
phar="EmailSender_packed.phar"
for f in ${files[*]}; do
    sed "/php/{ s/php/..\\\\php74\\\\php/; s/\.\.\/src\/EmailSenderCli\.php/..\/build\/$phar/ }"\
    "release/example/$f"\
    > "release/example/${f%\.bat}_php74.bat"
    rm -rf "release/example/$f"
done
