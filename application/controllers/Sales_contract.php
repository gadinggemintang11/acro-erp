<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_contract extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        // Muat semua model yang dibutuhkan untuk form
        $this->load->model(['Sc_model', 'Customer_model', 'Fg_model', 'Commoncode_model']);
    }

    public function index() {
        if (!check_access('sales_contract', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'     => 'Sales Contract',
            'contracts' => $this->Sc_model->get_all(),
            // Data untuk modal
            'customers' => $this->Customer_model->get_all(['status' => 'active']),
            'styles'    => $this->Fg_model->get_all(['status' => 'active']), // Ambil data style
            'currencies'=> $this->Commoncode_model->get_by_group('CURRENCY')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('sales_contract/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('sales_contract', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $po_qty = (int) $this->input->post('po_qty');
        $unit_price = (float) str_replace(',', '', $this->input->post('unit_price'));
        $total_price = $po_qty * $unit_price;

        $data = [
            'sc_number'     => $this->Sc_model->generate_sc_number(),
            'customer_id'   => $this->input->post('customer_id'),
            'fg_id'         => $this->input->post('fg_id'),
            'contract_date' => $this->input->post('contract_date'),
            'lchd_date'     => $this->input->post('lchd_date'),
            'po_qty'        => $po_qty,
            'currency_code' => $this->input->post('currency_code'),
            'unit_price'    => $unit_price,
            'total_price'   => $total_price,
            'destination'   => $this->input->post('destination'),
            'status'        => 'Open',
        ];

        if ($this->Sc_model->insert($data)) {
            $this->session->set_flashdata('success', 'Sales Contract berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('sales_contract');
    }

    public function update($id) {
        if (!check_access('sales_contract', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $po_qty = (int) $this->input->post('po_qty');
        $unit_price = (float) str_replace(',', '', $this->input->post('unit_price'));
        $total_price = $po_qty * $unit_price;

        $data = [
            'customer_id'   => $this->input->post('customer_id'),
            'fg_id'         => $this->input->post('fg_id'),
            'contract_date' => $this->input->post('contract_date'),
            'lchd_date'     => $this->input->post('lchd_date'),
            'po_qty'        => $po_qty,
            'currency_code' => $this->input->post('currency_code'),
            'unit_price'    => $unit_price,
            'total_price'   => $total_price,
            'destination'   => $this->input->post('destination'),
            'status'        => $this->input->post('status'),
        ];

        if ($this->Sc_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Sales Contract berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('sales_contract');
    }

    public function delete($id) {
        if (!check_access('sales_contract', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        if ($this->Sc_model->delete($id)) {
            $this->session->set_flashdata('success', 'Sales Contract berhasil dibatalkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan data!');
        }
        redirect('sales_contract');
    }
}