# Smart Related Cart Products

[![PrestaShop Version](https://img.shields.io/badge/PrestaShop-8.0.0+-blueviolet.svg)](https://www.prestashop.com/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A PrestaShop module that displays intelligent product recommendations in the shopping cart footer, designed to increase cross-selling and average order value.

## 🚀 Overview

**Smart Related Cart Products** analyzes the current contents of a customer's shopping cart and dynamically displays a curated selection of 4 to 8 products. It prioritizes bestsellers from categories already present in the cart, creating a personalized shopping experience.

## ✨ Key Features

- **Intelligent Recommendations**: Uses a bestseller-first algorithm focused on the categories of products currently in the cart.
- **Dynamic Content**: Recommendations are fetched via AJAX, ensuring they stay relevant as the customer modifies their cart.
- **Smart Fallbacks**: If category-specific data is limited, the module automatically tops up the list with global bestsellers to maintain a full recommendation grid.
- **Seamless Integration**: Includes functional "Add to Cart" buttons for each recommended item, allowing customers to add products without leaving the cart page.
- **Optimized Performance**: Assets (CSS/JS) are only loaded on the Cart and Order pages to maintain store-wide speed.

## 🛠️ Technical Details

- **Minimum PrestaShop Version**: 8.0.0
- **Hooks Used**:
  - `displayShoppingCartFooter`: The primary entry point for the recommendation widget.
  - `actionFrontControllerSetMedia`: For targeted loading of styles and scripts.
- **Custom Controller**: Includes a dedicated front controller (`recommendations`) to handle AJAX requests efficiently.

## 📦 Installation

1. Download or clone this repository into your PrestaShop `/modules/` directory.
2. Rename the folder to `smartrelatedcartproducts`.
3. Go to the PrestaShop Back Office > **Module Manager**.
4. Search for "Smart Related Cart Products" and click **Install**.

## 🎨 Customization

The module's appearance can be customized by editing the files in:
- `/views/css/smartrelatedcartproducts.css`
- `/views/templates/hook/product_list.tpl` (Grid layout)
- `/views/templates/hook/displayShoppingCartFooter.tpl` (Container wrapper)

---
Developed by **astrodesign**

> **Disclaimer:** This module is provided "as is" for the community. PrestaShop modules are often expensive; this project aims to provide a free, high-quality alternative. **Not for resale.**
