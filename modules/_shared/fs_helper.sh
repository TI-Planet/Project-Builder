#!/bin/bash

# Part of TI-Planet's Project Builder
# (C) Adrien "Adriweb" Bertrand
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# TODO: deal with the template that is definitely not generic enough...
# TODO: deal with the regexps that are maybe not generic enough...

if [ "$(id -u)" -eq "0" ]
then
    echo "You don't want to run this as root..." 1>&2
    exit 99
fi

################################
## Pre-processing
################################

if [ "$#" -lt 2 ] || [ "$#" -gt 3 ]
then
    echo "***USAGE***" 1>&2
    echo "  First argument needs to be the project's unique ID. Then, a command and its arg if needed: " 1>&2
    echo "    createProj" 1>&2
    echo "    deleteProj" 1>&2
    echo "    clone [newID]" 1>&2
    exit 2
else
    dir="/home/pbbot/projectbuilder"
    id=$1
    cmd=$2

    #echo "bash fs_helper called with $# args" > ${dir}/projects/templog_fs.txt
    #echo "${cmd} command received on id ${id}" >> ${dir}/projects/templog_fs.txt

    if [[ ! $id =~ ^[a-zA-Z0-9_]+$ ]]
    then
        echo "Bad id" 1>&2
        exit 42
    fi

    if [[ "$cmd" == "clone" ]]
    then
        if [ "$#" -eq 3 ]
        then
            newid=$3
            if [[ ! $newid =~ ^[a-zA-Z0-9_]+$ ]]
            then
                echo "Bad newID arg" 1>&2
                exit 43
            fi
        else
            echo "clone command needs newID arg" 1>&2
            exit 7
        fi
    fi

################################
## Processing
################################

    if [[ "$cmd" == "clone" ]]
    then
        # check if it exists then clone it keeping permissions (www-data)
        cd "${dir}/projects/${id}" || exit 5
        cp -rp "${dir}/projects/${id}" "${dir}/projects/${newid}" || exit 3

    elif [[ "$cmd" == "createProj" ]]
    then
        cp -rp "${dir}/projects/template" "${dir}/projects/${id}" || exit 3
        touch "${dir}/projects/${id}/config.json"
        find "${dir}/projects/${id}/" -type f -regex '.*\.\(c\|cpp\|h\|hpp\|asm\|inc\|json\)' -exec chmod 666 {} \; # let www-data write

    elif [[ "$cmd" == "deleteProj" ]]
    then
        rm -rf "${dir}/projects/${id}" || exit 4
        # echo $? >> ${dir}/projects/templog_fs.txt

    else
        echo "Unrecognized command '$cmd'" 1>&2
        exit 7
    fi

    exit 0
fi

