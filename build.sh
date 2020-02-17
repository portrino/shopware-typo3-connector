#!/usr/bin/env bash

commit=$1
if [ -z ${commit} ]; then
    commit=$(git tag --sort=-creatordate | head -1)
    if [ -z ${commit} ]; then
        commit="master";
    fi
fi

# Remove old release
rm -rf Port1Typo3Connector/ Port1Typo3Connector-*.zip

# Build new release
mkdir -p Port1Typo3Connector
git archive ${commit} | tar -x -C Port1Typo3Connector
zip -r Port1Typo3Connector-${commit}.zip Port1Typo3Connector
