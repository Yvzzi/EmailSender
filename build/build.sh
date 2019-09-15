#!/bin/sh

path="../lib/Packager"
packager="$path/src/packagerCli.php"
target=".."

if ! [[ -d "$path" ]]
then
    echo -e "\033[1;31mPlease download Packager (Yvzzi)\033[0m"
    exit 1
fi

echo -e "\033[1;32mBuilding...\033[0m"
php $packager -p $target -i buildIgnore
echo -e "\033[1;32mAll Finished.\033[0m"
