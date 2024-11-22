
const AJMainClass = function () {
    const engine = new liquidjs.Liquid();
    let jsonData = [];
    let categoryList = [];
    this.init = async function () {
        jsonData = await readJSONdata();
        if (jsonData) {
            // register method to display start by rating 
            engine.registerFilter('starRating', (rating) => {
                let fullStars = Math.floor(rating); // Number of full stars
                let halfStar = rating % 1 >= 0.5 ? 1 : 0; // Half star if rating is >= .5
                let emptyStars = 5 - fullStars - halfStar; // Remaining empty stars
                let stars = '';

                // Add full stars
                for (let i = 0; i < fullStars; i++) {
                    stars += '★';
                }
                // Add half star (represented as empty star for now, adjust as needed)
                if (halfStar) {
                    stars += '☆';
                }
                // Add empty stars
                for (let i = 0; i < emptyStars; i++) {
                    stars += '☆';
                }

                return stars;
            });

            categoryList = getCategoryNames(jsonData);
            bindNavLinks(categoryList);
            bindCategoryLists(categoryList);
        }
        else {
            console.log("no data found")
        }
    }

    // Function to convert into camel Case
    function camelCase(str) {
        return str.split(' ')  // Split the string into an array of words
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))  // Capitalize the first letter of each word
            .join(' ');
    }

    function getCategoryNames(data) {
        let categoryJSONData = [];
        let categoryNames = []
        data.forEach(item => {
            if (categoryNames.indexOf(item.category) < 0) {
                categoryJSONData.push({
                    name: camelCase(item.category),
                    category: item.category,
                    count: 1,
                    src: item.src
                });
                categoryNames.push(item.category)
            }
            else {
                let index = categoryNames.indexOf(item.category);
                categoryJSONData[index].count += 1;
            }
        });
        return categoryJSONData;
    }

    async function bindNavLinks(categoryList) {
        let navLinks = [{
            name: "Home",
            category: "home"
        }, ...categoryList];
        const template = document.querySelector("#navLinksTemplate");
        const navLinksElm = document.querySelector("#navLinks");
        await engine.parseAndRender(template.innerHTML, { navLinks: navLinks }).then((html) => {
            navLinksElm.innerHTML = html;
        });
        $("#navLinks a.nav-link").on("click", navClick);
        $("a#siteName").on("click", navClick);
    }
    async function bindCategoryLists(categoryItems) {
        const template = document.querySelector("#productCategoryTemplate");
        const displayProductElm = document.querySelector("#productLists");
        await engine.parseAndRender(template.innerHTML, { listItems: categoryItems }).then((html) => {
            displayProductElm.innerHTML = html;
        });
        $("#productLists button.btn").on("click", categoryButton);

    }

    function bindProductListByCategory(category) {
        let results = jsonData.filter((x) => x.category == category)
        if (results && results.length > 0) {
            const template = document.querySelector("#productInfoTemplate");
            const displayProductElm = document.querySelector("#productLists");
            engine.parseAndRender(template.innerHTML, { listItems: results }).then((html) => {
                displayProductElm.innerHTML = html;
            });
        }

    }

    async function readJSONdata() {
        let responseData = null;
        try {
            const response = await fetch('./assets/productsInfo.json');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            responseData = await response.json();
        }
        catch (error) {
            console.error('There was a problem while fetching data from JSON file. For more details, ', error);
        }
        return responseData;
    }

    function navClick(event) {
        console.log("nav click. event info", event);
        let category = event.target.getAttribute("category");
        // 
        if (category === "home") {
            bindCategoryLists(categoryList);
        }
        else {
            bindProductListByCategory(category);
        }
        $(event.target.parentNode.parentNode).find("a.nav-link").removeClass("active");
        if (event.target.id === "siteName") {
            $("ul#navLinks a.nav-link[category='" + category + "']").addClass("active");
        }
        else {
            event.target.classList.add("active");
        }
    }

    function categoryButton(event) {
        console.log("category Button click. event info", event);
        let category = event.target.getAttribute("category");
        if (category) {
            bindProductListByCategory(category);
            $("ul#navLinks a.nav-link").removeClass("active");
            $("ul#navLinks a.nav-link[category='" + category + "']").addClass("active");
        }
    }
}

$(document).ready(function () {
    console.log("ready!");
    let aj = new AJMainClass();
    aj.init();
});
