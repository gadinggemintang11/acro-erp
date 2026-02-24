<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration runner controller.
 * Access: http://localhost:8080/migrate
 *
 * IMPORTANT: Remove or restrict this controller after use in production.
 */
class Migrate extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Only allow from CLI or localhost
        if (!$this->input->is_cli_request() && $this->input->ip_address() !== '127.0.0.1') {
            show_error('Migration runner is only accessible from localhost.', 403);
        }
    }

    /**
     * Run all pending migrations up to the latest.
     * URL: /migrate  OR  php index.php migrate
     */
    public function index() {
        $this->config->set_item('migration_enabled', TRUE);
        $this->load->library('migration');

        $result = $this->migration->latest();

        if ($result === FALSE) {
            show_error('Migration failed: ' . $this->migration->error_string());
        }

        echo '<pre>';
        echo "✅ Migration complete.\n";
        echo 'Current version: ' . $this->migration->get_version() . "\n";
        echo '</pre>';
    }

    /**
     * Roll back the latest migration.
     * URL: /migrate/rollback  OR  php index.php migrate rollback
     */
    public function rollback() {
        $this->config->set_item('migration_enabled', TRUE);
        $this->load->library('migration');

        $current  = (int)$this->migration->get_version();
        $previous = $current - 1;

        if ($previous < 0) {
            echo '<pre>Already at version 0. Nothing to roll back.</pre>';
            return;
        }

        $result = $this->migration->version($previous);

        if ($result === FALSE) {
            show_error('Rollback failed: ' . $this->migration->error_string());
        }

        echo '<pre>';
        echo "✅ Rolled back to version: $previous\n";
        echo '</pre>';
    }
}
