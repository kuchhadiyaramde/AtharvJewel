
const AJMainClass = function () {
    const engine = new liquidjs.Liquid();

    this.init = async function () {
        engine.registerFilter('starRating', (rating) => {
            let fullStars  = Math.floor(rating);
            let halfStar   = rating % 1 >= 0.5 ? 1 : 0;
            let emptyStars = 5 - fullStars - halfStar;
            let stars = '';
            for (let i = 0; i < fullStars; i++)  stars += '★';
            if (halfStar)                          stars += '☆';
            for (let i = 0; i < emptyStars; i++)  stars += '☆';
            return stars;
        });

        const categories = await fetchFromAPI('./api/categories.php');
        if (categories && categories.length > 0) {
            await bindNavLinks(categories);
            await bindCategoryLists(categories);
        } else {
            document.querySelector('#productLists').innerHTML =
                '<p class="col-12 text-center text-muted py-5">No categories found. Please check the database setup.</p>';
        }
    };

    async function fetchFromAPI(url) {
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        } catch (error) {
            console.error('API fetch failed [' + url + ']:', error);
            return null;
        }
    }

    async function bindNavLinks(categories) {
        const navLinks = [{ name: 'Home', category: 'home' }, ...categories];
        const template = document.querySelector('#navLinksTemplate');
        const navLinksElm = document.querySelector('#navLinks');
        await engine.parseAndRender(template.innerHTML, { navLinks }).then((html) => {
            navLinksElm.innerHTML = html;
        });
        $('#navLinks a.nav-link').on('click', navClick);
        $('a#siteName').on('click', navClick);
    }

    async function bindCategoryLists(categories) {
        const template = document.querySelector('#productCategoryTemplate');
        const displayElm = document.querySelector('#productLists');
        await engine.parseAndRender(template.innerHTML, { listItems: categories }).then((html) => {
            displayElm.innerHTML = html;
        });
        $('#productLists button.btn').on('click', categoryButton);
    }

    async function bindProductListByCategory(category) {
        const displayElm = document.querySelector('#productLists');
        displayElm.innerHTML = '<div class="col-12 text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>';

        const products = await fetchFromAPI('./api/products.php?category=' + encodeURIComponent(category));
        if (products && products.length > 0) {
            const template = document.querySelector('#productInfoTemplate');
            engine.parseAndRender(template.innerHTML, { listItems: products }).then((html) => {
                displayElm.innerHTML = html;
            });
        } else {
            displayElm.innerHTML = '<div class="col-12 text-center text-muted py-5">No products found in this category.</div>';
        }
    }

    function navClick(event) {
        event.preventDefault();
        const category = event.target.getAttribute('category');
        if (category === 'home') {
            fetchFromAPI('./api/categories.php').then((cats) => { if (cats) bindCategoryLists(cats); });
        } else {
            bindProductListByCategory(category);
        }
        $('ul#navLinks a.nav-link').removeClass('active');
        if (event.target.id === 'siteName') {
            $('ul#navLinks a.nav-link[category="home"]').addClass('active');
        } else {
            event.target.classList.add('active');
        }
    }

    function categoryButton(event) {
        const category = event.target.getAttribute('category');
        if (category) {
            bindProductListByCategory(category);
            $('ul#navLinks a.nav-link').removeClass('active');
            $('ul#navLinks a.nav-link[category="' + category + '"]').addClass('active');
        }
    }
};

$(document).ready(function () {
    const aj = new AJMainClass();
    aj.init();
});
