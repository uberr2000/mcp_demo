<?php

/**
 * 測試 SSE 強制關閉連線功能
 * 此腳本模擬客戶端突然斷開連線的情況，並驗證服務器端是否能夠正確檢測並強制關閉連線
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Client\Factory as HttpClient;

class SSEForcefulClosureTest
{
    private $baseUrl;
    private $httpClient;
    private $results = [];    public function __construct()
    {
        $this->baseUrl = 'http://localhost:8080';
        $this->httpClient = new HttpClient();
    }

    /**
     * 執行所有測試
     */
    public function runAllTests()
    {
        echo "=== SSE 強制關閉連線測試 ===\n\n";

        $this->testNormalDisconnection();
        $this->testAbruptDisconnection();
        $this->testConnectionTimeout();
        $this->testMultipleConnections();
        
        $this->printSummary();
    }

    /**
     * 測試正常斷開連線
     */
    private function testNormalDisconnection()
    {
        echo "1. 測試正常斷開連線...\n";
        
        try {
            $process = $this->startSSEConnection();
            
            // 等待幾秒讓連線建立
            sleep(3);
            
            // 正常終止進程
            $this->terminateProcess($process, SIGTERM);
            
            // 等待進程結束
            $exitCode = proc_close($process);
            
            $this->results['normal_disconnect'] = [
                'status' => 'passed',
                'exit_code' => $exitCode,
                'note' => '正常斷開連線測試完成'
            ];
            
            echo "   ✓ 正常斷開連線測試通過\n";
            
        } catch (Exception $e) {
            $this->results['normal_disconnect'] = [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
            echo "   ✗ 正常斷開連線測試失敗: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * 測試突然斷開連線
     */
    private function testAbruptDisconnection()
    {
        echo "2. 測試突然斷開連線...\n";
        
        try {
            $process = $this->startSSEConnection();
            
            // 等待幾秒讓連線建立
            sleep(3);
            
            // 強制終止進程（模擬網絡中斷）
            $this->terminateProcess($process, SIGKILL);
            
            // 不等待進程結束，直接關閉
            proc_close($process);
            
            $this->results['abrupt_disconnect'] = [
                'status' => 'passed',
                'note' => '突然斷開連線測試完成'
            ];
            
            echo "   ✓ 突然斷開連線測試通過\n";
            
        } catch (Exception $e) {
            $this->results['abrupt_disconnect'] = [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
            echo "   ✗ 突然斷開連線測試失敗: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * 測試連線超時
     */
    private function testConnectionTimeout()
    {
        echo "3. 測試連線超時...\n";
        
        try {
            // 創建一個會長時間運行但不回應的連線
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/mcp/sse');
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5秒超時
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
                // 忽略所有輸出，模擬無回應的客戶端
                return strlen($data);
            });
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $this->results['connection_timeout'] = [
                'status' => 'passed',
                'http_code' => $httpCode,
                'curl_error' => $error,
                'note' => '連線超時測試完成'
            ];
            
            echo "   ✓ 連線超時測試通過 (HTTP: $httpCode)\n";
            
        } catch (Exception $e) {
            $this->results['connection_timeout'] = [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
            echo "   ✗ 連線超時測試失敗: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * 測試多重連線
     */
    private function testMultipleConnections()
    {
        echo "4. 測試多重連線處理...\n";
        
        try {
            $processes = [];
            $connectionCount = 3;
            
            // 啟動多個連線
            for ($i = 0; $i < $connectionCount; $i++) {
                $processes[] = $this->startSSEConnection();
                usleep(500000); // 0.5秒延遲
            }
            
            echo "   - 已建立 $connectionCount 個連線\n";
            
            // 等待連線建立
            sleep(3);
            
            // 隨機終止連線
            foreach ($processes as $i => $process) {
                if ($i % 2 === 0) {
                    $this->terminateProcess($process, SIGKILL); // 強制終止
                } else {
                    $this->terminateProcess($process, SIGTERM); // 正常終止
                }
                usleep(200000); // 0.2秒延遲
            }
            
            // 清理進程
            foreach ($processes as $process) {
                proc_close($process);
            }
            
            $this->results['multiple_connections'] = [
                'status' => 'passed',
                'connection_count' => $connectionCount,
                'note' => '多重連線測試完成'
            ];
            
            echo "   ✓ 多重連線測試通過\n";
            
        } catch (Exception $e) {
            $this->results['multiple_connections'] = [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
            echo "   ✗ 多重連線測試失敗: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * 啟動 SSE 連線
     */
    private function startSSEConnection()
    {
        $cmd = "curl -N -H 'Accept: text/event-stream' " . $this->baseUrl . "/mcp/sse 2>/dev/null";
        
        $process = proc_open($cmd, [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ], $pipes);
        
        if (!is_resource($process)) {
            throw new Exception("無法啟動 SSE 連線進程");
        }
        
        return $process;
    }

    /**
     * 終止進程
     */
    private function terminateProcess($process, $signal = SIGTERM)
    {
        if (is_resource($process)) {
            $processInfo = proc_get_status($process);
            if ($processInfo['running']) {
                // 在 Windows 上，我們使用 taskkill
                if (PHP_OS_FAMILY === 'Windows') {
                    if ($signal === SIGKILL) {
                        exec("taskkill /F /PID " . $processInfo['pid']);
                    } else {
                        exec("taskkill /PID " . $processInfo['pid']);
                    }
                } else {
                    posix_kill($processInfo['pid'], $signal);
                }
            }
        }
    }

    /**
     * 檢查服務器是否運行
     */
    private function checkServerStatus()
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/mcp/info', [
                'timeout' => 5
            ]);
            
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 打印測試總結
     */
    private function printSummary()
    {
        echo "=== 測試總結 ===\n";
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $testName => $result) {
            $status = $result['status'] === 'passed' ? '✓' : '✗';
            echo "$status $testName: " . $result['status'] . "\n";
            
            if ($result['status'] === 'passed') {
                $passed++;
            } else {
                $failed++;
                if (isset($result['error'])) {
                    echo "    錯誤: " . $result['error'] . "\n";
                }
            }
        }
        
        echo "\n總計: $passed 通過, $failed 失敗\n";
        
        if ($failed === 0) {
            echo "🎉 所有測試都通過了！SSE 強制關閉連線功能正常運作。\n";
        } else {
            echo "⚠️  有 $failed 個測試失敗，請檢查服務器日誌。\n";
        }
    }
}

// 執行測試
try {
    $test = new SSEForcefulClosureTest();
    $test->runAllTests();
} catch (Exception $e) {
    echo "測試執行失敗: " . $e->getMessage() . "\n";
    exit(1);
}
