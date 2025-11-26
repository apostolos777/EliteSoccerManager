<?php
// Syntax checker for database_factory.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Factory Syntax Checker</h1>";

echo "<h2>Testing database_factory.php syntax</h2>";

if (file_exists('database_factory.php')) {
    $content = file_get_contents('database_factory.php');
    echo "<p>File size: " . strlen($content) . " bytes</p>";
    
    // Check for syntax errors using php -l equivalent
    $tempFile = tempnam(sys_get_temp_dir(), 'syntax_check');
    file_put_contents($tempFile, $content);
    
    // Try to parse the file
    $tokens = @token_get_all($content);
    if ($tokens === false) {
        echo "<p style='color:red'>✗ Failed to tokenize the file</p>";
    } else {
        echo "<p style='color:green'>✓ File tokenized successfully</p>";
    }
    
    // Try to evaluate syntax safely by invoking the PHP linter on a temp file
    $code = file_get_contents('database_factory.php');
    // Remove opening tag if present
    $code_checked = preg_replace('/^<\?php\s*/', '', $code);
    $tempLint = tempnam(sys_get_temp_dir(), 'php_lint_');
    if ($tempLint !== false) {
        // Ensure file ends up as a valid PHP file for linting
        $tempFile = $tempLint . '.php';
        file_put_contents($tempFile, "<?php\n" . $code_checked);

        // Use php -l to lint. Use escapeshellarg to be safe.
        $cmd = 'php -l ' . escapeshellarg($tempFile) . ' 2>&1';
        $output = [];
        $exit = 1;
        // Suppress warnings if exec is disabled
        if (function_exists('exec')) {
            exec($cmd, $output, $exit);
            if ($exit === 0) {
                echo "<p style='color:green'>✓ No obvious syntax errors found (php -l)</p>";
            } else {
                echo "<p style='color:red'>✗ Syntax check failed:</p>";
                echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
            }
        } else {
            // Fallback: rely on token_get_all result from earlier
            echo "<p style='color:orange'>⚠️ exec() disabled; falling back to tokenization only — this is a weaker check.</p>";
        }

        // Cleanup
        @unlink($tempFile);
        @unlink($tempLint);
    } else {
        echo "<p style='color:orange'>⚠️ Could not create temp file for linting; tokenization result used instead.</p>";
    }
    
    unlink($tempFile);
} else {
    echo "<p style='color:red'>✗ database_factory.php not found</p>";
}

echo "<h2>Testing simple database factory</h2>";

// Create a minimal working version right here
echo "<p>Creating minimal DatabaseFactory inline...</p>";

class DatabaseFactory {
    private static $instance = null;
    private $connection = null;
    
    private $host = 'localhost';
    private $dbname = 'ehostcoz_wp995';
    private $username = 'ehostcoz_vivoapp';
    private $password = 'VIVOAPP_ADMIN';
    private $port = 3306;
    
    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

echo "<p style='color:green'>✓ Inline DatabaseFactory class created</p>";

// Test the inline version
try {
    $db = DatabaseFactory::getInstance()->getConnection();
    echo "<p style='color:green'>✓ Database connection successful with inline class</p>";
    
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p style='color:green'>✓ Database query successful: " . $result['test'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.php'>Try Main Dashboard</a></p>";
echo "<p><a href='setup-database.php'>Run Database Setup</a></p>";
?>
