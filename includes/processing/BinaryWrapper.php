<?php
/**
 * Binary Wrapper Class
 *
 * @package WyoshiImageOptimizer\Processing
 * @since 1.0.0
 */

namespace WyoshiImageOptimizer\Processing;

use WyoshiImageOptimizer\Common\Logger;
use WyoshiImageOptimizer\Common\Utils;

/**
 * Binary Wrapper Class
 *
 * Handles execution of image optimization binaries (cwebp, cavif, etc.)
 *
 * @since 1.0.0
 */
class BinaryWrapper {

    /**
     * Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Binary directory path
     *
     * @var string
     */
    private $bin_dir;

    /**
     * System architecture
     *
     * @var string
     */
    private $architecture;

    /**
     * Operating system
     *
     * @var string
     */
    private $os;

    /**
     * Available binaries
     *
     * @var array
     */
    private $binaries = [
        'cwebp' => [
            'name' => 'cwebp',
            'description' => 'WebP encoder',
            'required' => true,
            'version_flag' => '-version',
            'test_command' => '-h'
        ],
        'dwebp' => [
            'name' => 'dwebp',
            'description' => 'WebP decoder',
            'required' => false,
            'version_flag' => '-version',
            'test_command' => '-h'
        ],
        'cavif' => [
            'name' => 'cavif',
            'description' => 'AVIF encoder',
            'required' => false,
            'version_flag' => '--version',
            'test_command' => '--help'
        ],
        'davif' => [
            'name' => 'davif',
            'description' => 'AVIF decoder',
            'required' => false,
            'version_flag' => '--version',
            'test_command' => '--help'
        ],
        'jpegoptim' => [
            'name' => 'jpegoptim',
            'description' => 'JPEG optimizer',
            'required' => false,
            'version_flag' => '--version',
            'test_command' => '--help'
        ],
        'optipng' => [
            'name' => 'optipng',
            'description' => 'PNG optimizer',
            'required' => false,
            'version_flag' => '-v',
            'test_command' => '-h'
        ],
        'gifsicle' => [
            'name' => 'gifsicle',
            'description' => 'GIF optimizer',
            'required' => false,
            'version_flag' => '--version',
            'test_command' => '--help'
        ]
    ];

    /**
     * Binary status cache
     *
     * @var array
     */
    private $binary_status = [];

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->bin_dir = WYOSHI_IMG_OPT_BIN_DIR;
        $this->detect_system();
        $this->load_cached_binary_status();
    }

    /**
     * Detect system architecture and OS
     *
     * @return void
     */
    private function detect_system() {
        // Detect operating system
        if (PHP_OS_FAMILY === 'Windows') {
            $this->os = 'windows';
        } elseif (PHP_OS_FAMILY === 'Linux') {
            $this->os = 'linux';
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            $this->os = 'darwin';
        } else {
            $this->os = 'unknown';
        }

        // Detect architecture
        $arch = strtolower(php_uname('m'));
        if (strpos($arch, 'x86_64') !== false || strpos($arch, 'amd64') !== false) {
            $this->architecture = 'x64';
        } elseif (strpos($arch, 'arm64') !== false || strpos($arch, 'aarch64') !== false) {
            $this->architecture = 'arm64';
        } elseif (strpos($arch, 'i386') !== false || strpos($arch, 'i686') !== false) {
            $this->architecture = 'i386';
        } else {
            $this->architecture = 'unknown';
        }

        $this->logger->info('System detected', [
            'os' => $this->os,
            'architecture' => $this->architecture,
            'php_os' => PHP_OS,
            'php_os_family' => PHP_OS_FAMILY
        ]);
    }

    /**
     * Get platform string
     *
     * @return string Platform string
     */
    public function get_platform() {
        return $this->os . '-' . $this->architecture;
    }

    /**
     * Load cached binary status or check binaries if cache is invalid
     *
     * @return void
     */
    private function load_cached_binary_status() {
        $cache_key = 'wyoshi_img_opt_binary_status_' . $this->get_platform();
        $cached_status = get_transient($cache_key);
        
        if ($cached_status !== false && is_array($cached_status)) {
            $this->binary_status = $cached_status;
            $this->logger->debug('Loaded cached binary status', $this->binary_status);
            return;
        }
        
        // Cache miss or invalid, check binaries
        $this->check_binaries();
        
        // Cache the result for 1 hour
        set_transient($cache_key, $this->binary_status, HOUR_IN_SECONDS);
    }

    /**
     * Check binary availability
     *
     * @return void
     */
    private function check_binaries() {
        foreach ($this->binaries as $name => $config) {
            $this->binary_status[$name] = $this->test_binary($name);
        }

        $this->logger->info('Binary status check complete', $this->binary_status);
    }

    /**
     * Test if a binary is available and working
     *
     * @param string $binary_name Binary name
     * @return array Binary status information
     */
    public function test_binary($binary_name) {
        if (!isset($this->binaries[$binary_name])) {
            return [
                'available' => false,
                'error' => 'Unknown binary'
            ];
        }

        $config = $this->binaries[$binary_name];
        $binary_path = $this->get_binary_path($binary_name);

        if (!$binary_path) {
            return [
                'available' => false,
                'error' => 'Binary not found',
                'path' => null
            ];
        }

        // Test binary execution
        $test_result = $this->execute_binary($binary_name, $config['test_command'], [], false);

        if ($test_result['success']) {
            // Get version information
            $version_result = $this->execute_binary($binary_name, $config['version_flag'], [], false);
            $version = $this->parse_version($version_result['output'] ?? '', $binary_name);

            return [
                'available' => true,
                'path' => $binary_path,
                'version' => $version,
                'description' => $config['description']
            ];
        }

        return [
            'available' => false,
            'error' => $test_result['error'] ?? 'Execution failed',
            'path' => $binary_path
        ];
    }

    /**
     * Get binary path
     *
     * @param string $binary_name Binary name
     * @return string|false Binary path or false if not found
     */
    private function get_binary_path($binary_name) {
        $extension = ($this->os === 'windows') ? '.exe' : '';
        $binary_filename = $binary_name . $extension;

        // Check platform-specific directory first
        $platform_dir = $this->bin_dir . $this->os . '-' . $this->architecture . '/';
        $platform_path = $platform_dir . $binary_filename;

        if (file_exists($platform_path) && is_executable($platform_path)) {
            return $platform_path;
        }

        // Check generic platform directory
        $generic_platform_dir = $this->bin_dir . $this->os . '/';
        $generic_platform_path = $generic_platform_dir . $binary_filename;

        if (file_exists($generic_platform_path) && is_executable($generic_platform_path)) {
            return $generic_platform_path;
        }

        // Check root bin directory
        $root_path = $this->bin_dir . $binary_filename;
        if (file_exists($root_path) && is_executable($root_path)) {
            return $root_path;
        }

        // Check system PATH
        $system_path = $this->find_in_path($binary_filename);
        if ($system_path) {
            return $system_path;
        }

        return false;
    }

    /**
     * Find binary in system PATH
     *
     * @param string $binary_filename Binary filename
     * @return string|false Binary path or false if not found
     */
    private function find_in_path($binary_filename) {
        $path_env = getenv('PATH');
        if (!$path_env) {
            return false;
        }

        $path_separator = ($this->os === 'windows') ? ';' : ':';
        $paths = explode($path_separator, $path_env);

        foreach ($paths as $path) {
            $full_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $binary_filename;
            if (file_exists($full_path) && is_executable($full_path)) {
                return $full_path;
            }
        }

        return false;
    }

    /**
     * Execute binary with arguments
     *
     * @param string $binary_name Binary name
     * @param string $arguments Command arguments
     * @param array $options Execution options
     * @param bool $log_output Whether to log output
     * @return array Execution result
     */
    public function execute_binary($binary_name, $arguments = '', $options = [], $log_output = true) {
        $binary_path = $this->get_binary_path($binary_name);
        
        if (!$binary_path) {
            $error = "Binary '{$binary_name}' not found";
            if ($log_output) {
                $this->logger->error($error);
            }
            return [
                'success' => false,
                'error' => $error,
                'output' => '',
                'exit_code' => -1
            ];
        }

        // Build command
        $command = escapeshellarg($binary_path);
        if (!empty($arguments)) {
            $command .= ' ' . $arguments;
        }

        // Set execution options
        $timeout = $options['timeout'] ?? 30;
        $working_dir = $options['working_dir'] ?? null;
        $env_vars = $options['env'] ?? null;

        if ($log_output) {
            $this->logger->debug('Executing binary', [
                'binary' => $binary_name,
                'command' => $command,
                'timeout' => $timeout
            ]);
        }

        // Execute command
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, $working_dir, $env_vars);

        if (!is_resource($process)) {
            $error = "Failed to start process for '{$binary_name}'";
            if ($log_output) {
                $this->logger->error($error);
            }
            return [
                'success' => false,
                'error' => $error,
                'output' => '',
                'exit_code' => -1
            ];
        }

        // Close stdin
        fclose($pipes[0]);

        // Set timeout for reading
        $start_time = time();
        $stdout = '';
        $stderr = '';

        // Read output with timeout
        while (time() - $start_time < $timeout) {
            $read = [$pipes[1], $pipes[2]];
            $write = null;
            $except = null;

            $ready = stream_select($read, $write, $except, 1);

            if ($ready > 0) {
                foreach ($read as $stream) {
                    $data = fread($stream, 8192);
                    if ($stream === $pipes[1]) {
                        $stdout .= $data;
                    } else {
                        $stderr .= $data;
                    }
                }
            }

            // Check if process is still running
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
        }

        // Close pipes
        fclose($pipes[1]);
        fclose($pipes[2]);

        // Get exit code
        $exit_code = proc_close($process);

        $success = ($exit_code === 0);
        $output = trim($stdout);
        $error_output = trim($stderr);

        if ($log_output) {
            if ($success) {
                $this->logger->debug('Binary execution successful', [
                    'binary' => $binary_name,
                    'exit_code' => $exit_code,
                    'output_length' => strlen($output)
                ]);
            } else {
                $this->logger->error('Binary execution failed', [
                    'binary' => $binary_name,
                    'exit_code' => $exit_code,
                    'error' => $error_output,
                    'output' => $output
                ]);
            }
        }

        return [
            'success' => $success,
            'output' => $output,
            'error' => $error_output,
            'exit_code' => $exit_code
        ];
    }

    /**
     * Parse version from binary output
     *
     * @param string $output Binary output
     * @param string $binary_name Binary name
     * @return string Parsed version
     */
    private function parse_version($output, $binary_name) {
        if (empty($output)) {
            return 'unknown';
        }

        // Common version patterns
        $patterns = [
            '/version\s+([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
            '/v([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i',
            '/([0-9]+\.[0-9]+(?:\.[0-9]+)?)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches)) {
                return $matches[1];
            }
        }

        // Binary-specific parsing
        switch ($binary_name) {
            case 'cwebp':
            case 'dwebp':
                if (preg_match('/([0-9]+\.[0-9]+\.[0-9]+)/', $output, $matches)) {
                    return $matches[1];
                }
                break;

            case 'cavif':
            case 'davif':
                if (preg_match('/cavif\s+([0-9]+\.[0-9]+(?:\.[0-9]+)?)/i', $output, $matches)) {
                    return $matches[1];
                }
                break;
        }

        return 'unknown';
    }

    /**
     * Check if binary is available
     *
     * @param string $binary_name Binary name
     * @return bool True if available
     */
    public function is_binary_available($binary_name) {
        return isset($this->binary_status[$binary_name]) && $this->binary_status[$binary_name]['available'];
    }

    /**
     * Get binary status
     *
     * @param string|null $binary_name Binary name or null for all
     * @return array Binary status
     */
    public function get_binary_status($binary_name = null) {
        if ($binary_name === null) {
            return $this->binary_status;
        }

        return $this->binary_status[$binary_name] ?? [
            'available' => false,
            'error' => 'Unknown binary'
        ];
    }

    /**
     * Get available binaries
     *
     * @return array Available binaries
     */
    public function get_available_binaries() {
        $available = [];
        foreach ($this->binary_status as $name => $status) {
            if ($status['available']) {
                $available[$name] = $status;
            }
        }
        return $available;
    }

    /**
     * Get missing required binaries
     *
     * @return array Missing required binaries
     */
    public function get_missing_required_binaries() {
        $missing = [];
        foreach ($this->binaries as $name => $config) {
            if ($config['required'] && !$this->is_binary_available($name)) {
                $missing[$name] = $config;
            }
        }
        return $missing;
    }

    /**
     * Refresh binary status
     *
     * @return void
     */
    public function refresh_binary_status() {
        $this->binary_status = [];
        $this->check_binaries();
        
        // Update cache
        $cache_key = 'wyoshi_img_opt_binary_status_' . $this->get_platform();
        set_transient($cache_key, $this->binary_status, HOUR_IN_SECONDS);
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public function get_system_info() {
        return [
            'os' => $this->os,
            'architecture' => $this->architecture,
            'php_os' => PHP_OS,
            'php_os_family' => PHP_OS_FAMILY,
            'bin_dir' => $this->bin_dir,
            'binaries' => $this->binary_status
        ];
    }

    /**
     * Convert image using WebP
     *
     * @param string $input_path Input image path
     * @param string $output_path Output WebP path
     * @param array $options Conversion options
     * @return array Conversion result
     */
    public function convert_to_webp($input_path, $output_path, $options = []) {
        if (!$this->is_binary_available('cwebp')) {
            return [
                'success' => false,
                'error' => 'cwebp binary not available'
            ];
        }

        $quality = $options['quality'] ?? 80;
        $method = $options['method'] ?? 4;
        $preset = $options['preset'] ?? 'default';

        $arguments = sprintf(
            '-q %d -m %d -preset %s %s -o %s',
            intval($quality),
            intval($method),
            escapeshellarg($preset),
            escapeshellarg($input_path),
            escapeshellarg($output_path)
        );

        return $this->execute_binary('cwebp', $arguments, $options);
    }

    /**
     * Convert image using AVIF
     *
     * @param string $input_path Input image path
     * @param string $output_path Output AVIF path
     * @param array $options Conversion options
     * @return array Conversion result
     */
    public function convert_to_avif($input_path, $output_path, $options = []) {
        if (!$this->is_binary_available('cavif')) {
            return [
                'success' => false,
                'error' => 'cavif binary not available'
            ];
        }

        $quality = $options['quality'] ?? 75;
        $speed = $options['speed'] ?? 4;

        $arguments = sprintf(
            '--quality %d --speed %d --output %s %s',
            intval($quality),
            intval($speed),
            escapeshellarg($output_path),
            escapeshellarg($input_path)
        );

        return $this->execute_binary('cavif', $arguments, $options);
    }

    /**
     * Optimize JPEG using jpegoptim
     *
     * @param string $file_path JPEG file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    public function optimize_jpeg($file_path, $options = []) {
        if (!$this->is_binary_available('jpegoptim')) {
            return [
                'success' => false,
                'error' => 'jpegoptim binary not available'
            ];
        }

        $quality = $options['quality'] ?? 85;
        $progressive = $options['progressive'] ?? true;
        $strip_metadata = $options['strip_metadata'] ?? true;

        $arguments = sprintf(
            '--max=%d %s %s %s',
            intval($quality),
            $progressive ? '--all-progressive' : '',
            $strip_metadata ? '--strip-all' : '',
            escapeshellarg($file_path)
        );

        return $this->execute_binary('jpegoptim', $arguments, $options);
    }

    /**
     * Optimize PNG using optipng
     *
     * @param string $file_path PNG file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    public function optimize_png($file_path, $options = []) {
        if (!$this->is_binary_available('optipng')) {
            return [
                'success' => false,
                'error' => 'optipng binary not available'
            ];
        }

        $level = $options['level'] ?? 2;
        $strip_metadata = $options['strip_metadata'] ?? true;

        $arguments = sprintf(
            '-o%d %s %s',
            intval($level),
            $strip_metadata ? '-strip all' : '',
            escapeshellarg($file_path)
        );

        return $this->execute_binary('optipng', $arguments, $options);
    }

    /**
     * Optimize GIF using gifsicle
     *
     * @param string $file_path GIF file path
     * @param array $options Optimization options
     * @return array Optimization result
     */
    public function optimize_gif($file_path, $options = []) {
        if (!$this->is_binary_available('gifsicle')) {
            return [
                'success' => false,
                'error' => 'gifsicle binary not available'
            ];
        }

        $level = $options['level'] ?? 3;

        $arguments = sprintf(
            '-O%d --batch %s',
            intval($level),
            escapeshellarg($file_path)
        );

        return $this->execute_binary('gifsicle', $arguments, $options);
    }
}