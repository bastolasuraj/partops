<?php
/**
 * Check timezone configuration
 */

echo "Timezone Configuration Check\n";
echo str_repeat("=", 50) . "\n\n";

// Before setting
echo "1. Default PHP timezone (from php.ini):\n";
echo "   " . date_default_timezone_get() . "\n\n";

// Set to America/Toronto
date_default_timezone_set('America/Toronto');

echo "2. After setting to America/Toronto:\n";
echo "   Timezone: " . date_default_timezone_get() . "\n";
echo "   Current time: " . date('Y-m-d H:i:s T') . "\n";
echo "   Current time (full): " . date('l, F j, Y g:i:s A T') . "\n\n";

// Compare with other timezones
echo "3. Time comparison:\n";

date_default_timezone_set('America/Toronto');
echo "   Toronto:  " . date('Y-m-d H:i:s T') . "\n";

date_default_timezone_set('Europe/Berlin');
echo "   Berlin:   " . date('Y-m-d H:i:s T') . "\n";

date_default_timezone_set('UTC');
echo "   UTC:      " . date('Y-m-d H:i:s T') . "\n\n";

// Reset to Toronto
date_default_timezone_set('America/Toronto');

echo "✓ Timezone is now set to America/Toronto (EST/EDT)\n";
echo "✓ All timestamps in the application will use Toronto time\n\n";
