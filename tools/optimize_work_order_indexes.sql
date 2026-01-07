-- Optimasi Index untuk Work Order
-- Jalankan query ini di phpMyAdmin atau MySQL client

-- Index untuk wo_header
ALTER TABLE wo_header 
ADD INDEX idx_status (status),
ADD INDEX idx_sales_contract (sales_contract_id),
ADD INDEX idx_fg (fg_id),
ADD INDEX idx_wo_number (wo_number);

-- Index untuk wo_details_material
ALTER TABLE wo_details_material
ADD INDEX idx_wo_header (wo_header_id),
ADD INDEX idx_component (component_item_id, component_item_type);

-- Index untuk sales_contracts (jika belum ada)
ALTER TABLE sales_contracts
ADD INDEX idx_status (status),
ADD INDEX idx_fg_id (fg_id);

-- Index untuk finished_goods (jika belum ada)
ALTER TABLE finished_goods
ADD INDEX idx_kode (kode_barang),
ADD INDEX idx_nama (nama_barang(100));

-- Index untuk materials (jika belum ada)
ALTER TABLE materials
ADD INDEX idx_kode (kode_material),
ADD INDEX idx_nama (nama_material(100)),
ADD INDEX idx_status (status);

-- Index untuk semifinished_goods (jika belum ada)
ALTER TABLE semifinished_goods
ADD INDEX idx_kode (kode_barang),
ADD INDEX idx_nama (nama_barang(100)),
ADD INDEX idx_status (status);

SELECT 'Index optimization completed!' as status;
