#!/bin/bash

ROOT="$( cd -P "$( dirname "$( dirname "${BASH_SOURCE[0]}" )" )" && pwd )"
JS_DIR="$ROOT/app/assets/js"
JS_MANIFEST="$JS_DIR/manifest.txt"
PUBLIC_JS_DIR="$ROOT/public/js"
APP="$PUBLIC_JS_DIR/app.js"

mkdir -p "$PUBLIC_JS_DIR"
while read line; do
  if ! [[ $line =~ ^\s*# ]]; then
    cat "$JS_DIR/$line"
  fi
done <"$JS_MANIFEST" | jsmin >"$APP"
