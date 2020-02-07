<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/*
  #####################################################
  # Bitrix: Modules and Components tests              #
  # Copyright (c) 2020 D.Starovoytov (VseUchteno)     #
  # mailto:denis@starovoytov.online                   #
  #####################################################
 */
use Bitrix\Iblock\PropertyTable as Property;
use Bitrix\Iblock\ElementPropertyTable as ElementProperties;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Diag;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;


Loc::loadMessages(__FILE__);

/**
 * Component class for test API
 */
class CVseuchtenoTestapi extends CBitrixComponent
{
	
	public function executeComponent()
	{
         

	}
	
}
