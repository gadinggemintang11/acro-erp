<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get notifications from user_logs table
     */
    public function get_notifications($user_id = null, $limit = 10, $unread_only = false) {
        $this->db->select('id, log_time as created_at, username as created_by, module, action, description as message');
        $this->db->from('user_logs');
        
        // Filter by user if provided
        if ($user_id !== null && $user_id != '') {
            // Get username from user_id
            $user = $this->db->select('username')->where('user_id', $user_id)->get('tblbmuserm')->row();
            if ($user) {
                $this->db->where('username', $user->username);
            }
        }
        
        $this->db->order_by('log_time', 'DESC');
        $this->db->limit($limit);
        
        $logs = $this->db->get()->result();
        
        // Transform logs to notification format
        $notifications = array();
        foreach ($logs as $log) {
            $notifications[] = (object) array(
                'id' => $log->id,
                'title' => $this->get_action_title($log->action, $log->module),
                'message' => $log->message,
                'type' => $this->get_action_type($log->action),
                'icon' => $this->get_action_icon($log->action),
                'link' => null,
                'is_read' => 1, // All logs are considered read
                'created_at' => $log->created_at,
                'created_by' => $log->created_by
            );
        }
        
        return $notifications;
    }

    /**
     * Count recent notifications (last 24 hours)
     */
    public function count_unread($user_id = null) {
        $this->db->from('user_logs');
        
        // Get logs from last 24 hours
        $this->db->where('log_time >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
        
        // Filter by user if provided
        if ($user_id !== null && $user_id != '') {
            $user = $this->db->select('username')->where('user_id', $user_id)->get('tblbmuserm')->row();
            if ($user) {
                $this->db->where('username', $user->username);
            }
        }
        
        return $this->db->count_all_results();
    }

    /**
     * Get action title based on action and module
     */
    private function get_action_title($action, $module) {
        $titles = array(
            'LOGIN' => 'User Login',
            'LOGOUT' => 'User Logout',
            'CREATE' => 'New ' . $module . ' Created',
            'UPDATE' => $module . ' Updated',
            'DELETE' => $module . ' Deleted',
            'EXPORT' => $module . ' Exported',
            'IMPORT' => $module . ' Imported',
            'PRINT' => $module . ' Printed',
            'VIEW' => $module . ' Viewed'
        );
        
        return isset($titles[$action]) ? $titles[$action] : $action . ' - ' . $module;
    }

    /**
     * Get notification type based on action
     */
    private function get_action_type($action) {
        $types = array(
            'LOGIN' => 'success',
            'LOGOUT' => 'info',
            'CREATE' => 'success',
            'UPDATE' => 'warning',
            'DELETE' => 'danger',
            'EXPORT' => 'info',
            'IMPORT' => 'info',
            'PRINT' => 'info',
            'VIEW' => 'info'
        );
        
        return isset($types[$action]) ? $types[$action] : 'info';
    }

    /**
     * Get icon based on action
     */
    private function get_action_icon($action) {
        $icons = array(
            'LOGIN' => 'fa-sign-in-alt',
            'LOGOUT' => 'fa-sign-out-alt',
            'CREATE' => 'fa-plus-circle',
            'UPDATE' => 'fa-edit',
            'DELETE' => 'fa-trash-alt',
            'EXPORT' => 'fa-file-export',
            'IMPORT' => 'fa-file-import',
            'PRINT' => 'fa-print',
            'VIEW' => 'fa-eye'
        );
        
        return isset($icons[$action]) ? $icons[$action] : 'fa-bell';
    }

    /**
     * Mark notification as read (not applicable for log-based notifications)
     */
    public function mark_as_read($notification_id) {
        // Since we're using logs, we don't mark as read
        return true;
    }

    /**
     * Mark all notifications as read (not applicable for log-based notifications)
     */
    public function mark_all_as_read($user_id = null) {
        // Since we're using logs, we don't mark as read
        return true;
    }

    /**
     * Delete notification (delete log entry)
     */
    public function delete_notification($notification_id) {
        $this->db->where('id', $notification_id);
        return $this->db->delete('user_logs');
    }

    /**
     * Delete old notifications (older than specified days)
     */
    public function delete_old_notifications($days = 30) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $this->db->where('log_time <', $date);
        return $this->db->delete('user_logs');
    }
    
    /**
     * Check if table exists
     */
    public function table_exists() {
        return $this->db->table_exists('user_logs');
    }
    
    /**
     * Get recent important notifications
     */
    public function get_important_notifications($limit = 5) {
        $this->db->select('id, log_time as created_at, username as created_by, module, action, description as message');
        $this->db->from('user_logs');
        $this->db->where_in('action', array('CREATE', 'DELETE', 'EXPORT', 'LOGIN'));
        $this->db->where('log_time >=', date('Y-m-d H:i:s', strtotime('-24 hours')));
        $this->db->order_by('log_time', 'DESC');
        $this->db->limit($limit);
        
        $logs = $this->db->get()->result();
        
        $notifications = array();
        foreach ($logs as $log) {
            $notifications[] = (object) array(
                'id' => $log->id,
                'title' => $this->get_action_title($log->action, $log->module),
                'message' => $log->message,
                'type' => $this->get_action_type($log->action),
                'icon' => $this->get_action_icon($log->action),
                'link' => null,
                'is_read' => 0, // Mark as unread for important actions
                'created_at' => $log->created_at,
                'created_by' => $log->created_by
            );
        }
        
        return $notifications;
    }
}
