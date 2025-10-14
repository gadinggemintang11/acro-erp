<?php

namespace App\Controllers;

use App\Models\SupplierModel;

class Supplier extends BaseController
{
    protected $supplierModel;

    public function __construct()
    {
        $this->supplierModel = new SupplierModel();
    }

    // Menampilkan daftar supplier
    public function index()
    {
        $data = [
            'title' => 'Master Supplier',
            'suppliers' => $this->supplierModel->findAll()
        ];
        return view('supplier/index', $data);
    }

    // Menampilkan form tambah data
    public function new()
    {
        $data = [
            'title' => 'Tambah Supplier Baru'
        ];
        return view('supplier/new', $data);
    }

    // Proses menyimpan data baru
    public function create()
    {
        $this->supplierModel->save([
            'supplier_code' => $this->request->getVar('supplier_code'),
            'supplier_name' => $this->request->getVar('supplier_name'),
            'address' => $this->request->getVar('address'),
            'phone' => $this->request->getVar('phone'),
        ]);

        return redirect()->to('/supplier')->with('success', 'Data supplier berhasil ditambahkan.');
    }

    // Dan seterusnya untuk edit, update, delete...
}