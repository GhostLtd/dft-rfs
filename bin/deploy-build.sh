#!/bin/sh

if [ -d "$PWD/vendor/bin" ] ; then
  PATH="$PWD/vendor/bin:$PATH"
fi

which yarn
yarn install --frozeon-lockfile
yarn run deploy-build
