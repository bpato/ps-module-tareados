<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminImportCsvController extends ModuleAdminController
{
    public $separator;

    public function __construct( $separator = ',')
    {
        $this->separator = $separator;
    }

    public function import($file)
    {
        $imported = 0;
        if ( is_array($file_content = $this->readCsvFile($file)) ) {
            foreach (array_splice($file_content, 1) as $product) {
                $product['id'] = null;
                if ($id_product = self::getProductByReference($product['referencia'])) {
                    $product['id'] = $id_product;
                }

                if (! $this->importProduct($product)) {
                    return false;
                }
                $imported++;
            }
        }

        return $imported;        
    }

    private function readCsvFile($file)
    {
        $handler = false;
        if (is_file($file) && is_readable($file)) {
            if (!mb_check_encoding(file_get_contents($file), 'UTF-8')) {
                $this->convert = true;
            }
            $handler = fopen($file, 'r');
        }

        if (!$handler) {
            return null; // error case
        }

        while( ($result = fgets($handler)) !== FALSE)
        {
            if (isset($file_content[0]) && is_array($file_content[0])) {
                $row = array_combine(
                    $file_content[0], 
                    array_map('trim', str_getcsv( $result, $this->separator))
                );
                $file_content[] = $row;
            } else {
                $file_content[0] = array_map('trim', str_getcsv( $result, $this->separator));
                $file_content[0] = array_map('strtolower', $file_content[0]);
                $file_content[0] = array_map(function($value) { 
                    return str_replace(" ", "_", $value);
                }, $file_content[0]);
            }
        }

        return $file_content;

    }

    private function importProduct($p_data)
    {
        $id_lang = Context::getContext()->language->id;
        $tax_rules_ids = $this->getIdTaxRulesGroups($id_lang);

        $product = new Product($p_data['id']);
        if(!isset($product->id)) {
            $product->add();
        }
        $product->name = $p_data['nombre'];
        $product->reference = $p_data['referencia'];
        $product->ean13 = $p_data['ean13'];
        $product->wholesale_price = $p_data['precio_de_coste'];
        $product->price = $p_data['precio_de_venta'];
        $product->id_tax_rules_group = (int) isset($tax_rules_ids[$p_data['iva']])?$tax_rules_ids[$p_data['iva']]:0;
        $product->depends_on_stock = 0;
        $product->quantity = (int) $p_data['cantidad'];
        $product->id_manufacturer = (int) $this->importManufacturer( $p_data['marca']);
        $product->id_category = $this->importCategory($p_data['categorias'], $id_lang);
        $product->id_category_default = $product->id_category[count($product->id_category) - 1];
        $product->addToCategories($product->id_category);
        $product->update();

        StockAvailable::setQuantity((int) $product->id, 0, (int) $product->quantity);

        return Product::existsInDatabase((int) $product->id, 'product');
    }

    private function getIdTaxRulesGroups($id_lang)
    {
        $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        $tax_rules_group = TaxRulesGroup::getAssociatedTaxRatesByIdCountry($id_country);
        $tax_rules_ids = [];
        foreach ($tax_rules_group as $tax_id => $tax_value) {
            if(!isset($tax_rules_ids[(int)$tax_value])) {
                $tax_rules_ids[(int)$tax_value] = $tax_id;
            }
        }

        return $tax_rules_ids;
    }

    private function importManufacturer($name)
    {
        $id_manufacturer = null;
        if ($id = Manufacturer::getIdByName($name)) {
            $id_manufacturer = $id;
        }
        $manufacturer = new Manufacturer($id_manufacturer);
        $manufacturer->name = $name;
        $manufacturer->active = true;

        if(isset($manufacturer->id)) {
            $manufacturer->update();
        } else {
            $manufacturer->add();
        }
        
        return $manufacturer->id;
    }

    private function importCategory($categories, $id_lang)
    {
        $cat_names = explode(";", $categories);
        //$root = Category::getRootCategory($id_lang);

        $id_parent = Configuration::get('PS_HOME_CATEGORY');
        $product_categories = [(int) $id_parent];

        foreach ($cat_names as $cat_name) {
            $id = null;
            $cat = Category::searchByNameAndParentCategoryId($id_lang, $cat_name, $id_parent);

            if ($cat) {
                $id = (int) $cat['id_category'];
            }

            $category = new Category($id);
            $category->name = [$id_lang => $cat_name];
            $category->active = 1;
            $category->id_parent = (int)$id_parent;
            $category->link_rewrite = [$id_lang => Tools::link_rewrite($category->name[$id_lang])];
            
            if(isset($category->id)) {
                $category->update();
            } else {
                $category->add();
            }

            $product_categories[] = (int) $category->id;
            $id_parent = $category->id;
        }
        
        return $product_categories;
    }

    public static function getProductByReference($reference)
    {
        return (int) Db::getInstance()->getValue('
                        SELECT p.`id_product`
                        FROM `' . _DB_PREFIX_ . 'product` p
                        ' . Shop::addSqlAssociation('product', 'p') . '
                         WHERE p.`reference` = "' . pSQL($reference) . '"
                    ', false);
    }
}