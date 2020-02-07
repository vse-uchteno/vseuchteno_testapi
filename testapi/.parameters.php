<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\TypeLanguageTable;
use Bitrix\Highloadblock\HighloadBlockTable;

$arIblockTypes[]='';
$arIblocks[]='';
$arHBlocks[]='';


$arIblockTypesFilter=array(
	'LANGUAGE_ID' => SITE_ID,
);

$arIblockTypesResult=TypeLanguageTable::getList(array('filter' => $arIblockTypesFilter))->fetchAll();
foreach($arIblockTypesResult as $arIblockTypeItem) {
	$arIblockTypes[$arIblockTypeItem['IBLOCK_TYPE_ID']] = $arIblockTypeItem['NAME'];
}



if (isset($arCurrentValues['OFFICE_IBLOCK_TYPE']) && ($arCurrentValues['OFFICE_IBLOCK_TYPE'] != "")) {
	
	$arIblocksFilter=array(
		'IBLOCK_TYPE_ID' => $arCurrentValues['OFFICE_IBLOCK_TYPE'],
	);
	$arIblocksResult=IblockTable::getList(array('filter' => $arIblocksFilter))->fetchAll();
	foreach($arIblocksResult as $arIblockItem) {
		$arIblocks[$arIblockItem['ID']] = $arIblockItem['NAME'];
	}
	
}

Loader::includeModule("highloadblock");

$dbHBlocks = HighloadBlockTable::getList(array())->fetchAll();
foreach($dbHBlocks as $hblock)
{
	$arHBlocks[$hblock['ID']] = "[" . $hblock["ID"] . "] " . $hblock["NAME"];
}

$arComponentParameters = array(
	'PARAMETERS'=>array(
		"API_TOKEN"=>array(
			"PARENT"=>"BASE",
			"NAME"=>GetMessage("VU_API_TOKEN"),
			"TYPE"=>"STRING",
			"MULTIPLE"=>"N",
			"DEFAULT"=>"111222333",
			"COLS"=>25
		),
		"OFFICE_IBLOCK_TYPE" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("VU_OFFICE_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arIblockTypes,
			"REFRESH" => "Y"
		),
		"OFFICE_IBLOCK" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("VU_OFFICE_IBLOCK"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arIblocks,
		),
		"OFFICE_HLBLOCK" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("VU_OFFICE_HLBLOCK"),
			"TYPE" => "LIST",
			"ADDITIONAL_VALUES" => "N",
			"VALUES" => $arHBlocks,
		),
	),
);
?>