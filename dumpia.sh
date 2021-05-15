#!/bin/sh

read fanid
mkdir $fanid # When using WSL, /mnt/c(d,e,f etc)/dumpia/ and don't forget /$fanid at the end!
php dumpia.php --key <_session_id> --output <output directory> --fanclub $fanid