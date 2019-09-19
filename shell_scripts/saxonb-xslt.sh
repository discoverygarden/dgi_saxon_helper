#!/bin/bash

# Grab our input variables.
saxon_executable=$1
saxon_params=$2
transform_file=$3

declare -a escaped_array
while read -u 3 p; do
  escaped_array+=("$p")
done

$saxon_executable -versionmsg:off -ext:on "$saxon_params" "$transform_file" "${escaped_array[@]}"
