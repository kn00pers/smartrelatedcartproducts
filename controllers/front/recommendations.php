<?php
/**
 * Smart Related Cart Products - AJAX recommendations controller
 *
 * @author    astrodesign
 * @copyright 2026 astrodesign
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartRelatedCartProductsRecommendationsModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    public function initContent(): void
    {
        parent::initContent();

        header('Content-Type: application/json; charset=utf-8');

        $idLang = (int) $this->context->language->id;

        try {
            $products = $this->module->getRecommendedProducts($idLang);
        } catch (Exception $e) {
            $this->ajaxRender(json_encode(['html' => '']));
            exit;
        }

        if (empty($products)) {
            $this->ajaxRender(json_encode(['html' => '']));
            exit;
        }

        $this->context->smarty->assign([
            'smart_related_products' => $products,
            'smart_related_cart_url' => $this->context->link->getPageLink('cart', true),
            'smart_related_token' => Tools::getToken(false),
        ]);

        $html = $this->module->fetch(
            'module:smartrelatedcartproducts/views/templates/hook/product_list.tpl'
        );

        $this->ajaxRender(json_encode(['html' => $html]));
        exit;
    }
}
