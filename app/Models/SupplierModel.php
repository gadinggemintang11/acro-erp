<?php

namespace App\Models;

use CodeIgniter\Model;

class SupplierModel extends Model
{
    protected $table            = 'suppliers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['supplier_code', 'supplier_name', 'address', 'phone'];
    protected $useTimestamps    = true; // Otomatis mengisi created_at & updated_at
}