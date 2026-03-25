(function () {
    'use strict';

    var container = document.getElementById('smart-related-products-container');
    if (!container || typeof smartRelatedCartUrl === 'undefined') {
        return;
    }

    function fetchRecommendations() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', smartRelatedCartUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.html !== undefined) {
                        container.innerHTML = data.html;
                    }
                } catch (e) {
                }
            }
        };
        xhr.send();
    }

    document.addEventListener('updateCart', fetchRecommendations);

    if (typeof $ !== 'undefined') {
        $('body').on('updateCart', fetchRecommendations);
    }

    if (typeof prestashop !== 'undefined' && prestashop.on) {
        prestashop.on('updateCart', fetchRecommendations);
    }
})();
