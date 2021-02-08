#!/bin/bash
find app -type f -name '*.php' | xargs -n 1 php -l
