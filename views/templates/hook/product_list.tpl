{if $smart_related_products|count > 0}
<section class="smart-related-products block">
    <div class="smart-related-products__header">
        <h2 class="smart-related-products__title">
            Recommended for you
        </h2>
    </div>

    <div class="smart-related-products__list">
        {foreach from=$smart_related_products item=product}
        <article class="smart-related-products__item">
            <a href="{$product.url}" class="smart-related-products__link" title="{$product.name|escape:'html':'UTF-8'}">
                <div class="smart-related-products__img-wrapper">
                    <img
                        src="{$product.image_url}"
                        alt="{$product.name|escape:'html':'UTF-8'}"
                        class="smart-related-products__img"
                        loading="lazy"
                    />
                </div>
                <div class="smart-related-products__info">
                    <h3 class="smart-related-products__name">{$product.name|truncate:50:'...'}</h3>
                    <div class="smart-related-products__price">
                        {if $product.has_discount}
                            <span class="smart-related-products__price--old">{$product.regular_price}</span>
                        {/if}
                        <span class="smart-related-products__price--current {if $product.has_discount}is-discount{/if}">
                            {$product.price}
                        </span>
                    </div>
                </div>
            </a>
            <div class="smart-related-products__add">
                <form action="{$smart_related_cart_url}" method="post">
                    <input type="hidden" name="token" value="{$smart_related_token}">
                    <input type="hidden" name="id_product" value="{$product.id_product}">
                    <input type="hidden" name="id_product_attribute" value="{$product.id_product_attribute}">
                    <input type="hidden" name="qty" value="1">
                    <input type="hidden" name="add" value="1">
                    <input type="hidden" name="action" value="update">
                    <button
                        type="submit"
                        class="btn btn-primary smart-related-products__btn"
                        data-button-action="add-to-cart"
                        title="Add to cart"
                    >
                        <span class="srcp-btn-text">Add to cart</span>
                        <i class="material-icons srcp-btn-icon" aria-hidden="true">shopping_cart</i>
                    </button>
                </form>
            </div>
        </article>
        {/foreach}
    </div>
</section>
{/if}
