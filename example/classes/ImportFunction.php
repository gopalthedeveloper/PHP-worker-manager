<?php
require_once __DIR__.'/../includes/common.php';
include __DIR__.'/../classes/ChunkReadFilter.php';


use PhpOffice\PhpSpreadsheet\IOFactory;


class ImportFunction
{
    const IMPORT_PENDING = 1;
	const IMPORT_PROCESSING = 2;
	const IMPORT_COMPLETED = 3;
    const IMPORT_ERROR = 4;
    
    public static function getEmployeeExcelUpdate($importId, $type = self::IMPORT_PENDING)
    {
        include __DIR__.'/../includes/conn.php';
        

        try {
            $sql = "SELECT * FROM `import` WHERE import_status = {$type} and import_id =" . $importId;
            $query = $conn->query($sql);
            
            if ( $query->num_rows == 0) {
                throw new Exception("Invalid import id-".$importId);
            }
            $importData = $query->fetch_assoc();
    
            $inputFileName = __DIR__ . '/../uploads/excel/' . $importData['filename'];
            
            if (!file_exists($inputFileName)) {
                throw new Exception("File not found");
            }
            $sql = "UPDATE  `import` set `import_status` = ".self::IMPORT_PROCESSING."  WHERE import_id =" . $importId;
            if (!$conn->query($sql)) {
                throw new Exception('Cannot import processing id='.$importId);
            };
            $header = $importData['processed_rows'] + 1;

            
            $inputFileType = IOFactory::identify($inputFileName);

            /**  Create a new Reader of the type defined in $inputFileType  **/
            $reader =   IOFactory::createReader($inputFileType);

            $worksheetData = $reader->listWorksheetInfo($inputFileName);


            /** Define how many rows we want to read for each "chunk" **/
            $chunkSize = 100;
            /** Create a new Instance of our Read Filter **/
            $chunkFilter = new chunkReadFilter();

            /** Tell the Reader that we want to use the Read Filter **/
            $reader->setReadFilter($chunkFilter);

            /** Loop to read our worksheet in "chunk size" blocks **/
            $totalRows = reset($worksheetData)['totalRows'];
            
            for ($startRow = 1+$header; $startRow <= $totalRows; $startRow += $chunkSize) {
                /** Tell the Read Filter which rows we want this iteration **/
                $chunkFilter->setRows($startRow, $chunkSize);

                /** Load only the rows that match our filter **/
                $objPHPExcel = $reader->load($inputFileName);

                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);
                // print_r($sheetData);die;
                // print_r([$startRow-2, $chunkSize]);
                $sheetData= array_slice($sheetData,$startRow-1, $chunkSize,true);
                $rowId = 0;
                foreach($sheetData as $rowId => $employee) {
                    if($employee['A'] == 'employee_id'){
                        continue;
                    }
                    $sql = "SELECT `id`, `employee_no`, `firstname`, `lastname`, `address`, `birthdate`, `contact_info`, `gender`, `created_on` FROM `employees` WHERE employee_no = '{$employee['A']}'";
                    $query = $conn->query($sql);


                    $dob = date('Y-m-d',strtotime(str_replace('/','-',$employee['E'])));
                    $gender = (strtolower($employee['G']) == 'male')?'Male':'Female';
                    $conn->begin_transaction();

                    if($query->num_rows == 0) {

                        $sql = "INSERT INTO `employees`( `employee_no`, `firstname`, `lastname`, `address`, `birthdate`, `contact_info`, `gender`, `created_on`) VALUES ('{$employee['A']}','{$employee['B']}','{$employee['C']}','{$employee['D']}','{$dob}','".(int)$employee['F']."','{$gender}','".date('Y-m-d H:i:s')."')";

                        if(!$conn->query($sql)) {
                            goto skipRecord;
                        };
                        $empId = $conn->insert_id;

                    } else {
                        $row = $query->fetch_assoc();
                        $empId = $row['id'];

                        $sql = "UPDATE `employees` SET `firstname`='{$employee['B']}',`lastname`='{$employee['C']}',`address`='{$employee['D']}',`birthdate`='{$dob}',`contact_info`='{$employee['F']}',`gender`='{$gender}' WHERE `id` = ".$empId;

                        if(!$conn->query($sql)) {
                            goto skipRecord;
                        };
                    }

                    $sql = "SELECT `employee_salary_id`, `employee_id`, `salary`, `created_at`, `updated_at` FROM `employee_salary` WHERE  employee_id = '{$empId}'";
                    $query = $conn->query($sql);

                    
                    if($query->num_rows == 0) {
                        $sql = "INSERT INTO `employee_salary`( `employee_id`, `salary`, `created_at`) VALUES ({$empId},'".(double)$employee['H']."','".date('Y-m-d H:i:s')."')";

                        if(!$conn->query($sql)) {
                            goto skipRecord;
                        };
                    } else {
                        
                        $sql = "UPDATE `employee_salary` SET `salary`='".(double)$employee['H']."',`updated_at`='".date('Y-m-d H:i:s')."' WHERE employee_id = {$empId}";

                        if(!$conn->query($sql)) {
                            goto skipRecord;
                        };
                    }
                    $conn->commit(); 
                    continue;
                    skipRecord:
                    $conn->rollback(); 
                }
                if($rowId != 0) {
                    $sql = "UPDATE  `import` set `processed_rows` = ".$rowId." WHERE import_id =" . $importId;
                    $query = $conn->query($sql);
                }
            }
            $sql = "UPDATE  `import` set `import_status` = ".self::IMPORT_COMPLETED." WHERE import_id =" . $importId;
            $query = $conn->query($sql);
        } catch (\Exception $e) {
            $sql = "UPDATE  `import` set `import_status` = ".self::IMPORT_ERROR." WHERE import_id =" . $importId;
            $query = $conn->query($sql);
            print_r($e);
        }

        $conn->close();
    }
}
