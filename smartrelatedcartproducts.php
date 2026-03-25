<?php
/**
 * Smart Related Cart Products
 *
 * @author    astrodesign
 * @copyright 2026 astrodesign
 * @license   https://opensource.org/license/mit MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartRelatedCartProducts extends Module
{
    const MIN_PRODUCTS = 4;
    const MAX_PRODUCTS = 8;

    public function __construct()
    {
        $this->name = 'smartrelatedcartproducts';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'astrodesign';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Smart Related Cart Products');
        $this->description = $this->l('Displays smart product recommendations in the shopping cart footer based on cart contents.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayShoppingCartFooter')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        $phpSelf = isset($this->context->controller->php_self) ? $this->context->controller->php_self : '';
        if (in_array($phpSelf, ['cart', 'order'])) {
            $this->context->controller->registerStylesheet(
                'smartrelatedcartproducts-css',
                'modules/' . $this->name . '/views/css/smartrelatedcartproducts.css',
                ['media' => 'all', 'priority' => 200]
            );
            $this->context->controller->registerJavascript(
                'smartrelatedcartproducts-js',
                'modules/' . $this->name . '/views/js/smartrelatedcartproducts.js',
                ['position' => 'bottom', 'priority' => 200]
            );
            Media::addJsDef([
                'smartRelatedCartUrl' => $this->context->link->getModuleLink(
                    $this->name,
                    'recommendations',
                    [],
                    true
                ),
            ]);
        }
    }

    public function hookDisplayShoppingCartFooter(array $params): string
    {
        $idLang = (int) $this->context->language->id;
        $products = $this->getRecommendedProducts($idLang);

        if (empty($products)) {
            return '<div id="smart-related-products-container" data-loaded="0"></div>';
        }

        $this->context->smarty->assign([
            'smart_related_products' => $products,
            'smart_related_cart_url' => $this->context->link->getPageLink('cart', true),
            'smart_related_token' => Tools::getToken(false),
        ]);

        $inner = $this->display(__FILE__, 'views/templates/hook/product_list.tpl');

        $this->context->smarty->assign(['smart_related_inner' => $inner]);

        return $this->display(__FILE__, 'views/templates/hook/displayShoppingCartFooter.tpl');
    }

    public function getRecommendedProducts(int $idLang): array
    {
        $cart = $this->context->cart;

        if (!Validate::isLoadedObject($cart)) {
            return [];
        }

        $cartProducts = $cart->getProducts();

        if (empty($cartProducts)) {
            return [];
        }

        $cartProductIds = array_map('intval', array_column($cartProducts, 'id_product'));
        $recommendedIds = [];

        if (count($cartProducts) === 1) {
            $product = reset($cartProducts);
            $categoryId = (int) $product['id_category_default'];
            $rows = $this->getBestsellersFromCategoryWithFallback($categoryId, self::MAX_PRODUCTS, $cartProductIds, $idLang);
            $recommendedIds = array_map('intval', array_column($rows, 'id_product'));
        } else {
            $sorted = $cartProducts;
            usort($sorted, function ($a, $b) {
                return (float) $b['price'] > (float) $a['price'] ? 1 : -1;
            });

            $mostExpensive = $sorted[0];
            $mostExpensiveCategoryId = (int) $mostExpensive['id_category_default'];
            $exclude = $cartProductIds;

            $fromExpensive = $this->getBestsellersFromCategoryWithFallback($mostExpensiveCategoryId, 2, $exclude, $idLang);
            foreach ($fromExpensive as $p) {
                $recommendedIds[] = (int) $p['id_product'];
                $exclude[] = (int) $p['id_product'];
            }

            $others = array_slice($sorted, 1);
            shuffle($others);
            $usedCategories = [$mostExpensiveCategoryId];
            $otherCount = 0;

            foreach ($others as $other) {
                if ($otherCount >= 2) {
                    break;
                }
                $catId = (int) $other['id_category_default'];
                if (in_array($catId, $usedCategories)) {
                    continue;
                }
                $usedCategories[] = $catId;
                $otherCount++;

                $fromOther = $this->getBestsellersFromCategoryWithFallback($catId, 1, $exclude, $idLang);
                foreach ($fromOther as $p) {
                    $recommendedIds[] = (int) $p['id_product'];
                    $exclude[] = (int) $p['id_product'];
                }
            }
        }

        if (count($recommendedIds) < self::MIN_PRODUCTS) {
            $needed = self::MIN_PRODUCTS - count($recommendedIds);
            $allExclude = array_merge($cartProductIds, $recommendedIds);
            $topUp = $this->getGlobalBestsellers($needed, $allExclude);
            foreach ($topUp as $p) {
                $recommendedIds[] = (int) $p['id_product'];
            }
        }

        $recommendedIds = array_slice($recommendedIds, 0, self::MAX_PRODUCTS);

        if (empty($recommendedIds)) {
            return [];
        }

        return $this->buildProductsData($recommendedIds, $idLang);
    }

    public function buildProductsData(array $productIds, int $idLang): array
    {
        $products = [];
        $idShop = (int) $this->context->shop->id;
        $idCurrency = (int) $this->context->currency->id;

        foreach ($productIds as $idProduct) {
            $idProduct = (int) $idProduct;
            try {
                $product = new Product($idProduct, false, $idLang, $idShop);
                if (!Validate::isLoadedObject($product) || !$product->active) {
                    continue;
                }

                $cover = Product::getCover($idProduct);
                $linkRewrite = is_array($product->link_rewrite)
                    ? ($product->link_rewrite[$idLang] ?? reset($product->link_rewrite))
                    : $product->link_rewrite;

                $imageUrl = $cover
                    ? $this->context->link->getImageLink($linkRewrite, $cover['id_image'], 'medium_default')
                    : $this->context->link->getImageLink($linkRewrite, $idProduct . '-0', 'medium_default');

                $priceRaw = Product::getPriceStatic($idProduct, true, null, 2);
                $regularPriceRaw = Product::getPriceStatic($idProduct, true, null, 2, false, false);
                $hasDiscount = $priceRaw < $regularPriceRaw;

                $name = is_array($product->name)
                    ? ($product->name[$idLang] ?? reset($product->name))
                    : $product->name;

                $defaultAttribute = (int) Product::getDefaultAttribute($idProduct);

                $products[] = [
                    'id_product' => $idProduct,
                    'id_product_attribute' => $defaultAttribute,
                    'name' => $name,
                    'url' => $this->context->link->getProductLink($product, $linkRewrite, null, null, $idLang, $idShop),
                    'image_url' => $imageUrl,
                    'price' => Tools::displayPrice($priceRaw, $idCurrency),
                    'regular_price' => Tools::displayPrice($regularPriceRaw, $idCurrency),
                    'has_discount' => $hasDiscount,
                ];
            } catch (Exception $e) {
                continue;
            }
        }

        return $products;
    }

    protected function getGlobalBestsellers(int $needed, array $exclude): array
    {
        $excludeStr = '';
        if (!empty($exclude)) {
            $excludeStr = ' AND p.id_product NOT IN (' . implode(',', array_map('intval', $exclude)) . ')';
        }

        $poolSize = max($needed * 5, 20);

        $sql = '
            SELECT p.id_product, COALESCE(ps.quantity, 0) AS qty_sold
            FROM `' . _DB_PREFIX_ . 'product` p
            INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps_shop
                ON ps_shop.id_product = p.id_product
                AND ps_shop.id_shop = ' . (int) $this->context->shop->id . '
                AND ps_shop.active = 1
                AND ps_shop.visibility IN (\'both\', \'catalog\')
            LEFT JOIN `' . _DB_PREFIX_ . 'product_sale` ps ON ps.id_product = p.id_product
            WHERE p.active = 1
            ' . $excludeStr . '
            ORDER BY qty_sold DESC
            LIMIT ' . (int) $poolSize . '
        ';

        $rows = Db::getInstance()->executeS($sql);
        if (empty($rows)) {
            return [];
        }
        shuffle($rows);
        return array_slice($rows, 0, $needed);
    }

    protected function getBestsellersFromCategoryWithFallback(
        int $categoryId,
        int $needed,
        array $exclude,
        int $idLang,
        int $depth = 0
    ): array {
        if ($depth > 10 || $categoryId <= 2) {
            return [];
        }

        $found = $this->getBestsellersFromCategory($categoryId, $needed, $exclude);

        if (count($found) >= $needed) {
            return $found;
        }

        $category = new Category($categoryId);
        if (!Validate::isLoadedObject($category) || (int) $category->id_parent <= 1) {
            return $found;
        }

        $alreadyFoundIds = array_map('intval', array_column($found, 'id_product'));
        $newExclude = array_merge($exclude, $alreadyFoundIds);
        $remaining = $needed - count($found);

        $fromParent = $this->getBestsellersFromCategoryWithFallback(
            (int) $category->id_parent,
            $remaining,
            $newExclude,
            $idLang,
            $depth + 1
        );

        return array_merge($found, $fromParent);
    }

    protected function getBestsellersFromCategory(int $categoryId, int $limit, array $exclude): array
    {
        $excludeStr = '';
        if (!empty($exclude)) {
            $excludeStr = ' AND p.id_product NOT IN (' . implode(',', array_map('intval', $exclude)) . ')';
        }

        $poolSize = max($limit * 5, 20);

        $sql = '
            SELECT p.id_product, COALESCE(ps.quantity, 0) AS qty_sold
            FROM `' . _DB_PREFIX_ . 'product` p
            INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp
                ON cp.id_product = p.id_product AND cp.id_category = ' . (int) $categoryId . '
            INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps_shop
                ON ps_shop.id_product = p.id_product
                AND ps_shop.id_shop = ' . (int) $this->context->shop->id . '
                AND ps_shop.active = 1
                AND ps_shop.visibility IN (\'both\', \'catalog\')
            LEFT JOIN `' . _DB_PREFIX_ . 'product_sale` ps ON ps.id_product = p.id_product
            WHERE p.active = 1
            ' . $excludeStr . '
            ORDER BY qty_sold DESC
            LIMIT ' . (int) $poolSize . '
        ';

        $rows = Db::getInstance()->executeS($sql);
        if (empty($rows)) {
            return [];
        }
        shuffle($rows);
        return array_slice($rows, 0, $limit);
    }
}
