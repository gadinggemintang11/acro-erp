<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Notification_model');
        
        // Check if user is logged in
        if (!$this->session->userdata('logged_in')) {
            redirect('auth/login');
        }
    }

    /**
     * Get notifications (AJAX)
     */
    public function get_notifications() {
        try {
            $user_id = $this->session->userdata('user_id');
            $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
            
            // Check if table exists first
            if (!$this->Notification_model->table_exists()) {
                echo json_encode(array(
                    'status' => false,
                    'message' => 'Log activity table not found.',
                    'notifications' => array(),
                    'unread_count' => 0
                ));
                return;
            }
            
            // Get all recent notifications (not filtered by user for now)
            $notifications = $this->Notification_model->get_notifications(null, $limit);
            $unread_count = $this->Notification_model->count_unread(null);
            
            echo json_encode(array(
                'status' => true,
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'notifications' => array(),
                'unread_count' => 0
            ));
        }
    }

    /**
     * Mark notification as read (AJAX)
     */
    public function mark_as_read() {
        $notification_id = $this->input->post('id');
        
        if ($notification_id) {
            $result = $this->Notification_model->mark_as_read($notification_id);
            echo json_encode(array(
                'status' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark as read'
            ));
        } else {
            echo json_encode(array(
                'status' => false,
                'message' => 'Notification ID is required'
            ));
        }
    }

    /**
     * Mark all notifications as read (AJAX)
     */
    public function mark_all_as_read() {
        $user_id = $this->session->userdata('user_id');
        $result = $this->Notification_model->mark_all_as_read($user_id);
        
        echo json_encode(array(
            'status' => $result,
            'message' => $result ? 'All notifications marked as read' : 'Failed to mark as read'
        ));
    }

    /**
     * Delete notification (AJAX)
     */
    public function delete() {
        $notification_id = $this->input->post('id');
        
        if ($notification_id) {
            $result = $this->Notification_model->delete_notification($notification_id);
            echo json_encode(array(
                'status' => $result,
                'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
            ));
        } else {
            echo json_encode(array(
                'status' => false,
                'message' => 'Notification ID is required'
            ));
        }
    }

    /**
     * View all notifications page
     */
    public function index() {
        $data['page'] = 'Notification/Index';
        $data['title'] = 'All Notifications';
        
        $user_id = $this->session->userdata('user_id');
        $data['notifications'] = $this->Notification_model->get_notifications($user_id, 50);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('Page/Notification/Index', $data);
        $this->load->view('templates/footer');
    }
}
