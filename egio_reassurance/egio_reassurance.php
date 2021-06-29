<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

use PrestaShop\Module\Egio\Model\EgioReassurance;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Egio_reassurance extends Module implements WidgetInterface
{
    protected $config_form = false;
    public $can_add = false;
    public $show_back_btn = false;
    public $show_add_btn = true;
    public $is_updated = false;
    public $upload_folder;
    public $img_path;

    public function __construct()
    {
        $this->name = 'egio_reassurance';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Ayoub M.';
        $this->need_instance = 0;

        $this->upload_folder = _PS_MODULE_DIR_ . $this->name . '/uploads/';
        $this->img_path = __PS_BASE_URI__ . 'modules/' . $this->name . '/uploads/';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Egio Block Reassurance');
        $this->description = $this->l('Module de réassurance. Prestashop 1.7. Test Développeur');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

    }
    /**
     * 
     */
    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');

        Configuration::updateValue('EGIO_REASSURANCE_MAX_ELEMENTS', '10');
        Configuration::updateValue('EGIO_REASSURANCE_ICON_WIDTH', '40');
        Configuration::updateValue('EGIO_REASSURANCE_ICON_HEIGHT', '40');

        return parent::install() &&
            $this->registerHook('displayContentWrapperBottom') &&
            $this->registerHook('displayAfterProductThumbs') &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        // Commented to keep datas on DB
        include(dirname(__FILE__).'/sql/uninstall.php');

        Configuration::deleteByName('EGIO_REASSURANCE_MAX_ELEMENTS');
        Configuration::deleteByName('EGIO_REASSURANCE_ICON_WIDTH');
        Configuration::deleteByName('EGIO_REASSURANCE_ICON_HEIGHT');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';
        $errors = [];
        $this->can_add = (bool)((int)Configuration::get('EGIO_REASSURANCE_MAX_ELEMENTS', 10) > EgioReassurance::getCounts($this->context->shop->id));
        
        /**
         * Add/Update form submit.
         */
        if(((bool)Tools::isSubmit('updateReassuranceModule')) == true && Tools::getValue('updating')){
            $errors = $this->postProcessReassurance();
        }
        /**
         * Config form submit.
         */
        if (((bool)Tools::isSubmit('submitEgio_reassuranceModule')) == true) {
            $errors = $this->postProcess();
        }
        
        /**
         * changestatus.
         */
        if (Tools::getValue('statusegio_reassurancereassurance') || Tools::isSubmit('statusegio_reassurancereassurance') ) {
            $this->changeReassuranceStatus();
        }

        /**
         * Delete.
         */
        if (Tools::getValue('deleteegio_reassurancereassurance') || Tools::isSubmit('deleteegio_reassurancereassurance') ) {
            $this->deleteReassurance();
        }

        /**
         * Render forms.
         */
        if (Tools::getValue('updateReassuranceModule') || Tools::isSubmit('updateegio_reassurancereassurance') ) {
            $get_id = (int) Tools::getValue('id_egioreassurance');
            if($get_id>0 || $this->can_add)
                $output .= $this->renderReassuranceForm($get_id);
            $this->show_back_btn = true;
            if($get_id<1)
                $this->show_add_btn = false;
        }else{
            $output .= $this->renderForm();
        }
        //$t = new EgioReassurance();

        $this->context->smarty->assign('show_back_btn', $this->show_back_btn);
        $this->context->smarty->assign('show_add_btn', $this->show_add_btn);
        $this->context->smarty->assign('can_add', $this->can_add);
        $this->context->smarty->assign('is_updated', $this->is_updated);
        $this->context->smarty->assign('images_uri', $this->img_path);
        $this->context->smarty->assign('add_link', $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name, 'module_name' => $this->name, 'updateReassuranceModule' => '1']));
        $this->context->smarty->assign('back_link', $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name, 'module_name' => $this->name]));
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl') . $output;

        $this->context->smarty->assign('errors', $errors);
        if(!$this->show_back_btn)
            $output = $this->renderReassuranceList().$output;
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl').$output;
        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration page of module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEgio_reassuranceModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function changeReassuranceStatus()
    {
        $now = new DateTime();
        $id_egioreassurance = (int) Tools::getValue('id_egioreassurance');
        if($id_egioreassurance > 0){
            $reassurance = new EgioReassurance($id_egioreassurance);
            $reassurance->status = ((int)$reassurance->status == 1)?0:1;
            $reassurance->date_upd = date('Y-m-d H:i:s');
            $reassurance->id_shop = $this->context->shop->id;
            $reassurance->update();
            $this->is_updated = true;
        }
    }

    protected function deleteReassurance()
    {
        $id_egioreassurance = (int) Tools::getValue('id_egioreassurance');
        $result = false;
        $reassurance = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'egio_reassurance WHERE `id_egioreassurance` = ' . (int) $id_egioreassurance);
        if (!empty($reassurance)) {
            $result = true;
            // Remove Custom icon
            if (!empty($reassurance['icon'])) {
                $filePath = $this->upload_folder.$reassurance['icon'];
                if (file_exists($filePath)) {
                    $result = unlink($filePath);
                }
            }
            // Remove Block Translations
            if ($result) {
                $result = Db::getInstance()->delete('egio_reassurance_lang', 'id_egioreassurance = ' . (int) $id_egioreassurance);
            }
            // Remove Block
            if ($result) {
                $result = Db::getInstance()->delete('egio_reassurance', 'id_egioreassurance = ' . (int) $id_egioreassurance);
                $this->is_deleted = true;
            }
        }
        return $result;
    }

    protected function renderReassuranceForm(int $id = 0)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'updateReassuranceModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&id_egioreassurance='.$id;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getReassuranceFormValues((int) $id), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        $is_add = ($id == 0);

        return $helper->generateForm(array($this->getReassuranceConfigForm($is_add)));
        
    }

    /**
     * Create the structure of your form.
     */
    protected function getReassuranceConfigForm($is_add = true)
    {
        $title = $this->l(($is_add)?'Add':'Update');
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $title,
                    'icon' => 'icon-star',
                ),
                'input' => array(
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'title',
                        'label' => $this->l('Title'),
                        'lang' => true,
                        'required' => true
                    ),
                    array(
                        'col' => 7,
                        'type' => 'textarea',
                        'desc' => $this->l('description'),
                        'name' => 'description',
                        'label' => $this->l('Description'),
                        'lang' => true,
                        'cols' => 60,
                        'rows' => 10,
                        'class' => 'rte',
                        'autoload_rte' => true,
                        'required' => true
                    ),
                    array(
                        'col' => 6,
                        'type' => 'file',
                        'name' => 'icon',
                        'label' => $this->l('Icon'),
                        'display_image' => true,
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'icon_alt',
                        'label' => $this->l('Icon Alt text'),
                        'lang' => true,
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'link',
                        'label' => $this->l('Link'),
                        'lang' => true,
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'link_title',
                        'label' => $this->l('Link Title'),
                        'lang' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Open in new window'),
                        'name' => 'blank',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'position',
                        'label' => $this->l('Position'),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'status',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'updating',
                        'default' => 1,
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'desc' => $this->l('Max allowed number of entries to insert.'),
                        'name' => 'EGIO_REASSURANCE_MAX_ELEMENTS',
                        'label' => $this->l('Max allowed elements'),
                    ),
                    array(
                        'col' => 1,
                        'type' => 'text',
                        'name' => 'EGIO_REASSURANCE_ICON_WIDTH',
                        'label' => $this->l('Icon height'),
                        'suffix' => 'px',
                    ),
                    array(
                        'col' => 1,
                        'type' => 'text',
                        'name' => 'EGIO_REASSURANCE_ICON_HEIGHT',
                        'label' => $this->l('Icon width'),
                        'suffix' => 'px',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getReassuranceFormValues(int $id)
    {
        $reassurance = EgioReassurance::getBlockById($id, Context::getContext()->language->id, Context::getContext()->shop->id);
        if(isset($reassurance[0]))
        {
            //dump($reassurance[0]); die;
            $return = $reassurance[0];
        }else{
            $definition = EgioReassurance::getDefinition("PrestaShop\Module\Egio\Model\EgioReassurance");
            $return = [];
            foreach ($definition['fields'] as $key => $value) {
                $return[$key] = '';
            }
        }
        
        $languages = Language::getLanguages(false);
        $fields = array();
        $form_fields = $this->getReassuranceConfigForm($id);
        foreach ($languages as $lang)
        {
            foreach ($form_fields['form']['input'] as $key => $input) {
                if(isset($input['lang']) && $input['lang']){
                    if(!is_array($return[$input['name']]))
                        $return[$input['name']] = array();
                    $r = false;
                    if(isset($reassurance[0]))
                    {
                        $r = EgioReassurance::getBlockById($id, $lang['id_lang'], Context::getContext()->shop->id);
                    }
                    
                    $return[$input['name']][$lang['id_lang']] = (isset($r[0]))?$r[0][$input['name']]:'';
                    if(Tools::getValue($input['name'].'_'.$lang['id_lang']))
                        $return[$input['name']][$lang['id_lang']] = Tools::getValue($input['name'].'_'.$lang['id_lang']);
                }else{
                    if(Tools::getValue($input['name']))
                        $return[$input['name']] = Tools::getValue($input['name']);
                }
            }

        }
        $return['updating'] = 1;
        return $return;
    }
    protected function getConfigFormValues()
    {
        return array(
            'EGIO_REASSURANCE_MAX_ELEMENTS' => Configuration::get('EGIO_REASSURANCE_MAX_ELEMENTS', 10),
            'EGIO_REASSURANCE_ICON_WIDTH' => Configuration::get('EGIO_REASSURANCE_ICON_WIDTH', 40),
            'EGIO_REASSURANCE_ICON_HEIGHT' => Configuration::get('EGIO_REASSURANCE_ICON_HEIGHT', 40),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcessReassurance()
    {
        $id_egioreassurance = empty(Tools::getValue('id_egioreassurance')) ? null : Tools::getValue('id_egioreassurance');
        $icon = Tools::getValue('icon');
        $errors = [];
        
        $reassurance = new EgioReassurance($id_egioreassurance);
        if ($id_egioreassurance === null) {
            if(!$this->can_add)
                return;
            // Last position
            $reassurance->position = Db::getInstance()->getValue('SELECT MAX(position) AS max FROM ' . _DB_PREFIX_ . 'egio_reassurance');
            $reassurance->position = $reassurance->position ? $reassurance->position + 1 : 1;
            $reassurance->date_add = date('Y-m-d H:i:s');
        }
        $reassurance->date_upd = date('Y-m-d H:i:s');
        $reassurance->status = (bool)Tools::getValue('status');
        $reassurance->blank = (bool)Tools::getValue('blank');
        
        //Multilang fields 
        $languages = Language::getLanguages();
        foreach ($languages as $language) {
            $reassurance->title[$language['id_lang']]          =  Tools::getValue('title'.'_'.$language['id_lang']);
            $reassurance->description[$language['id_lang']]    =  Tools::getValue('description'.'_'.$language['id_lang']);
            $reassurance->icon_alt[$language['id_lang']]       =  Tools::getValue('icon_alt'.'_'.$language['id_lang']);
            $reassurance->link[$language['id_lang']]           =  Tools::getValue('link'.'_'.$language['id_lang']);
            $reassurance->link_title[$language['id_lang']]     =  Tools::getValue('link_title'.'_'.$language['id_lang']);
        }
        
        //Icon upload
        if (isset($_FILES) && !empty($_FILES['icon']['name'])) {
            $customImage = $_FILES['icon'];
            $fileTmpName = $customImage['tmp_name'];
            $filename = $customImage['name'];
            // validateUpload return false if no error (false -> OK)
            $authExtensions = ['jpg', 'jpeg', 'jpe', 'png', 'svg'];
            $authMimeType = ['image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/svg'];
            if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
                // PrestaShop 1.7.7.0+
                $validUpload = ImageManager::validateUpload(
                    $customImage,
                    0,
                    $authExtensions,
                    $authMimeType
                );
            } else {
                // PrestaShop < 1.7.7
                $validUpload = false;
                $mimeType = $this->getMimeType($customImage['tmp_name']);
                if ($mimeType && (
                    !in_array($mimeType, $authMimeType)
                    || !ImageManager::isCorrectImageFileExt($customImage['name'], $authExtensions)
                    || preg_match('/\%00/', $customImage['name'])
                )) {
                    $validUpload = Context::getContext()->getTranslator()->trans('Image format not recognized, allowed formats are: .svg, .jpg, .png', [], 'Admin.Notifications.Error');
                }
                if ($customImage['error']) {
                    $validUpload = Context::getContext()->getTranslator()->trans('Error while uploading image; please change your server\'s settings. (Error code: %s)', [$customImage['error']], 'Admin.Notifications.Error');
                }
            }
            if (is_bool($validUpload) && $validUpload === false) {
                ImageManager::resize($fileTmpName, $this->upload_folder . $filename, Configuration::get('EGIO_REASSURANCE_ICON_WIDTH', 40), Configuration::get('EGIO_REASSURANCE_ICON_HEIGHT', 40));
                $reassurance->icon = $filename;
            } else {
                $errors[] = $validUpload;
            }

        }
        if (empty($errors)) {
            $reassurance->id_shop = $this->context->shop->id;
            if ($id_egioreassurance > 0) {
                $reassurance->update();
            } else {
                if($this->can_add ){
                    $reassurance->add();
                    Tools::redirect($this->context->link->getAdminLink('AdminModules', true, [], ['configure' => $this->name, 'module_name' => $this->name, 'id_egioreassurance' => $reassurance->id, 'updateReassuranceModule' => '1']));
                }
            }
            $this->is_updated = true;
            return;
        }
        return $errors;
        //dump($reassurance);die;
    }
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, trim((int)Tools::getValue($key),'-'));
        }
        $this->is_updated = true;
        return;
    }
    
    /**
     * @param string $hookName
     * @param array $configuration
     *
     * @return array
     */
    public function renderWidget($hookName, array $configuration) 
    {
        $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));

        return $this->fetch('module:'.$this->name.'/views/templates/widget/reassurance.tpl');
    }
 
    public function getWidgetVariables($hookName , array $configuration)
    {
        $reassurances = EgioReassurance::getAllBlockByStatus($this->context->language->id, $this->context->shop->id, true);
        
        return [
            'reassurances' => $reassurances,
            'images_uri' => $this->img_path,
        ];
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function renderReassuranceList()
    {
        $reassurances = EgioReassurance::getAllBlockByLang($this->context->language->id, $this->context->shop->id);

        $fields_list = [
            'id_egioreassurance' => [
                'title' => $this->trans('ID', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
            ],
            'icon' => [
                'title' => $this->trans('Icon', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
                'egio_image' => $this->img_path,
                'image_id' => 'icon',
            ],
            'title' => [
                'title' => $this->trans('Title', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
            ],
            'description' => [
                'title' => $this->trans('Description', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
            ],
            'position' => [
                'title' => $this->trans('Position', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
            ],
            'status' => [
                'title' => $this->trans('Status', [], 'Modules.egio_reassurance.Admin'),
                'active' => 'status',
                'type' => 'bool',
            ],
            'date_add' => [
                'title' => $this->trans('Date Add', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
            ],
            'date_upd' => [
                'title' => $this->trans('Date Update', [], 'Modules.egio_reassurance.Admin'),
                'type' => 'text',
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->actions = ['edit', 'delete'];
        $helper->module = $this;
        $helper->identifier = 'id_egioreassurance';
        $helper->title = $this->trans('Reassurances list', [], 'Modules.egio_reassurance.Admin');
        $helper->table = $this->name . 'reassurance';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        return $helper->generateList($reassurances, $fields_list);
    }
}
