#!/bin/sh
php_path="/d/Environment/php74"

if [[ -d "release" ]]; then
    rm -rf release
fi

mkdir -p release/build
cp -r ../docs release
cp -r ../example release
cp -r ../resources release
cp -r ../LICENSE release
cp -r ../README.md release
cp -r $php_path release
file=`ls | grep EmailSender`
cp $file release/build

. ./modify_example.sh

echo "Done!!"