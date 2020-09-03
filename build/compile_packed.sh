#!/bin/sh

libpath="../../lib"
modulelib="../lib"

# lib
lib=("common" "MailAPI" "SimpleTemplate")
if [[ ! -d $modulelib ]]; then
    mkdir $modulelib
fi
for f in ${lib[*]}; do
    cp -r "${libpath}/${f}" $modulelib
done
cp "${libpath}/autoload@lib.php" $modulelib

# vender
# cp -r "../../vendor" ../

packager.phar -p .. -o EmailSender_packed.phar

# clean
rm -rf $modulelib
# rm -rf "../vendor"