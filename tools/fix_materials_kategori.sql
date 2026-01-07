-- Fix materials table: Change kategori from ENUM to VARCHAR
-- Reason: Dynamic categories from common_code need flexibility

USE neokaber_mingbao;

-- Backup current ENUM values (optional, for reference)
-- Current ENUM: 'Kain','Outsole','Insole','Benang','Lem','Lainnya'

-- Modify kategori column from ENUM to VARCHAR(50)
ALTER TABLE `materials` 
MODIFY COLUMN `kategori` VARCHAR(50) DEFAULT 'Lainnya' COMMENT 'Material category from common_code.MATERIAL_CATEGORY';

-- Modify asal column from ENUM to VARCHAR for consistency
ALTER TABLE `materials` 
MODIFY COLUMN `asal` VARCHAR(20) DEFAULT 'Lokal' COMMENT 'Origin: Lokal/Impor/Import from common_code.ITEM_ORIGIN';

-- Show the new structure
DESCRIBE materials;

SELECT 'Materials table structure updated successfully!' as status;
