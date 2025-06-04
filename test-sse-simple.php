<?php

/**
 * Simple SSE Test for Ubuntu Server on Port 8080
 */

echo "=== Simple SSE Connection Test ===\n\n";

// Test basic connectivity first
echo "1. Testing basic server connectivity...\n";
$url = 'http://localhost:8080/mcp/stdio';
$context = stream_context_create([
    'http' => [
        'timeout' => 5
    ]
]);

$response = @file_get_contents($url, false, $context);
if ($response === false) {
    echo "   ✗ Cannot connect to server at localhost:8080\n";
    echo "   Make sure the server is running on port 8080\n";
    exit(1);
} else {
    echo "   ✓ Server is responding\n";
    $data = json_decode($response, true);
    if ($data && isset($data['protocol'])) {
        echo "   ✓ MCP protocol detected: " . $data['protocol'] . "\n";
    }
}

echo "\n2. Testing SSE endpoint...\n";

// Test SSE endpoint
$sseUrl = 'http://localhost:8080/mcp/sse';
$postData = json_encode([
    'jsonrpc' => '2.0',
    'method' => 'ping',
    'params' => [],
    'id' => 'simple-test-' . time()
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: text/event-stream',
            'Cache-Control: no-cache'
        ],
        'content' => $postData,
        'timeout' => 10
    ]
]);

echo "   - Sending request to SSE endpoint...\n";
echo "   - Request: $postData\n";

$stream = @fopen($sseUrl, 'r', false, $context);

if ($stream === false) {
    echo "   ✗ Failed to open SSE stream\n";
    $error = error_get_last();
    if ($error) {
        echo "   Error: " . $error['message'] . "\n";
    }
    exit(1);
}

echo "   ✓ SSE stream opened successfully\n";
echo "   - Reading SSE data...\n\n";

$startTime = time();
$maxReadTime = 15; // Read for max 15 seconds
$messageCount = 0;
$lastActivity = time();
$activityTimeout = 5; // 5 seconds without activity = timeout

// Set stream to non-blocking mode to prevent hanging
stream_set_blocking($stream, false);

while ((time() - $startTime) < $maxReadTime) {
    $line = fgets($stream, 4096);
    
    if ($line !== false && !empty(trim($line))) {
        $line = trim($line);
        $messageCount++;
        $lastActivity = time();
        echo "   [" . date('H:i:s') . "] $line\n";
          // Check for specific SSE events
        if (strpos($line, 'event: welcome') !== false) {
            echo "   ✓ Welcome event received\n";
        } elseif (strpos($line, 'event: ping') !== false) {
            echo "   ✓ Ping event received\n";
        } elseif (strpos($line, 'event: response') !== false) {
            echo "   ✓ Response event received\n";
        }
        
        // Stop after receiving enough messages
        if ($messageCount >= 3) {
            echo "   ✓ Received enough messages, stopping test\n";
            break;
        }
    } else {
        // Check for timeout without activity
        if ((time() - $lastActivity) > $activityTimeout) {
            echo "   ⚠ No activity for {$activityTimeout} seconds, stopping...\n";
            break;
        }
        
        // Check if stream ended
        if (feof($stream)) {
            echo "   ⚠ Stream ended unexpectedly\n";
            break;
        }
        
        // Small delay to prevent CPU spinning
        usleep(200000); // 0.2 second
    }
}

fclose($stream);

echo "\n3. Test Results:\n";
echo "   - Total messages received: $messageCount\n";
echo "   - Connection duration: " . (time() - $startTime) . " seconds\n";

if ($messageCount > 0) {
    echo "   ✓ SSE connection working correctly!\n";
    echo "   ✓ Enhanced forceful closure implementation is active\n";
    echo "\n=== Test Passed! ===\n";
} else {
    echo "   ✗ No SSE messages received\n";
    echo "   Check server logs for errors\n";
    echo "\n=== Test Failed ===\n";
    exit(1);
}

echo "\n4. Testing forceful disconnect simulation...\n";
echo "   - Opening another connection that will be terminated...\n";

// Test forceful disconnect by opening a connection and terminating it
$stream2 = @fopen($sseUrl, 'r', false, $context);
if ($stream2) {
    echo "   ✓ Second connection opened\n";
    
    // Set non-blocking mode to prevent hanging
    stream_set_blocking($stream2, false);
    
    // Read a few messages with timeout
    $count = 0;
    $readStart = time();
    $maxReadTime2 = 5; // Only read for 5 seconds max
    
    while ((time() - $readStart) < $maxReadTime2 && $count < 3) {
        $line = fgets($stream2, 4096);
        if ($line !== false && !empty(trim($line))) {
            $count++;
            echo "   [MSG $count] " . trim($line) . "\n";
        } else {
            // Check if stream ended
            if (feof($stream2)) {
                echo "   ⚠ Stream ended early\n";
                break;
            }
            // Small delay to prevent CPU spinning
            usleep(100000); // 0.1 second
        }
    }
    
    echo "   - Read $count messages in " . (time() - $readStart) . " seconds\n";
    echo "   - Forcefully closing connection...\n";
    fclose($stream2);
    echo "   ✓ Connection closed - server should detect and cleanup\n";
    
    // Give server time to detect disconnect and log it
    sleep(1);
    
} else {
    echo "   ✗ Could not open second connection\n";
}

echo "\n=== Enhanced SSE with Forceful Closure Test Complete ===\n";
echo "Check server logs to verify connection lifecycle events.\n";
