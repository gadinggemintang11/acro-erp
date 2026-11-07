<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Cron job for PRF Payment Notification
 * Send notification on the 1st of every month for unpaid PRFs
 *
 * Panggil dari command line:
 * php /path/to/index.php cron_prf_payment_notify send_notification
 */

class Cron_prf_payment_notify extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Prf_model');
        $this->load->library('email');
    }

    /**
     * Send payment notification for unpaid PRFs
     * Dijalankan otomatis setiap tanggal 1 pukul 08:00
     */
    public function send_notification() {
        log_message('info', 'Payment Request Payment Notification - Starting...');

        try {
            // Get all unpaid PRFs that have been approved by Finance
            $unpaid_prfs = $this->Prf_model->get_unpaid_prfs();

            if (empty($unpaid_prfs)) {
                log_message('info', 'Payment Request Payment Notification - No unpaid PRFs found');
                return;
            }

            // Get finance department users for notification
            $finance_users = $this->_get_finance_users();

            if (empty($finance_users)) {
                log_message('warning', 'Payment Request Payment Notification - No finance users found for notification');
                return;
            }

            // Prepare notification data
            $total_amount = 0;
            $total_prfs = count($unpaid_prfs);

            foreach ($unpaid_prfs as $prf) {
                $total_amount += $prf->subtotal;
            }

            // Send notification email to each finance user
            foreach ($finance_users as $user) {
                $this->_send_notification_email($user, $unpaid_prfs, $total_amount, $total_prfs);
            }

            // Log to database for tracking
            $this->_log_notification($total_prfs, $total_amount);

            log_message('info', 'Payment Request Payment Notification - Sent successfully. Total PRFs: ' . $total_prfs . ', Total Amount: ' . $total_amount);

        } catch (Exception $e) {
            log_message('error', 'Payment Request Payment Notification Error: ' . $e->getMessage());
        }
    }

    /**
     * Get finance department users for notification
     */
    private function _get_finance_users() {
        $this->db->select('id, email, fullname');
        $this->db->from('users');
        // Adjust this query based on your role/department structure
        $this->db->where_in('role_id', [3, 4]); // Finance roles (adjust as needed)
        $this->db->where('is_active', 1);
        return $this->db->get()->result();
    }

    /**
     * Send notification email
     */
    private function _send_notification_email($user, $unpaid_prfs, $total_amount, $total_count) {
        // Prepare email content
        $html_content = $this->_generate_email_html($unpaid_prfs, $total_amount, $total_count);

        // Configure email
        $this->email->clear();
        $this->email->from('noreply@company.com', 'ERP System');
        $this->email->to($user->email);
        $this->email->subject('[Payment Request Payment Reminder] Monthly Payment Due - ' . date('F Y'));
        $this->email->message($html_content);

        // Send email
        if (!$this->email->send()) {
            log_message('error', 'Failed to send Payment Request notification to ' . $user->email . ': ' . $this->email->print_debugger());
        } else {
            log_message('info', 'Payment Request notification sent to ' . $user->email);
        }
    }

    /**
     * Generate HTML email content
     */
    private function _generate_email_html($unpaid_prfs, $total_amount, $total_count) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #2c3e50; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
                .content { background-color: #f5f5f5; padding: 20px; border: 1px solid #ddd; }
                .summary { background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .summary-item { margin-bottom: 10px; font-size: 14px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background-color: white; }
                th { background-color: #34495e; color: white; padding: 10px; text-align: left; font-weight: bold; }
                td { padding: 10px; border-bottom: 1px solid #ddd; }
                tr:hover { background-color: #f9f9f9; }
                .footer { background-color: #2c3e50; color: white; padding: 15px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; }
                .amount { text-align: right; font-weight: bold; }
                .action-link { display: inline-block; background-color: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Payment Request Payment Reminder</h2>
                    <p>Monthly Payment Due Notification</p>
                </div>

                <div class="content">
                    <p>Dear Finance Team,</p>
                    <p>This is a monthly reminder for Payment Requests (Payment Request) that are waiting for payment. Please find the details below:</p>

                    <div class="summary">
                        <div class="summary-item">
                            <strong>Total Unpaid PRFs:</strong> <?php echo $total_count; ?>
                        </div>
                        <div class="summary-item">
                            <strong>Total Amount Due:</strong> Rp <?php echo number_format($total_amount, 2, ',', '.'); ?>
                        </div>
                        <div class="summary-item">
                            <strong>Notification Date:</strong> <?php echo date('d-m-Y H:i'); ?>
                        </div>
                    </div>

                    <h3>Unpaid Payment Request Details</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Payment Request Number</th>
                                <th>Date</th>
                                <th>Requester</th>
                                <th class="amount">Amount</th>
                                <th class="amount">Days Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($unpaid_prfs as $prf): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prf->prf_number); ?></strong></td>
                                    <td><?php echo date('d-m-Y', strtotime($prf->prf_date)); ?></td>
                                    <td><?php echo htmlspecialchars($prf->requester_name); ?></td>
                                    <td class="amount">Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></td>
                                    <td class="amount"><?php echo (int)((strtotime('now') - strtotime($prf->created_at)) / 86400); ?> days</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p>Please review and process the payment for these PRFs as soon as possible.</p>

                    <a href="<?php echo base_url('prf'); ?>" class="action-link">View Payment Request Details</a>
                </div>

                <div class="footer">
                    <p>This is an automated message from the ERP System.</p>
                    <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(company_info('company_name')); ?>. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Log notification to database for tracking
     */
    private function _log_notification($total_prfs, $total_amount) {
        // Create a log entry in a notification_logs table if you want to track this
        // For now, just use CodeIgniter's log_message function
        $data = [
            'notification_type' => 'PRF_PAYMENT_REMINDER',
            'total_prfs' => $total_prfs,
            'total_amount' => $total_amount,
            'sent_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->table_exists('notification_logs')) {
            $this->db->insert('notification_logs', $data);
        }
    }
}
?>
