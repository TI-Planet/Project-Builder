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
#
# This file is meant to be run as the user having access to the projects files
#
# This file is incomplete/to be modified. You must implement the features according to your architecture.
# See the various TODO in the code below to know what you must do.
#


if [ "$(id -u)" -eq "0" ]
then
    echo "You don't want to run this as root..." 1>&2
    exit 99
fi

################################
## Functions
################################

function validateIncludesOrDie {
    incls=$(sed -n 's/^[[:space:]]*#[[:space:]]*include[[:space:]]*[<"][[:space:]]*\([^<>"]*\)[[:space:]]*[>"]/\1/p' src/*.h src/*.hpp src/*.c src/*.cpp src/*.asm src/*.inc 2>/dev/null | tr '\n' ' ')
    if [[ "TODO: Write your desired conditions/checks here, for instance, check if $incls has any slashes, ../, etc." ]]
    then
        echo "Forbidden include detected" >> $1
        exit 8
    fi
}

################################
## Pre-processing
################################

if [ "$#" -lt 2 ] || [ "$#" -gt 4 ]
then
    echo "Needs 2, 3, or 4 args: none (makegfx), unique_id and command (addfile, deletefile, clean, llvmsyntax, makegfx). 3 if [llvm]build/syntax (prgmName) or addfile/deletefile (fileName), 4 if renamefile (old new)" 1>&2
    exit 2
else
    projectsdir="/home/pbbot/pbprojects"
    id=$1
    cmd=$2
    prgmName="CPRGMCE"

    #echo "bash builder called with $# args" > ${projectsdir}/templog.txt
    #echo "${cmd} command received on id ${id}. \$3 = $3 ;; \$4 = $4 ;;" >> ${projectsdir}/templog.txt

    if [ "$#" -eq 3 ]
    then
        if [ "$cmd" == "build" ] || [ "$cmd" == "llvmbuild" ]
        then
            prgmName=$3
            if [[ ! $prgmName =~ ^[A-Z][A-Z0-9]{0,7}$ ]]
            then
                echo "Bad prgmName. Fallback to 'CPRGMCE'" 1>&2
                prgmName="CPRGMCE"
            fi
        fi
        if [ "$cmd" == "addfile" ] || [ "$cmd" == "deletefile" ]
        then
            fileName=$3
            if [[ "$fileName" != "icon.png" && ! $fileName =~ ^src\/(gfx\/)?[a-zA-Z0-9_]+\.(png|bmp|c|cpp|h|hpp|asm|inc)$ ]]
            then
                echo "Bad fileName. Aborting" 1>&2
                exit 41
            fi
        fi
    fi

    if [ "$#" -eq 4 ]
    then
        if [ "$cmd" == "renamefile" ]
        then
            oldName=$3
            newName=$4
            if [[ ! $oldName =~ ^src\/[a-zA-Z0-9_]+\.(c|cpp|h|hpp|asm|inc)$ ]]
            then
                echo "Bad oldName. Aborting" 1>&2
                exit 41
            fi
            if [[ ! $newName =~ ^src\/[a-zA-Z0-9_]+\.(c|cpp|h|hpp|asm|inc)$ ]]
            then
                echo "Bad newName. Aborting" 1>&2
                exit 41
            fi
        fi
    fi

    if [[ ! $id =~ ^[a-zA-Z0-9_]+$ ]]
    then
        echo "Bad id" 1>&2
        exit 42
    fi

################################
## Processing
################################

    if [[ "$cmd" == "addfile" ]]
    then
        gfxPath="${projectsdir}/${id}/src/gfx"
        if [[ $fileName =~ ^src\/gfx\/ ]] && [[ ! -d "${gfxPath}" ]]; then
            mkdir -p "${gfxPath}" || exit 44
            touch "${gfxPath}/convimg.yaml"
        fi
        # TODO: Add permissions here to other users/groups if need be.
        touch "${projectsdir}/${id}/${fileName}" || exit 44
        exit 0
    fi

    if [[ "$cmd" == "deletefile" ]]
    then
        rm "${projectsdir}/${id}/${fileName}" || exit 45
        exit 0
    fi

    if [[ "$cmd" == "renamefile" ]]
    then
        mv "${projectsdir}/${id}/${oldName}" "${projectsdir}/${id}/${newName}" || exit 46
        exit 0
    fi

    if [[ "$cmd" == "clean" ]]
    then
        cd "${projectsdir}/${id}" || exit 5
        find . -regex ".*\.\(o\|d\|bc\|cppobj\|obj\|bin\|src\|lst\|8xp\|8xv\|hex\|map\|txt\|gfxbuilt\)\$" -delete
        exit 0
    fi

    if [[ "$cmd" == "llvmsyntax" ]]
    then
        cd "${projectsdir}/${id}" || exit 5

        logFile="output_llvm_syntax.txt";
        rm -f $logFile

        if [[ ! -f "$3" ]]; then
            echo "Bad input file for llvmsyntax. Aborting" 1>&2
            exit 47
        fi

        validateIncludesOrDie $logFile

        timeout 5 cppcheck --enable=warning,performance "$3" &> cppcheck.txt

        cpfolder=/home/pbbot/debchroot/tmp/src_llvm_${id}
        mkdir -p $cpfolder/src
        cp src/*.c src/*.cpp src/*.h src/*.hpp src/*.asm src/*.inc $cpfolder/src/
        if [[ -d src/gfx/ ]]; then
          mkdir -p $cpfolder/src/gfx
          cp src/gfx/*.c src/gfx/*.h $cpfolder/src/gfx/
        fi

        # TODO: make sure to use your own paths here
        clangbin=/opt/llvm-project/build/bin/clang
        params="-I/opt/CEdev/include/"
        diags="-fsyntax-only -fdiagnostics-parseable-fixits -fdiagnostics-fixit-info"
        warns="-W -Wall -Wextra -Wwrite-strings -Wno-unknown-pragmas -Wno-incompatible-library-redeclaration -Wno-main-return-type -Wno-dangling-else"

        [[ "$3" == *pp ]] && params="$params -std=c++1z -fno-exceptions -fno-rtti"

        extraParams=$(jq -r ".clangArgs | select (.!=null)" config.json 2>/dev/null)

        # TODO: Replace "SAFELAUNCH" by something to make it safer than just launching it directly (for instance, in a chroot etc.)
        SAFELAUNCH $clangbin $warns $params $extraParams $diags -target ez80 -S -o- /tmp/src_llvm_${id}/$3 >> $logFile 2>&1

        rm -rf $cpfolder
        # TODO: also remove any temporary files clang may have created (for instance in /tmp/)

        exit 0
    fi

    if [[ "$cmd" == "makegfx" ]]
    then
        cd "${projectsdir}/${id}" || exit 5
        find . -name "*.txt" -delete

        logFile="output_llvm_build.txt";
        rm -f $logFile

        echo -e $(date +%s)"\r" > $logFile

        validateIncludesOrDie $logFile

        chrootedProjectDir="/home/pbbot/debchroot/projectbuilder/projects/${id}"
        rm -rf "${chrootedProjectDir}"
        cp -Ra "${projectsdir}/${id}" "${chrootedProjectDir}"
        buildret=0
        if [[ -d src/gfx/ ]] && [[ -s src/gfx/convimg.yaml ]]; then
          # TODO: Replace "SAFELAUNCH" by something to make it safer than just launching it directly (for instance, in a chroot etc.)
          SAFELAUNCH sh -c ". /home/.bashrc && cd /projectbuilder/projects/${id} && timeout 30 make -f ../../modules/native_eZ80/internal/toolchain/makefile version gfx && touch src/gfx/.gfxbuilt" >> ${logFile} 2>&1
          buildret=$?
        fi
        for folder in bin obj; do [[ -d "${chrootedProjectDir}/${folder}" ]] && cp -Ra "${chrootedProjectDir}/${folder}" "${projectsdir}/${id}/"; done
        [[ -d "${chrootedProjectDir}/src/gfx" ]] && rm -rf "${projectsdir}/${id}/src/gfx" && cp -Ra "${chrootedProjectDir}/src/gfx/" "${projectsdir}/${id}/src/"
        rm -rf "${chrootedProjectDir}"

        exit $buildret
    fi

    if [[ "$cmd" == "build" ]] || [[ "$cmd" == "llvmbuild" ]]
    then
        cd "${projectsdir}/${id}" || exit 5
        find . -name "*.txt" -delete

        logFile="output_llvm_build.txt";
        rm -f $logFile

        echo -e $(date +%s)"\r" > $logFile

        validateIncludesOrDie $logFile

        timeout 5 cppcheck --enable=warning,performance src/*.c src/*.cpp src/*.h src/*.hpp &> cppcheck.txt &

        extraParams=$(jq -r ".clangArgs | select (.!=null)" config.json 2>/dev/null)
        description=$(jq -r ".description | select (.!=null)" config.json 2>/dev/null)
        compressionMode=$(jq -r ".compressionMode | select (.!=null)" config.json 2>/dev/null)
        ltoEnabled=$(jq -r ".ltoEnabled | select (.!=null)" config.json 2>/dev/null)
        archived8xp=$(jq -r ".archived8xp | select (.!=null)" config.json 2>/dev/null)

        if test -z "$extraParams"
        then
              extraCflagsParam=""
        else
              extraCflagsParam="CFLAGS=\"${extraParams}\" CXXFLAGS=\"${extraParams}\""
        fi

        if test -z "$compressionMode"
        then
              compressionParam=""
        elif [[ "$compressionMode" == "none" ]]; then
              compressionParam="COMPRESSED=NO"
        else
              compressionParam="COMPRESSED_MODE=${compressionMode}"
        fi

        if test -z "$ltoEnabled"
        then
              ltoEnabledParam=""
        else
              ltoEnabledParam="LTO=${ltoEnabled}"
        fi

        if test -z "$archived8xp"
        then
              archivedParam=""
        else
              archivedParam="ARCHIVED=${archived8xp}"
        fi
        # TODO: Replace "SAFELAUNCH" by something to make it safer than just launching it directly (for instance, in a chroot etc.)

        chrootedProjectDir="/home/pbbot/debchroot/projectbuilder/projects/${id}"
        rm -rf "${chrootedProjectDir}"
        cp -Ra "${projectsdir}/${id}" "${chrootedProjectDir}"
        if [[ -d src/gfx/ ]] && [[ -s src/gfx/convimg.yaml ]] && [[ ! -f src/gfx/.gfxbuilt ]]; then
          SAFELAUNCH sh -c ". /home/.bashrc && cd /projectbuilder/projects/${id} && timeout 30 make -f ../../modules/native_eZ80/internal/toolchain/makefile version gfx && touch src/gfx/.gfxbuilt" >> ${logFile} 2>&1
        fi
        SAFELAUNCH sh -c ". /home/.bashrc && cd /projectbuilder/projects/${id} && timeout 30 make -f ../../modules/native_eZ80/internal/toolchain/makefile DESCRIPTION='\"${description}\"' NAME=${prgmName} ${extraCflagsParam} ${compressionParam} ${ltoEnabledParam} ${archivedParam} version all " >> ${logFile} 2>&1
        buildret=$?
        for folder in bin obj; do [[ -d "${chrootedProjectDir}/${folder}" ]] && cp -Ra "${chrootedProjectDir}/${folder}" "${projectsdir}/${id}/"; done
        [[ -d "${chrootedProjectDir}/src/gfx" ]] && rm -rf "${projectsdir}/${id}/src/gfx" && cp -Ra "${chrootedProjectDir}/src/gfx/" "${projectsdir}/${id}/src/"
        rm -rf "${chrootedProjectDir}"

        exit $buildret
    fi

    echo "Unrecognized command '$cmd'" 1>&2
    exit 7
fi

