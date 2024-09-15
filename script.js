const gallery = document.getElementById('gallery');
const loadMoreButton = document.getElementById('loadMoreButton');

let photos = [];

let currentIndex = 0;
const itemsPerPage = 5;

 // Function to fetch photos from JSON file
async function fetchPhotos() {
    try {
        const response = await fetch('photos.json');
        photos = await response.json();
        displayPhotos();
    } catch (error) {
        console.error('Error fetching photos:', error);
    }
}

// Function to display photos
function displayPhotos() {
    const endIndex = currentIndex + itemsPerPage;
    const photosToDisplay = photos.slice(currentIndex, endIndex);

    photosToDisplay.forEach(photo => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';

        productCard.innerHTML = `
            <div class="product-image">
                <img src="${photo.src}" alt="${photo.name}">
                <button class="wishlist-button">${photo.currentPrice}</button>
            </div>
            <div class="product-details">
                <div class="product-rating">
                    ${'&#9733;'.repeat(Math.floor(photo.rating))}${'&#9734;'.repeat(5 - Math.floor(photo.rating))}
                </div>
                <h3 class="product-name">${photo.name}</h3>
                <div class="product-pricing">
                    <span class="current-price">${photo.currentPrice}</span>
                    <span class="original-price">${photo.originalPrice}</span>
                </div>
                <p class="product-description">${photo.description}</p>
            </div>
        `;

        gallery.appendChild(productCard);
    });

    currentIndex += itemsPerPage;

    if (currentIndex >= photos.length) {
        loadMoreButton.style.display = 'none';
    }
}

// Load more photos when button is clicked
loadMoreButton.addEventListener('click', displayPhotos);

 // Fetch photos on page load
fetchPhotos();
