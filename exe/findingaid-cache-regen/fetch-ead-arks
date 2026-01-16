#!/bin/bash
set -euo pipefail

# The environment should provide these variables
# SOLR_URL: e.g. https://solrhost.example.com/solr/select
# ARKS_FILE: where ARKs should be written

COUNT_URL="${SOLR_URL}?wt=json&fl=id&fq=format:collections"
numFound=$(curl -s "$COUNT_URL" | jq -r .response.numFound)
echo "Found $numFound collections"

echo "Fetching ids"
EADS_URL="${SOLR_URL}?wt=json&fl=id&fq=format:collections&rows=${numFound}"
curl -s "$EADS_URL" | jq -r '.response.docs[] | .id' > "$ARKS_FILE"

echo "IDs written to $ARKS_FILE"
