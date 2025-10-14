<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MaterialModel;

class Material extends BaseController
{
    protected $materialModel;

    public function __construct()
    {
        $this->materialModel = new MaterialModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Master Material',
            'materials' => $this->materialModel->findAll()
        ];
        return view('material/index', $data);
    }
    
    // TAMBAHKAN DARI SINI ===================================
    
    /**
     * Method untuk menampilkan halaman form tambah data
     */
    public function new()
    {
        $data = [
            'title' => 'Tambah Material Baru',
        ];
        return view('material/new', $data);
    }

    /**
     * Method untuk memproses penyimpanan data dan auto-generate kode
     */
    public function create()
    {
        // 1. Ambil Tipe dari Form (import/lokal)
        $type = $this->request->getPost('item_type');
        $prefix = ($type == 'import') ? 'IMP' : 'LOK';
        
        // 2. Ambil Tahun
        $year = date('y'); // Misal: 25 untuk tahun 2025
        
        // 3. Cari Kode Terakhir di Database
        $lastMaterial = $this->materialModel
                                ->like('item_code', "$prefix-RM-$year-", 'after')
                                ->orderBy('item_code', 'DESC')
                                ->first();
        
        // 4. Buat Nomor Urut Baru
        if ($lastMaterial) {
            $lastNum = (int)substr($lastMaterial['item_code'], -5);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1; // Jika ini data pertama
        }

        $sequence = str_pad($newNum, 5, '0', STR_PAD_LEFT); // Format menjadi 00001
        
        // 5. Gabungkan menjadi Kode Barang Final
        $itemCode = "$prefix-RM-$year-$sequence"; // Hasil: IMP-RM-25-00001
        
        // 6. Simpan ke Database
        $this->materialModel->save([
            'item_code'   => $itemCode,
            'item_name'   => $this->request->getPost('item_name'),
            'hs_code'     => $this->request->getPost('hs_code'),
            'item_type'   => $type,
            'unit'        => $this->request->getPost('unit'),
        ]);

        // Kirim pesan sukses ke halaman berikutnya
        session()->setFlashdata('success', 'Material baru berhasil dibuat dengan kode: ' . $itemCode);

        // Arahkan kembali ke halaman daftar material
        return redirect()->to('/material');
    }

    // SAMPAI SINI =========================================
}