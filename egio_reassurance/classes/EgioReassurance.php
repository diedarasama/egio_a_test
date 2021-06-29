<?php
namespace PrestaShop\Module\Egio\Model;

use ObjectModel;
use \Db;

class EgioReassurance extends ObjectModel
{
    const TYPE_LINK_NONE = 0;
    const TYPE_LINK_CMS_PAGE = 1;
    const TYPE_LINK_URL = 2;

    public $id;
    public $id_shop;
    public $icon;
    public $status;
    public $position;
    public $type_link;
    public $id_cms;
    public $blank;
    public $title;
    public $description;
    public $icon_alt;
    public $link;
    public $link_title;
    public $date_add;
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'egio_reassurance',
        'primary' => 'id_egioreassurance',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            'id_shop' => [
                'type' => self::TYPE_INT, 
                'shop' => true, 
                'validate' => 'isunsignedInt', 
                'required' => true
            ],
            'icon' => [
                'type' => self::TYPE_STRING, 
                'shop' => true, 
                'validate' => 'isCleanHtml', 
                'size' => 255
            ],
            'status' => [
                'type' => self::TYPE_BOOL, 
                'shop' => true, 
                'validate' => 'isBool', 
                'required' => true
            ],
            'position' => [
                'type' => self::TYPE_INT, 
                'shop' => true, 
                'validate' => 'isunsignedInt', 
                'required' => false
            ],
            'type_link' => [
                'type' => self::TYPE_INT, 
                'shop' => true, 
                'validate' => 'isunsignedInt', 
                'required' => false
            ],
            'id_cms' => [
                'type' => self::TYPE_INT, 
                'shop' => true, 
                'validate' => 'isunsignedInt', 
                'required' => false
            ],
            'blank' => [
                'type' => self::TYPE_BOOL, 
                'shop' => true, 
                'validate' => 'isBool', 
                'required' => true
            ],
            'title' => [
                'type' => self::TYPE_STRING, 
                'shop' => true, 
                'lang' => true, 
                'validate' => 'isCleanHtml', 
                'size' => 255, 
                'required' => true
            ],
            'description' => [
                'type' => self::TYPE_HTML, 
                'shop' => true, 
                'lang' => true, 
                'validate' => 'isCleanHtml', 
                'size' => 2000, 
                'required' => true
            ],
            'icon_alt' => [
                'type' => self::TYPE_STRING, 
                'shop' => true, 
                'lang' => true, 
                'validate' => 'isCleanHtml', 
                'size' => 100
            ],
            'link' => [
                'type' => self::TYPE_STRING, 
                'shop' => true, 
                'lang' => true, 
                'validate' => 'isUrl', 
                'required' => false, 
                'size' => 250
            ],
            'link_title' => [
                'type' => self::TYPE_STRING, 
                'shop' => true, 
                'lang' => true, 
                'validate' => 'isCleanHtml',
                'required' => false, 
                'size' => 255
            ],
            'date_add' => [
                'type' => self::TYPE_DATE, 
                'shop' => true, 
                'validate' => 'isDate'
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE, 
                'shop' => true, 
                'validate' => 'isDate'
            ],
        ],
    ];

    /**
     * @param int|null $id if specified, loads and existing object from DB (optional)
     * @param int|null $id_lang required if object is multilingual (optional)
     * @param int|null $id_shop ID shop for objects with multishop tables
     * @param PrestaShopBundle\Translation\Translator
     *
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null, $translator = null)
    {

        parent::__construct($id, $id_lang, $id_shop, $translator);
    }
    /**
     * @param int $id_lang
     * @param int $id_shop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getAllBlockByLang($id_lang = 1, $id_shop = 1, $isfront = false)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'egio_reassurance` pr
            LEFT JOIN ' . _DB_PREFIX_ . 'egio_reassurance_lang prl ON (pr.id_egioreassurance = prl.id_egioreassurance)
            WHERE prl.id_lang = "' . (int) $id_lang . '" AND prl.id_shop = "' . (int) $id_shop . '"
            ORDER BY pr.position';

        $result = Db::getInstance()->executeS($sql);
        return $result;
    }
    /**
     * @param int $id_lang
     * @param int $id_shop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getCounts($id_shop = 1)
    {
        $sql = 'SELECT count(id_egioreassurance) FROM `' . _DB_PREFIX_ . 'egio_reassurance` pr
            WHERE pr.id_shop = "' . (int) $id_shop . '"';

        $result = Db::getInstance()->getValue($sql);
        return (int)$result;
    }

    /**
     * @param int $id_shop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getAllBlockByShop($id_shop = 1)
    {
        $result = [];

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'egio_reassurance` pr
            LEFT JOIN ' . _DB_PREFIX_ . 'egio_reassurance_lang prl ON (pr.id_egioreassurance = prl.id_egioreassurance)
            WHERE prl.id_shop = "' . (int) $id_shop . '"
            GROUP BY prl.id_lang, pr.id_egioreassurance
            ORDER BY pr.position';

        $dbResult = Db::getInstance()->executeS($sql);

        foreach ($dbResult as $key => $value) {
            $result[$value['id_lang']][$value['id_egioreassurance']]['title'] = $value['title'];
            $result[$value['id_lang']][$value['id_egioreassurance']]['description'] = $value['description'];
            $result[$value['id_lang']][$value['id_egioreassurance']]['url'] = $value['link'];
        }

        return $result;
    }

    /**
     * @param int $id_lang
     * @param int $id_shop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getAllBlockByStatus($id_lang = 1, $id_shop = 1, $isfront = false)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'egio_reassurance` pr
            LEFT JOIN ' . _DB_PREFIX_ . 'egio_reassurance_lang prl ON (pr.id_egioreassurance = prl.id_egioreassurance)
            WHERE prl.id_lang = "' . (int) $id_lang . '" 
                AND prl.id_shop = "' . (int) $id_shop . '"
                AND pr.status = 1
            ORDER BY pr.position';

        $result = Db::getInstance()->executeS($sql);

        if($isfront)
        {
            foreach ($result as $i => $row) {
                $result[$i]['description'] = \Tools::truncateString($row['description'], 100);
            }
        }

        return $result;
    }

    /**
     * @param int $id_egioreassurance
     * @param int $id_lang
     * @param int $id_shop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     */
    public static function getBlockById($id_egioreassurance, $id_lang = 1, $id_shop = 1)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'egio_reassurance` pr
            LEFT JOIN ' . _DB_PREFIX_ . 'egio_reassurance_lang prl ON (pr.id_egioreassurance = prl.id_egioreassurance)
            WHERE prl.id_lang = "' . (int) $id_lang . '" 
                AND prl.id_shop = "' . (int) $id_shop . '"
                AND pr.id_egioreassurance = ' . (int) $id_egioreassurance . '
            ORDER BY pr.position';

        $result = Db::getInstance()->executeS($sql);

        foreach ($result as &$item) {
            $item['is_svg'] = !empty($item['custom_icon'])
                && (ImageManager::getMimeType(str_replace(__PS_BASE_URI__, _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR, $item['custom_icon'])) == 'image/svg');
        }

        return $result;
    }

}
