<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/PHPExcel.php';

$list = [
	['col1' => '1', 'col2' => '甲', 'col3' => 'X', 'col4' => '99'],
	['col1' => '2', 'col2' => '乙', 'col3' => 'Y', 'col4' => '88'],
	['col1' => '3', 'col2' => '丙', 'col3' => 'Z', 'col4' => '77'],
	['col1' => '4', 'col2' => '丁', 'col3' => 'W', 'col4' => '66'],
];

// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
// Set properties
$objPHPExcel->getProperties()->setCreator("jason")
	->setLastModifiedBy("jason")
	->setTitle("Office 2010 XLSX Test Document")
	->setSubject("Office 2010 XLSX Test Document")
	->setDescription("Test document for Office 2010 XLSX, generated using PHP classes.")
	->setKeywords("office 2010 openxml php")
	->setCategory("Test result file");

// set width
$objActiveSheet = $objPHPExcel->getActiveSheet();
// $objActiveSheet->getColumnDimension('A')->setWidth(20);
// $objActiveSheet->getColumnDimension('B')->setWidth(20);
// $objActiveSheet->getColumnDimension('C')->setWidth(20);
// $objActiveSheet->getColumnDimension('D')->setWidth(20);

// 设置行高度
//$objActiveSheet->getRowDimension('1')->setRowHeight(22);

//$objActiveSheet->getRowDimension('2')->setRowHeight(20);

// 字体和样式
//$objActiveSheet->getDefaultStyle()->getFont()->setSize(10);
//$objActiveSheet->getStyle('A2:D2')->getFont()->setBold(true);
//$objActiveSheet->getStyle('A1')->getFont()->setBold(true);

//$objActiveSheet->getStyle('A2:D2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//$objActiveSheet->getStyle('A2:D2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

// 设置水平居中
// $objActiveSheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// $objActiveSheet->getStyle('A')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// $objActiveSheet->getStyle('B')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// $objActiveSheet->getStyle('C')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
// $objActiveSheet->getStyle('D')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

//  合并
$objActiveSheet->mergeCells('A1:D1');

// 表头
$objPHPExcel->setActiveSheetIndex(0)
	->setCellValue('A1', '演示数据')
	->setCellValue('A2', '序号')
	->setCellValue('B2', '姓名')
	->setCellValue('C2', '班级')
	->setCellValue('D2', '成绩');

// 内容
for ($i = 0, $len = count($list); $i < $len; $i++) {
	$objActiveSheet->setCellValue('A' . ($i + 3), $list[$i]['col1']);
	$objActiveSheet->setCellValue('B' . ($i + 3), $list[$i]['col2']);
	$objActiveSheet->setCellValue('C' . ($i + 3), $list[$i]['col3']);
	$objActiveSheet->setCellValue('D' . ($i + 3), $list[$i]['col4']);
	//$objActiveSheet->getStyle('D' . ($i + 3))->getFont()->getColor()->setRGB('FF0000');
	//$objActiveSheet->getStyle('A' . ($i + 3) . ':D' . ($i + 3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	//$objActiveSheet->getStyle('A' . ($i + 3) . ':D' . ($i + 3))->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
	//$objActiveSheet->getRowDimension($i + 3)->setRowHeight(16);
}

// Rename sheet
$objActiveSheet->setTitle('演示数据');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// 输出
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="演示数据.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');