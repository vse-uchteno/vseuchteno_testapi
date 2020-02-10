<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/*
  #####################################################
  # Bitrix: Modules and Components tests              #
  # Copyright (c) 2020 D.Starovoytov (VseUchteno)     #
  # mailto:denis@starovoytov.online                   #
  #####################################################
 */
use Bitrix\Iblock\ElementTable as Element;
use Bitrix\Iblock\ElementPropertyTable as ElementProperties;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Highloadblock\HighloadBlockTable;
use \Bitrix\Main\UserFieldTable;
use Bitrix\Main\Loader;

Loader::includeModule("highloadblock");

Loc::loadMessages(__FILE__);

/**
 * Component class for test API
 */
class CVseuchtenoTestapi extends CBitrixComponent
{
        private $api_token, $iblock_id, $hlblock_id, $method;
        private $headers;
        private $api_errors, $api_response = [];
        private $api_params = [];
        
        /*Опишем методы и обязательные поля к ним*/
        private $availaible_methods= [
            'add' => [
                'NAME' => '',
                'ADDRESS' => '',
                'PHONE' => '',
                'TIME' => '',
                'TYPE' => '',
                
            ],
            'edit' => [],
            'delete' => [
                'ID' => '',
            ],
            'list' => [],
        ];
        
        private $ibOfficeTypes,$hlOfficeTypes = [];
        
        private $office_type_values=['обычный','VIP','круглосуточный'];
        
	public function executeComponent()
	{
            global $APPLICATION;
            $APPLICATION->RestartBuffer();
            
            if (!\Bitrix\Main\Loader::includeModule('iblock')) {
                $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_API_SYSTEM_ERROR');
                return "";
            }

            $context = Application::getInstance()->getContext();
            $request = $context->getRequest();
            
            if (!$request->isPost()) {
                $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_ERROR_IS_NO_POST');
                return "";
            }
            
            $this->api_token=$this->arParams['API_TOKEN'];
            $this->iblock_id=$this->arParams['OFFICE_IBLOCK'];
            $this->hlblock_id=$this->arParams['OFFICE_HLBLOCK'];
            $this->headers = $request->getHeaders();
            $this->method= $request->getPost('method');
            $this->api_params= $request->getPost('fields');
            
            
            $this->ibOfficeTypes = $this->getOfficeTypesFromIBlock();
            $this->hlOfficeTypes = $this->getOfficeTypesFromHLBlock();

            
            echo("<pre>");
            print_r($this->api_params);
            echo("</pre>");
            
            if ($this->checkAuth()) {
                if ($this->checkRequest()) {
                    $this->do();
                }
            }
            
            exit(json_encode($this->api_response));
            

	}

	
        private function do() {
            $result=true;
            switch ($this->method) {
                  case 'add':
                    $new_office_id=$this->apiAddOffice();  
                    if ($new_office_id === false) {
                         $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_API_ERROR_UNKNOWN');
                         $this->api_response['status'] = 'error';
                         $this->api_response['error'] = '';
                    } else {
                         $this->api_response['status'] = 'success';
                         $this->api_response['data'] = ['new_id' =>$new_office_id];
                         $this->api_response['error'] = '';

                    }  
                    break;

                default:
                    break;
            }
            
            
            return $result;
        }

        private function apiAddOffice() {
            
            $el=new CIBlockElement();
            $arData=[
                "IBLOCK_ID"      => $this->iblock_id,
                "PROPERTY_VALUES"=> [
                    "ADDRESS" => $this->api_params['ADDRESS'],
                    "PHONE" => $this->api_params['PHONE'],
                    "TIME" => $this->api_params['TIME'],
                    "TYPE" => $this->ibOfficeTypes[$this->api_params['TYPE']],
                ],
                "NAME"  => $this->api_params['NAME'], 
            ];
            $id=$el->add($arData);
            if ($id) {
                
                $arHFields=[
                    'UF_OFFICE_ID' => $id,
                    'UF_NAME' => $this->api_params['NAME'],
                    'UF_ADDRESS' => $this->api_params['ADDRESS'],
                    'UF_PHONE' => $this->api_params['PHONE'],
                    'UF_TIME' => $this->api_params['TIME'],
                    'UF_TYPE' => $this->hlOfficeTypes[$this->api_params['TYPE']],
                ];
                $arHLBlock = HighloadBlockTable::getById($this->hlblock_id)->fetch();
		$obEntity = HighloadBlockTable::compileEntity($arHLBlock);
		$hlDataClass = $obEntity->getDataClass();
                $obResult = $hlDataClass::add($arHFields);
                
                return $id; 
            } else {
                return false;
            }
            
            /*
             * Код не работает на заполнениии доп.свойств
             * Вроде всё сделано как здесь
             * https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=12868&LESSON_PATH=3913.5062.5748.12864.12868
             * 
            $iblock = \Bitrix\Iblock\Iblock::wakeUp($this->iblock_id);
            $newElement = $iblock->getEntityDataClass()::createObject();
            $newElement->setIblockId($this->iblock_id);
            $newElement->setName($this->api_params['NAME']);
            $newElement->setPhone($this->api_params['PHONE']);
            $res=$newElement->save();
            if ($res->isSuccess()) {
                return $res->getId();
            } else {
                return false;
            }*/
        }
        
        private function checkAuth() {
            $result=true;
            if (!$this->headers->get('token')) {
                $result=false;
                $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_ERROR_NO_TOKEN');
            } else {
                if ($this->headers->get('token') != $this->api_token) {
                    $result=false;
                    $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_ERROR_WRONG_TOKEN');
                }
            }
            return $result;
        }

	
        private function checkRequest() {
            $result=true;
            /*Проверка существования переданного метода*/
            if (!isset($this->availaible_methods[$this->method])) {
                $result=false;
                $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_ERROR_METHOD_NOT_FOUND');
            } else {
                
                /*Проверка корректности поля Тип офиса*/
                if (isset($this->api_params['TYPE']) && !in_array($this->api_params['TYPE'],$this->office_type_values)) {
                    $result=false;
                    $this->api_errors[]=sprintf(Loc::GetMessage('VU_TESTAPI_ERROR_TYPE_FIELD_NOT_CORRECT'), implode(", ", $this->office_type_values));
                }
                
                switch ($this->method) {
                    case 'edit':
                        /*Проверка чтобы при редактированиии было передано хоть одно поле, иначе операция бессмыссленна*/
                        if (empty($this->api_params)) {
                            $result=false;
                            $this->api_errors[]=Loc::GetMessage('VU_TESTAPI_ERROR_NOTHIN_EDIT');
                        }

                        break;

                    default:
                        /*Проверка обязательных полей для метода*/
                        $res=array_diff_key($this->availaible_methods[$this->method], $this->api_params);
                        if(!empty($res)) {
                            $result=false;
                            $this->api_errors[]=sprintf(Loc::GetMessage('VU_TESTAPI_ERROR_NO_SPECIFIED_REQUIRED_FIELD'), implode(", ", array_flip($res)));
                        }
                        break;
                }
            }
           
            return $result;
        }       
        
                
	/*
	 * Получает список значений Типов офиса для Инфоблока Офисов
	 */
	private function getOfficeTypesFromIBlock() 
	{
            	
		$result=[];
		
		$select=[
                    'ID','VALUE',
		];

		$filter=[
                    'PROPERTY.IBLOCK_ID' => $this->iblock_id,
                    'PROPERTY.CODE' => 'TYPE',
		];
		$arTypesResult = Bitrix\Iblock\PropertyEnumerationTable::getList([
			'select'  => $select,
			'filter'  => $filter,
		])->fetchAll();
		foreach ($arTypesResult as $typeItem) {
                    $result[$typeItem['VALUE']]=$typeItem['ID'];
		}
		
		return $result;
	}

                        
	/*
	 * Получает список значений Типов офиса для HLБлока Офисов
	 */
	private function getOfficeTypesFromHLBlock() 
	{
                $result=[];
		
		$select=[
                    'ID',
		];

		$filter=[
                    'ENTITY_ID' => 'HLBLOCK_'.$this->hlblock_id,
                    'FIELD_NAME' => 'UF_TYPE',
		];
		$arUFResult = \Bitrix\Main\UserFieldTable::getList([
			'select'  => $select,
			'filter'  => $filter,
		])->fetchAll();
                $arUFResult=reset($arUFResult);

                
                
                $enumValuesManager = new \CUserFieldEnum();
                $dbRes = $enumValuesManager->GetList(array(), array('USER_FIELD_ID' => $arUFResult['ID']));
                while($enumValue = $dbRes->fetch())
                {
                     $result[$enumValue['VALUE']]=$enumValue['ID'];
                }
		
		return $result;
	}

        
}
