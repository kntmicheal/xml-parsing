#!/bin/bash

/usr/bin/rsync --remove-source-files -avp "/Volumes/Documents/Reports/XML/Examinations/" /DSD/Reports/Examinations

/usr/bin/php /DSD/Scripts/post_examination_reports.php
