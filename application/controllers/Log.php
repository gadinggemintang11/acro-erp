<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Log_model');
    }

    public function index()
    {
        $data = $this->data;
        $data['title'] = 'Log Aktivitas Sistem';
        $data['content'] = 'log/index';
        $data['logs'] = $this->Log_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }
}