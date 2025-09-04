<?php
/**
 * Logger Class
 *
 * @package WyoshiImageOptimizer\Common
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Common;

/**
 * Logger Class
 *
 * Provides logging functionality for the plugin
 *
 * @since 1.0.0
 */
class Logger {

    /**
     * Log levels
     */
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * Log level priorities
     *
     * @var array
     */
    private static $level_priorities = [
        self::EMERGENCY => 800,
        self::ALERT => 700,
        self::CRITICAL => 600,
        self::ERROR => 500,
        self::WARNING => 400,
        self::NOTICE => 300,
        self::INFO => 200,
        self::DEBUG => 100
    ];

    /**
     * Logger context
     *
     * @var string
     */
    private $context;

    /**
     * Log file path
     *
     * @var string
     */
    private $log_file;

    /**
     * Minimum log level
     *
     * @var string
     */
    private $min_level;

    /**
     * Maximum log file size in bytes
     *
     * @var int
     */
    private $max_file_size;

    /**
     * Constructor
     *
     * @param string $context Logger context
     * @param string $min_level Minimum log level
     */
    public function __construct($context = 'general', $min_level = self::INFO) {
        $this->context = $context;
        $this->min_level = $min_level;
        $this->max_file_size = 10 * 1024 * 1024; // 10MB
        
        $this->setup_log_file();
    }

    /**
     * Setup log file path
     */
    private function setup_log_file() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/advanced-image-optimizer/logs';
        
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $this->log_file = $log_dir . '/' . $this->context . '.log';
        
        // Create .htaccess for security
        $htaccess_file = $log_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Advanced Image Optimizer Logs Security\nOptions -Indexes\n<Files *.log>\nOrder allow,deny\nDeny from all\n</Files>";
            file_put_contents($htaccess_file, $htaccess_content);
        }
    }

    /**
     * Log emergency message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function emergency($message, array $context = []) {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Log alert message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function alert($message, array $context = []) {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Log critical message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function critical($message, array $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function error($message, array $context = []) {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function warning($message, array $context = []) {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Log notice message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function notice($message, array $context = []) {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function info($message, array $context = []) {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Log debug message
     *
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function debug($message, array $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }

    /**
     * Log message with specified level
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     */
    public function log($level, $message, array $context = []) {
        // Check if logging is enabled
        if (!$this->is_logging_enabled()) {
            return;
        }
        
        // Check minimum log level
        if (!$this->should_log($level)) {
            return;
        }
        
        // Prevent duplicate log entries within the same request
        static $logged_messages = [];
        $message_hash = md5($level . $message . serialize($context));
        if (isset($logged_messages[$message_hash])) {
            return;
        }
        $logged_messages[$message_hash] = true;
        
        // Rotate log file if needed
        $this->rotate_log_if_needed();
        
        // Format log entry
        $log_entry = $this->format_log_entry($level, $message, $context);
        
        // Write to log file
        $this->write_to_file($log_entry);
        
        // Also log to WordPress debug.log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[Advanced Image Optimizer] ' . $log_entry);
        }
    }

    /**
     * Check if logging is enabled
     *
     * @return bool True if logging is enabled
     */
    private function is_logging_enabled() {
        $options = get_option('wyoshi_img_opt_options', []);
        return isset($options['enable_logging']) ? $options['enable_logging'] : true;
    }

    /**
     * Check if message should be logged based on level
     *
     * @param string $level Log level
     * @return bool True if should log
     */
    private function should_log($level) {
        if (!isset(self::$level_priorities[$level]) || !isset(self::$level_priorities[$this->min_level])) {
            return false;
        }
        
        return self::$level_priorities[$level] >= self::$level_priorities[$this->min_level];
    }

    /**
     * Format log entry
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     * @return string Formatted log entry
     */
    private function format_log_entry($level, $message, array $context = []) {
        $timestamp = current_time('Y-m-d H:i:s');
        $level_upper = strtoupper($level);
        
        // Interpolate context variables in message
        $message = $this->interpolate($message, $context);
        
        // Build log entry
        $log_entry = "[{$timestamp}] {$level_upper}: {$message}";
        
        // Add context if present
        if (!empty($context)) {
            $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $log_entry .= " Context: {$context_json}";
        }
        
        // Add memory usage for debug level
        if ($level === self::DEBUG) {
            $memory_usage = Utils::format_file_size(memory_get_usage(true));
            $log_entry .= " Memory: {$memory_usage}";
        }
        
        return $log_entry;
    }

    /**
     * Interpolate context variables in message
     *
     * @param string $message Message with placeholders
     * @param array $context Context variables
     * @return string Interpolated message
     */
    private function interpolate($message, array $context = []) {
        $replace = [];
        
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        
        return strtr($message, $replace);
    }

    /**
     * Write log entry to file
     *
     * @param string $log_entry Log entry
     */
    private function write_to_file($log_entry) {
        if (!is_writable(dirname($this->log_file))) {
            return;
        }
        
        $log_entry .= PHP_EOL;
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Rotate log file if it exceeds maximum size
     */
    private function rotate_log_if_needed() {
        if (!file_exists($this->log_file)) {
            return;
        }
        
        if (filesize($this->log_file) <= $this->max_file_size) {
            return;
        }
        
        // Create backup filename
        $backup_file = $this->log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        
        // Move current log to backup
        if (rename($this->log_file, $backup_file)) {
            // Clean up old backup files (keep only 5 most recent)
            $this->cleanup_old_backups();
        }
    }

    /**
     * Clean up old backup log files
     */
    private function cleanup_old_backups() {
        $log_dir = dirname($this->log_file);
        $context = $this->context;
        
        $backup_files = glob($log_dir . '/' . $context . '.log.*.bak');
        
        if (count($backup_files) <= 5) {
            return;
        }
        
        // Sort by modification time (oldest first)
        usort($backup_files, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remove oldest files, keep only 5 most recent
        $files_to_remove = array_slice($backup_files, 0, -5);
        foreach ($files_to_remove as $file) {
            unlink($file);
        }
    }

    /**
     * Get log file path
     *
     * @return string Log file path
     */
    public function get_log_file() {
        return $this->log_file;
    }

    /**
     * Get log file contents
     *
     * @param int $lines Number of lines to read (0 for all)
     * @return string Log file contents
     */
    public function get_log_contents($lines = 0) {
        if (!file_exists($this->log_file)) {
            return '';
        }
        
        if ($lines === 0) {
            return file_get_contents($this->log_file);
        }
        
        // Read last N lines
        $file_lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($file_lines === false) {
            return '';
        }
        
        $total_lines = count($file_lines);
        $start_line = max(0, $total_lines - $lines);
        
        return implode(PHP_EOL, array_slice($file_lines, $start_line));
    }

    /**
     * Clear log file
     *
     * @return bool Success status
     */
    public function clear_log() {
        if (!file_exists($this->log_file)) {
            return true;
        }
        
        return file_put_contents($this->log_file, '') !== false;
    }

    /**
     * Get log file size
     *
     * @return int Log file size in bytes
     */
    public function get_log_size() {
        if (!file_exists($this->log_file)) {
            return 0;
        }
        
        return filesize($this->log_file);
    }

    /**
     * Get formatted log file size
     *
     * @return string Formatted log file size
     */
    public function get_formatted_log_size() {
        return Utils::format_file_size($this->get_log_size());
    }

    /**
     * Check if log file exists
     *
     * @return bool True if log file exists
     */
    public function log_file_exists() {
        return file_exists($this->log_file);
    }

    /**
     * Get all available log contexts
     *
     * @return array Available log contexts
     */
    public static function get_available_contexts() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/advanced-image-optimizer/logs';
        
        if (!is_dir($log_dir)) {
            return [];
        }
        
        $log_files = glob($log_dir . '/*.log');
        $contexts = [];
        
        foreach ($log_files as $log_file) {
            $filename = basename($log_file, '.log');
            $contexts[] = $filename;
        }
        
        return $contexts;
    }

    /**
     * Get log statistics
     *
     * @return array Log statistics
     */
    public function get_log_stats() {
        if (!file_exists($this->log_file)) {
            return [
                'size' => 0,
                'formatted_size' => '0 B',
                'lines' => 0,
                'last_modified' => null
            ];
        }
        
        $file_lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $line_count = $file_lines ? count($file_lines) : 0;
        
        return [
            'size' => $this->get_log_size(),
            'formatted_size' => $this->get_formatted_log_size(),
            'lines' => $line_count,
            'last_modified' => filemtime($this->log_file)
        ];
    }

    /**
     * Set minimum log level
     *
     * @param string $level Minimum log level
     */
    public function set_min_level($level) {
        if (isset(self::$level_priorities[$level])) {
            $this->min_level = $level;
        }
    }

    /**
     * Get minimum log level
     *
     * @return string Minimum log level
     */
    public function get_min_level() {
        return $this->min_level;
    }

    /**
     * Set maximum log file size
     *
     * @param int $size Maximum file size in bytes
     */
    public function set_max_file_size($size) {
        $this->max_file_size = max(1024 * 1024, $size); // Minimum 1MB
    }

    /**
     * Get maximum log file size
     *
     * @return int Maximum file size in bytes
     */
    public function get_max_file_size() {
        return $this->max_file_size;
    }

    /**
     * Log performance metrics
     *
     * @param string $operation Operation name
     * @param float $start_time Start time
     * @param array $additional_data Additional data
     */
    public function log_performance($operation, $start_time, array $additional_data = []) {
        $execution_time = microtime(true) - $start_time;
        $memory_usage = Utils::get_memory_usage();
        
        $context = array_merge([
            'operation' => $operation,
            'execution_time' => round($execution_time, 4) . 's',
            'memory_current' => $memory_usage['current_formatted'],
            'memory_peak' => $memory_usage['peak_formatted']
        ], $additional_data);
        
        $this->info('Performance: {operation} completed in {execution_time}', $context);
    }

    /**
     * Log binary execution
     *
     * @param string $binary Binary name
     * @param string $command Command executed
     * @param int $exit_code Exit code
     * @param string $output Command output
     * @param string $error Command error
     */
    public function log_binary_execution($binary, $command, $exit_code, $output = '', $error = '') {
        $level = ($exit_code === 0) ? self::INFO : self::ERROR;
        
        $context = [
            'binary' => $binary,
            'command' => $command,
            'exit_code' => $exit_code,
            'output' => $output,
            'error' => $error
        ];
        
        $message = 'Binary execution: {binary} (exit code: {exit_code})';
        $this->log($level, $message, $context);
    }

    /**
     * Log image processing result
     *
     * @param string $operation Operation type
     * @param string $file_path File path
     * @param bool $success Success status
     * @param array $details Processing details
     */
    public function log_image_processing($operation, $file_path, $success, array $details = []) {
        $level = $success ? self::INFO : self::ERROR;
        
        $context = array_merge([
            'operation' => $operation,
            'file' => basename($file_path),
            'success' => $success
        ], $details);
        
        $status = $success ? 'successful' : 'failed';
        $message = 'Image processing: {operation} {status} for {file}';
        $context['status'] = $status;
        
        $this->log($level, $message, $context);
    }
}