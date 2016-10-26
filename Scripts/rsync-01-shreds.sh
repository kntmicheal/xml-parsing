#!/bin/bash

/usr/bin/rsync --remove-source-files -avp "/Volumes/Documents/Reports/XML/Shreds/" /DSD/Reports/Shreds

/usr/bin/php /DSD/Scripts/post_shred_reports.php
