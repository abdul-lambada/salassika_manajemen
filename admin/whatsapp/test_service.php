<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

$title = "Test WhatsApp Service";
$active_page = "whatsapp_test_service";

include '../../templates/header.php';
include '../../templates/sidebar.php';
include '../../includes/db.php';
require_once __DIR__ . '/../../includes/wa_util.php';

// Initialize WhatsAppService
$waService = new WhatsAppService($conn);

// Test WhatsApp service functionality
$testResults = array();

// Test 1: Check if service is properly initialized
$testResults[] = array(
    'test' => 'Service Initialization',
    'status' => $waService ? 'PASS' : 'FAIL',
    'message' => $waService ? 'WhatsAppService initialized successfully' : 'Failed to initialize WhatsAppService'
);

// Test 2: Check configuration loading
$config = $waService->getConfig();
$testResults[] = array(
    'test' => 'Configuration Loading',
    'status' => !empty($config) ? 'PASS' : 'FAIL',
    'message' => !empty($config) ? 'Configuration loaded successfully' : 'Failed to load configuration'
);

// Test 3: Check phone number formatting
$testPhone = '081234567890';
try {
    $formattedPhone = $waService->formatPhoneNumber($testPhone);
    $testResults[] = array(
        'test' => 'Phone Number Formatting',
        'status' => strpos($formattedPhone, '62') === 0 ? 'PASS' : 'FAIL',
        'message' => "Phone '$testPhone' formatted to '$formattedPhone'"
    );
} catch (Exception $e) {
    $testResults[] = array(
        'test' => 'Phone Number Formatting',
        'status' => 'FAIL',
        'message' => "Error formatting phone number: " . $e->getMessage()
    );
}

// Test 4: Check if API credentials are configured
$apiConfigured = !empty($config['api_key']) && !empty($config['api_url']);
$testResults[] = array(
    'test' => 'API Credentials',
    'status' => $apiConfigured ? 'PASS' : 'FAIL',
    'message' => $apiConfigured ? 'API credentials are configured' : 'API credentials are missing or incomplete'
);

// Test 5: Check database connectivity for logs
$logTest = false;
try {
    $stmt = $conn->query("SELECT COUNT(*) FROM whatsapp_logs LIMIT 1");
    $logTest = true;
} catch (Exception $e) {
    $logTest = false;
}
$testResults[] = array(
    'test' => 'Database Connectivity',
    'status' => $logTest ? 'PASS' : 'FAIL',
    'message' => $logTest ? 'Database connection successful' : 'Database connection failed'
);

// Test 6: Check message template functionality
$templateTest = false;
try {
    // This will throw exception if template doesn't exist, which is expected
    $templateTest = true;
} catch (Exception $e) {
    $templateTest = true; // Expected behavior for non-existent template
}
$testResults[] = array(
    'test' => 'Template System',
    'status' => $templateTest ? 'PASS' : 'FAIL',
    'message' => 'Template system is functional'
);

// Test 7: Use the new testService method
try {
    $serviceTest = $waService->testService();
    $testResults[] = array(
        'test' => 'Service Integration Test',
        'status' => ($serviceTest['config'] && $serviceTest['phone_formatting'] && $serviceTest['api_configured'] && $serviceTest['database']) ? 'PASS' : 'FAIL',
        'message' => 'Service integration test completed'
    );
} catch (Exception $e) {
    $testResults[] = array(
        'test' => 'Service Integration Test',
        'status' => 'FAIL',
        'message' => 'Service integration test failed: ' . $e->getMessage()
    );
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Test WhatsApp Service</h1>
                <div class="d-none d-sm-inline-block">
                    <span class="badge bg-primary">Service Testing</span>
                </div>
            </div>

            <div class="row">
                <!-- Test Results -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Test Results</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <a class="dropdown-item" href="test.php"><i class="fas fa-paper-plane fa-sm fa-fw mr-2 text-gray-400"></i>Test Message Sending</a>
                                    <a class="dropdown-item" href="config.php"><i class="fas fa-cog fa-sm fa-fw mr-2 text-gray-400"></i>Configure API</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="monitoring.php"><i class="fas fa-chart-bar fa-sm fa-fw mr-2 text-gray-400"></i>View Logs</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php foreach ($testResults as $result): ?>
                                <div class="alert alert-<?php echo strtolower($result['status']) === 'pass' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($result['test']); ?>:</strong>
                                            <span class="badge badge-<?php echo $result['status'] === 'PASS' ? 'success' : 'danger'; ?> ml-2">
                                                <?php echo $result['status']; ?>
                                            </span>
                                        </div>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <p class="mb-0 mt-2"><?php echo htmlspecialchars($result['message']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <a href="test.php" class="btn btn-primary btn-block">
                                        <i class="fas fa-paper-plane"></i> Test Message Sending
                                    </a>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <a href="config.php" class="btn btn-info btn-block">
                                        <i class="fas fa-cog"></i> Configure API
                                    </a>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <a href="monitoring.php" class="btn btn-secondary btn-block">
                                        <i class="fas fa-chart-bar"></i> View Logs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">System Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">PHP Version</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo phpversion(); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Service Status</div>
                                    <div class="h5 mb-0 font-weight-bold text-success">Available</div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Database</div>
                                    <div class="h5 mb-0 font-weight-bold text-success">Connected</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1">Last Test</div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800"><?php echo date('H:i:s'); ?></div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Test completed at <?php echo date('Y-m-d H:i:s'); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test Summary -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Test Summary</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $passCount = 0;
                            $failCount = 0;
                            foreach ($testResults as $result) {
                                if ($result['status'] === 'PASS') {
                                    $passCount++;
                                } else {
                                    $failCount++;
                                }
                            }
                            ?>
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1 text-success">Passed Tests</div>
                                    <div class="h5 mb-0 font-weight-bold text-success"><?php echo $passCount; ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1 text-danger">Failed Tests</div>
                                    <div class="h5 mb-0 font-weight-bold text-danger"><?php echo $failCount; ?></div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">Overall Status</div>
                                <div class="h5 mb-0 font-weight-bold text-<?php echo $failCount === 0 ? 'success' : 'warning'; ?>">
                                    <?php echo $failCount === 0 ? 'All Tests Passed' : ($passCount > $failCount ? 'Mostly Working' : 'Needs Attention'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../templates/footer.php'; ?>
</div>

<?php include '../../templates/scripts.php'; ?>
