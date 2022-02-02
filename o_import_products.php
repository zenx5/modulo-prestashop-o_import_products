<?php

	if(!defined('_PS_VERSION_')) {
		exit;
	}	

	include_once 'classModuleFD.php';
	
	class CSVImport{

		public $content = "";
		public $delimiter = ",";
		public $lines = array();
		public $vars = array();
		public $collection = array();

		function __construct($content, $delimiter = ","){
			$this->content = $content;
			$this->delimiter = $delimiter;
		}

		public function getLines(){
			$this->lines = explode("\n", $this->content);
		}

		public function get_vars(){
			$this->vars = explode($this->delimiter, $this->lines[0]);

			foreach ($this->vars as $index => $var) {
				$this->vars[$index] = str_replace(" ", "_", strtolower($var));
			}

			return $this->vars;
		}

		public function get_values($line){
			return explode($this->delimiter, $line);
		}

		public function getCollection( $vars = array() ){
			$start = 1;
			$this->getLines();
			if( count($vars) == 0 ){
				$this->vars = $this->get_vars();
			}
			else{
				$this->vars = $vars;
				$start = 0;
			}
			for($k = $start; $k < count($this->lines); $k++){
				$values = $this->get_values($this->lines[$k]);
				$this->collection[ $k - $start ] = array();
				
				foreach ($this->vars as $index => $var) {
					@$this->collection[ $k - $start ][$var] = $values[$index];
				}
			}

			return $this->collection;
		}
	}

	class o_import_products extends ModuleFD{

		
		

		public function __construct(){
			$this->name = 'o_import_products';
			$this->tab = 'back_office_features';
			$this->version = '1.0.0';
			$this->author = 'Octavio Martinez';
			$this->need_instance = 0;
			$this->ps_versions_compliancy = [
				'min' => '1.6',
				'max' => _PS_VERSION_
			];
			$this->bootstrap = true;
			parent::__construct();
			
			$this->displayName = 'Import Products';
			$this->description = 'Importa Productos desde archivo CSV';
			$this->confirmUninstall = 'Are you sure you want to Uninstall?';

			if(!Configuration::get('MYMODULE_NAME')) {
				$this->warning = 'No name provided';
			}

		}


		/******************
		**** INSTALL ******
		*******************/

		public function install(){
			//$this->installTab();
			
			if(Shop::isFeatureActive()) {
				Shop::setContext(Shop::CONTEXT_ALL);
			}
			return parent::install() && $this->addHooks($this->hooks);
		}

		public function uninstall(){
			//$this->uninstallTab();
			return parent::uninstall() && $this->addHooks($this->hooks, false);
		}

		/****************
		**** HOOKS ******
		*****************/
		

		/************************
		**** CONFIGURATION ******
		************************/

		public function getContent(){
			$output = null;

			if(Tools::isSubmit('submit')) {
				$productsCSV = Tools::fileAttachment('productCSV');
				$csv = new CSVImport( $productsCSV['content'], ",");
				$products = $csv->getCollection();
				foreach ($products as $product) {
					if($product['precio_de_venta']!=''){
						$newProduct = new Product();
						$newProduct->name = $product['nombre'];
						$newProduct->reference = $product['referencia'];
						$newProduct->ean13 = $product['ean13'];
						$newProduct->base_price = $product['precio_de_coste'];
						$newProduct->price = $product['precio_de_venta'];
						$newProduct->tax_name = 'IVA';
						$newProduct->tax_rate = $product['iva'];
						$newProduct->quantity = $product['cantidad'];
						$newProduct->category = $product['categorias'];
						$newProduct->manufacturer_name = $product['marca'];
						if(!$newProduct->existsRefInDatabase($product['referencia'])){
							$newProduct->add();
						}
					}
				}
				$output .= $this->displayConfirmation('Se agregaron los productos de manera exitosa.');
			}
			return $output.$this->displayForm();
		}

		public function displayForm(){
			$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

			$inputs = array(
				array(
					'type' => 'file',
					'label' => $this->l('Archivo'),
					'multiple' => false,
					'name' => 'productCSV',
					'desc' => $this->l('Por favor cargue un archivo CSV con los productos que seran cargados')
				)
			);

			$fields_form = array(
				'form' => array(
		            'legend' => array(
						'title' => 'Titulo',
						'icon' => 'icon-cogs'
		            ),
		            'input' => $inputs, 
		            'submit' => array(
		                'name' => 'submit',
		                'title' => $this->trans('Save', array(), 'Admin.Actions')
		            ),
		        ),
        	);

        	$helper = new HelperForm();
	        $helper->module = $this;
	        $helper->table = $this->name;
	        $helper->token = Tools::getAdminTokenLite('AdminModules');
	        $helper->currentIndex = $this->getModuleConfigurationPageLink();
	        
	        $helper->default_form_language = $lang->id;
	        
	        $helper->title = $this->displayName;
	        $helper->show_toolbar = false;
	        $helper->toolbar_scroll = false;
	        
	        $helper->submit_action = 'submit';
	        

			$helper->identifier = $this->identifier;


	        $helper->tpl_vars = array(
	            'languages' => $this->context->controller->getLanguages(),
	            'id_language' => $this->context->language->id,    
	        );

	        return $helper->generateForm(array($fields_form));
		}


		/**************
		**** TABS ******
		***************/
		private function installTab(){
			return true;
			/*
			$response = true;

			$subTab = new Tab();
			$subTab->active = 1;
			$subTab->name = array();
			$subTab->class_name = 'OscLinkTab';
			$subTab->icon = 'menu';
			foreach (Language::getLanguages() as $lang) {
				$subTab->name[$lang['id_lang']] = 'Subcategories Cards';
			}

			$subTab->id_parent = (int)Tab::getIdFromClassName('AdminCatalog');
			$subTab->module = $this->name;
			$response &= $subTab->add();

			return $response;*/
		}

		private function uninstallTab(){
			return true;
			/*$response = true;
			$tab_id = (int)Tab::getIdFromClassName('OscLinkTab');
			if(!$tab_id){
				return true;
			}

			$tab = new Tab($tab_id);
			$response &= $tab->delete();
			return $response;*/
		}
	}
		