<?php
// Append JSON:API write mode override to settings.php
$settings_path = '/var/www/html/sites/default/settings.php';
$line = "\n\$config['jsonapi.settings']['read_only'] = FALSE;\n";
file_put_contents($settings_path, $line, FILE_APPEND);
echo "Appended jsonapi write mode override to settings.php\n";
echo "Last 3 lines:\n";
$lines = file($settings_path);
echo implode('', array_slice($lines, -3));
