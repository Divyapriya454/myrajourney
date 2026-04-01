<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared\n";
} else {
    echo "OPcache not available\n";
}

// Clear realpath cache
clearstatcache(true);
echo "Realpath cache cleared\n";

echo "All caches cleared. Please test again.\n";
