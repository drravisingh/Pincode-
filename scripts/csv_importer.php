<?php
/**
 * ================================================
 * CSV BULK IMPORT SCRIPT
 * Efficiently imports 1.65+ lakh records
 * Uses batch processing to avoid memory issues
 * ================================================
 */

class PincodeImporter {
    
    private $db;
    private $batch_size = 1000; // Process 1000 rows at a time
    private $import_id;
    
    public function __construct($db_connection) {
        $this->db = $db_connection;
        
        // Increase execution time and memory for large imports
        set_time_limit(0);
        ini_set('memory_limit', '512M');
    }
    
    /**
     * Main import function
     */
    public function importCSV($file_path, $admin_id = null) {
        
        // Create import history record
        $this->import_id = $this->createImportRecord($file_path, $admin_id);
        
        // Open CSV file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $this->updateImportStatus('failed', 'Could not open file');
            return false;
        }
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $total_rows = 0;
        $imported_rows = 0;
        $failed_rows = 0;
        $batch_data = [];
        $errors = [];
        
        echo "Starting import...\n";
        
        // Read and process CSV row by row
        while (($row = fgetcsv($handle)) !== false) {
            $total_rows++;
            
            // Validate and prepare data
            $data = $this->prepareRowData($row);
            
            if ($data) {
                $batch_data[] = $data;
            } else {
                $failed_rows++;
                $errors[] = "Row $total_rows: Invalid data";
            }
            
            // Insert batch when batch size is reached
            if (count($batch_data) >= $this->batch_size) {
                $result = $this->insertBatch($batch_data);
                $imported_rows += $result;
                $batch_data = []; // Clear batch
                
                // Progress update
                echo "Processed $total_rows rows, Imported: $imported_rows, Failed: $failed_rows\n";
                
                // Update import history
                $this->updateImportProgress($total_rows, $imported_rows, $failed_rows);
            }
        }
        
        // Insert remaining batch
        if (count($batch_data) > 0) {
            $result = $this->insertBatch($batch_data);
            $imported_rows += $result;
        }
        
        fclose($handle);
        
        // Final update
        $this->updateImportStatus('completed', implode("\n", $errors));
        $this->updateImportProgress($total_rows, $imported_rows, $failed_rows);
        
        echo "\n✅ Import completed!\n";
        echo "Total rows: $total_rows\n";
        echo "Imported: $imported_rows\n";
        echo "Failed: $failed_rows\n";
        
        // Generate nearby PIN codes (optional, can run separately)
        // $this->generateNearbyPincodes();
        
        return [
            'success' => true,
            'total' => $total_rows,
            'imported' => $imported_rows,
            'failed' => $failed_rows
        ];
    }
    
    /**
     * Prepare single row data
     */
    private function prepareRowData($row) {
        
        // CSV columns mapping
        $data = [
            'circlename' => isset($row[0]) ? trim($row[0]) : null,
            'regionname' => isset($row[1]) ? trim($row[1]) : null,
            'divisionname' => isset($row[2]) ? trim($row[2]) : null,
            'officename' => isset($row[3]) ? trim($row[3]) : null,
            'pincode' => isset($row[4]) ? trim($row[4]) : null,
            'officetype' => isset($row[5]) ? strtoupper(trim($row[5])) : 'BO',
            'delivery' => isset($row[6]) ? trim($row[6]) : 'Delivery',
            'district' => isset($row[7]) ? trim($row[7]) : null,
            'statename' => isset($row[8]) ? trim($row[8]) : null,
            'latitude' => isset($row[9]) && $row[9] != 'NA' ? floatval($row[9]) : null,
            'longitude' => isset($row[10]) && $row[10] != 'NA' ? floatval($row[10]) : null,
        ];
        
        // Validation
        if (empty($data['pincode']) || empty($data['officename'])) {
            return false;
        }
        
        // Validate PIN code format (6 digits)
        if (!preg_match('/^\d{6}$/', $data['pincode'])) {
            return false;
        }
        
        // Validate coordinates (India bounds approximately)
        if ($data['latitude'] !== null && ($data['latitude'] < 6 || $data['latitude'] > 38)) {
            $data['latitude'] = null;
        }
        if ($data['longitude'] !== null && ($data['longitude'] < 68 || $data['longitude'] > 98)) {
            $data['longitude'] = null;
        }
        
        // Generate slug
        $data['slug'] = $this->generateSlug($data['pincode'], $data['officename']);
        
        return $data;
    }
    
    /**
     * Insert batch of records using prepared statement
     */
    private function insertBatch($batch_data) {
        
        if (empty($batch_data)) {
            return 0;
        }
        
        try {
            // Start transaction for better performance
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO pincode_master 
                    (circlename, regionname, divisionname, officename, pincode, 
                     officetype, delivery, district, statename, latitude, longitude, slug) 
                    VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    circlename = VALUES(circlename),
                    regionname = VALUES(regionname),
                    divisionname = VALUES(divisionname),
                    officename = VALUES(officename),
                    officetype = VALUES(officetype),
                    delivery = VALUES(delivery),
                    district = VALUES(district),
                    statename = VALUES(statename),
                    latitude = VALUES(latitude),
                    longitude = VALUES(longitude),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            
            $inserted = 0;
            foreach ($batch_data as $data) {
                $stmt->execute([
                    $data['circlename'],
                    $data['regionname'],
                    $data['divisionname'],
                    $data['officename'],
                    $data['pincode'],
                    $data['officetype'],
                    $data['delivery'],
                    $data['district'],
                    $data['statename'],
                    $data['latitude'],
                    $data['longitude'],
                    $data['slug']
                ]);
                $inserted++;
            }
            
            // Commit transaction
            $this->db->commit();
            
            return $inserted;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollBack();
            echo "Error inserting batch: " . $e->getMessage() . "\n";
            return 0;
        }
    }
    
    /**
     * Generate SEO-friendly slug
     */
    private function generateSlug($pincode, $officename) {
        $slug = strtolower($officename);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $pincode . '-' . $slug;
    }
    
    /**
     * Create import history record
     */
    private function createImportRecord($filename, $admin_id) {
        $sql = "INSERT INTO import_history (filename, imported_by, status, started_at) 
                VALUES (?, ?, 'processing', NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([basename($filename), $admin_id]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Update import progress
     */
    private function updateImportProgress($total, $imported, $failed) {
        $sql = "UPDATE import_history 
                SET total_rows = ?, imported_rows = ?, failed_rows = ? 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$total, $imported, $failed, $this->import_id]);
    }
    
    /**
     * Update import status
     */
    private function updateImportStatus($status, $error_log = null) {
        $sql = "UPDATE import_history 
                SET status = ?, error_log = ?, completed_at = NOW() 
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $error_log, $this->import_id]);
    }
    
    /**
     * Generate nearby PIN codes (optional, can be run separately)
     */
    public function generateNearbyPincodes($radius_km = 50) {
        
        echo "\nGenerating nearby PIN codes...\n";
        
        // Clear existing data
        $this->db->exec("TRUNCATE TABLE nearby_pincodes");
        
        // Get all PIN codes with valid coordinates
        $sql = "SELECT pincode, latitude, longitude 
                FROM pincode_master 
                WHERE latitude IS NOT NULL 
                AND longitude IS NOT NULL
                AND latitude BETWEEN 6 AND 38
                AND longitude BETWEEN 68 AND 98";
        
        $pincodes = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $batch_data = [];
        $processed = 0;
        
        foreach ($pincodes as $pincode) {
            
            // Calculate nearby PIN codes using Haversine formula
            $sql = "SELECT 
                        pincode,
                        (
                            6371 * acos(
                                cos(radians(?))
                                * cos(radians(latitude))
                                * cos(radians(longitude) - radians(?))
                                + sin(radians(?))
                                * sin(radians(latitude))
                            )
                        ) AS distance_km
                    FROM pincode_master
                    WHERE pincode != ?
                    AND latitude IS NOT NULL
                    AND longitude IS NOT NULL
                    HAVING distance_km <= ?
                    ORDER BY distance_km
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $pincode['latitude'],
                $pincode['longitude'],
                $pincode['latitude'],
                $pincode['pincode'],
                $radius_km
            ]);
            
            $nearby = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($nearby as $near) {
                $batch_data[] = [
                    $pincode['pincode'],
                    $near['pincode'],
                    $near['distance_km']
                ];
            }
            
            // Insert batch
            if (count($batch_data) >= 500) {
                $this->insertNearbyBatch($batch_data);
                $batch_data = [];
            }
            
            $processed++;
            if ($processed % 100 == 0) {
                echo "Processed $processed PIN codes...\n";
            }
        }
        
        // Insert remaining
        if (count($batch_data) > 0) {
            $this->insertNearbyBatch($batch_data);
        }
        
        echo "✅ Nearby PIN codes generated!\n";
    }
    
    /**
     * Insert nearby PIN codes batch
     */
    private function insertNearbyBatch($batch_data) {
        if (empty($batch_data)) return;
        
        $sql = "INSERT INTO nearby_pincodes (pincode, nearby_pincode, distance_km) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        foreach ($batch_data as $data) {
            $stmt->execute($data);
        }
    }
}

// ================================================
// USAGE EXAMPLE
// ================================================

/*
// Database connection
$db = new PDO('mysql:host=localhost;dbname=pincode_db', 'username', 'password');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create importer instance
$importer = new PincodeImporter($db);

// Import CSV file
$result = $importer->importCSV('path/to/pincode_data.csv', $admin_id);

if ($result['success']) {
    echo "Import successful!\n";
    echo "Total: {$result['total']}\n";
    echo "Imported: {$result['imported']}\n";
    echo "Failed: {$result['failed']}\n";
}

// Optional: Generate nearby PIN codes (run separately, takes time)
// $importer->generateNearbyPincodes(50); // 50 km radius
*/

?>