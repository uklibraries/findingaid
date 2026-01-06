#!/usr/bin/env bash
set -euo pipefail

XML_DIR="./xml"
XML_ARCHIVE="xml.tar.gz"
XML_URL="https://exploreuk.uky.edu/fa/findingaid/xml.tar.gz"

#check if xml directory exists or is empty
if [ ! -d "$XML_DIR" ] || [ -z "$(ls -A "$XML_DIR" 2>/dev/null)" ]; then
    echo "XML directory not found or empty - downloading test XML..."
    wget -q "$XML_URL" -O "$XML_ARCHIVE"
    echo "Extracting test XML..."
    mkdir -p "$XML_DIR"
    tar zxf "$XML_ARCHIVE" -C .
    rm "$XML_ARCHIVE"
    echo "Test XML loaded into ./xml"
else
    echo "Test XML already in project - skipping"
fi