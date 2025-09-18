#!/bin/bash
# hls_uploader.sh <camera> <hls_dir> <api_base> <secret>
CAM="\$1"
DIR="\$2"
API_BASE="\$3"   # e.g. https://thebeartraxs.com/wp-json/perceptor/v1
SECRET="\$4"

STATE_FILE="\$DIR/.uploaded"
touch "\$STATE_FILE"

while true; do
  for f in "\$DIR"/*; do
    [ -f "\$f" ] || continue
    bn=\$(basename "\$f")
    md=\$(md5sum "\$f" 2>/dev/null | cut -d' ' -f1)
    prev=\$(grep -F \"\$bn \" "\$STATE_FILE" 2>/dev/null | awk '{print \$2}' || true)
    if [ \"\$prev\" = \"\$md\" ]; then
      continue
    fi

    # upload
    code=\$(curl -s -o /dev/null -w "%{http_code}" -F "secret=\$SECRET" -F "camera=\$CAM" -F "file=@\$f" "\$API_BASE/hls_upload")
    if [ "\$code" = "200" ] || [ "\$code" = "201" ]; then
      # update state: remove old entry for this file then append new hash
      grep -v -F \"\$bn \" "\$STATE_FILE" 2>/dev/null > "\$STATE_FILE.tmp" || true
      mv "\$STATE_FILE.tmp" "\$STATE_FILE"
      echo "\$bn \$md" >> "\$STATE_FILE"
    else
      echo "\$(date -Iseconds) upload failed for \$bn code=\$code" >> "\$DIR/uploader.err.log"
    fi
  done
  sleep 1
done
