<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/PHPExcel.php';

$filename = 'abc.xlsx';

$objPHPExcel = PHPExcel_IOFactory::load($filename);

$objWorksheet = $objPHPExcel->getActiveSheet();
$highestRow = $objWorksheet->getHighestRow();
$highestColumn = $objWorksheet->getHighestColumn();
$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);

$html = '<table><tbody>';
for ($row = 1; $row <= $highestRow; $row++) {
	$html .= '<tr>';
	for ($col = 0; $col < $highestColumnIndex; $col++) {
		$html .= '<td>';
		$html .= (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
		$html .= '</td>';
	}
	$html .= '</tr>';
}
$html .= '</tbody></table>';

header('Content-type:text/html;charset:utf-8');
echo $html;