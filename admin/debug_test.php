<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Test Fingerprint</h2>";

// Test 1: Cek apakah file config bisa di-load
echo "<h3>1. Test Load Config</h3>";
try {
    include '../includes/fingerprint_config.php';
    echo "✅ Config berhasil di-load<br>";
    echo "IP Default: " . FINGERPRINT_IP . "<br>";
    echo "Port Default: " . FINGERPRINT_PORT . "<br>";
} catch (Exception $e) {
    echo "❌ Error load config: " . $e->getMessage() . "<br>";
}

// Test 2: Cek apakah ZKLibrary bisa di-load
echo "<h3>2. Test Load ZKLibrary</h3>";
try {
    require_once '../includes/zklib/zklibrary.php';
    echo "✅ ZKLibrary berhasil di-load<br>";
} catch (Exception $e) {
    echo "❌ Error load ZKLibrary: " . $e->getMessage() . "<br>";
}

// Test 3: Test fungsi ping
echo "<h3>3. Test Ping Function</h3>";
try {
    $ip = FINGERPRINT_IP;
    $ping_result = pingDevice($ip);
    echo "IP: $ip<br>";
    echo "Ping Result: " . ($ping_result !== 'down' ? "SUCCESS ({$ping_result}ms)" : "FAILED") . "<br>";
} catch (Exception $e) {
    echo "❌ Error ping: " . $e->getMessage() . "<br>";
}

// Test 4: Test fungsi testFingerprintConnection
echo "<h3>4. Test testFingerprintConnection Function</h3>";
try {
    $results = testFingerprintConnection(FINGERPRINT_IP);
    echo "Jumlah results: " . count($results) . "<br>";
    foreach ($results as $key => $result) {
        echo "- $key: " . $result['status'] . " - " . $result['message'] . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error testFingerprintConnection: " . $e->getMessage() . "<br>";
}

// Test 5: Test koneksi langsung ZKLibrary
echo "<h3>5. Test Direct ZKLibrary Connection</h3>";
try {
    $zk = new ZKLibrary(FINGERPRINT_IP, FINGERPRINT_PORT);
    $zk->setTimeout(5, 0);
    
    echo "ZKLibrary instance dibuat<br>";
    
    if ($zk->connect()) {
        echo "✅ Koneksi berhasil<br>";
        $version = $zk->getVersion();
        echo "Version: $version<br>";
        $zk->disconnect();
    } else {
        echo "❌ Koneksi gagal<br>";
    }
} catch (Exception $e) {
    echo "❌ Error ZKLibrary: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Debug selesai. Cek hasil di atas untuk melihat di mana masalahnya.</strong></p>";
?> 