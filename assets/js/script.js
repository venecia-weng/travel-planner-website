document.addEventListener('DOMContentLoaded', () => {
    const destinationSelect = document.getElementById('destinationSelect');
    const budgetInput = document.getElementById('budgetInput');
    const currencySelect = document.getElementById('currencySelect');
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    budgetInput.value = 1000;
    destinationSelect.value = '';

    updateBudgetValue(budgetInput.value);
    filterDeals();

    destinationSelect.addEventListener('change', filterDeals);
    currencySelect.addEventListener('change', () => {
        updateBudgetValue(budgetInput.value);
        filterDeals();
    });
    budgetInput.addEventListener('input', filterDeals);
    startDateInput.addEventListener('change', filterDeals);
    endDateInput.addEventListener('change', validateDates);
});

function getCurrencySymbol(currencyCode) {
    const symbols = {
        'sgd': '$',
        'usd': '$',
        'eur': '€',
        'gbp': '£',
        'jpy': '¥'
    };
    return symbols[currencyCode] || '$';
}

function updateBudgetValue(value) {
    const currencyCode = document.getElementById('currencySelect').value;
    const currencySymbol = getCurrencySymbol(currencyCode);
    document.getElementById('currency-symbol').textContent = currencySymbol;
}

function filterDeals() {
    const selectedDestination = document.getElementById('destinationSelect').value;
    const budgetValue = parseFloat(document.getElementById('budgetInput').value);
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const dealTypes = ['flight', 'accommodation'];

    const selectedDeals = dealTypes.filter(type => {
        const checkbox = document.getElementById(`${type}Deals`);
        return checkbox && checkbox.checked;
    });

    ['flights', 'accommodations'].forEach(id => {
        document.getElementById(id).innerHTML = '';
    });

    const filteredDeals = deals.filter(deal => {
        const matchesDestination = !selectedDestination || deal.destination === selectedDestination;
        const price = parseFloat(deal.price.replace(/[^\d.-]/g, ''));
        const matchesBudget = price <= budgetValue;
        const matchesDealType = selectedDeals.includes(deal.type);

        let matchesDate = true;

        if (deal.type === 'flight' && startDate) {
            const dealDate = deal.dateTime.split(" ")[0];
            const [year, month, day] = dealDate.split("-");
            const dealDateObj = new Date(Date.UTC(year, month - 1, day));

            const startDateObj = new Date(startDate);

            matchesDate = dealDateObj.toDateString() === startDateObj.toDateString(); 
        }

        return matchesDestination && matchesBudget && matchesDealType && matchesDate;
    });

    filteredDeals.forEach(deal => {
        const dealElement = document.createElement('div');
        dealElement.classList.add('deal');
        dealElement.innerHTML = `
            <div class="deal-image-container">
                <img src="${deal.image}" alt="Image of ${deal.description}">
            </div>
            <div class="deal-info">
                <p>${deal.description} - <span class="price">${deal.price}</span></p>
                <div id="${deal.type}-${deal.destination}-details" class="deal-details" style="display: none;">
                    <p><strong>${deal.type === 'flight' ? 'Date & Time:' : 'Additional Info:'}</strong> 
                    ${deal.type === 'flight' ? deal.dateTime : deal.additionalInfo}</p>
                </div>
                <button onclick="toggleDetails('${deal.type}', '${deal.destination}')">View Details</button>
                <button class="add-to-cart-btn" onclick="addToCart('${deal.type}', '${deal.description}', '${deal.price}')">Add to Cart</button>
            </div>
        `;
        document.getElementById(deal.type + 's').appendChild(dealElement);
    });
}

const deals = [
    { id: 'paris', type: 'flight', destination: 'paris', price: '$1000', description: 'Flight to Paris', dateTime: '2025-04-10 08:00 AM', image: 'images/france.png' },
    { id: 'tokyo', type: 'flight', destination: 'tokyo', price: '$1200', description: 'Flight to Tokyo', dateTime: '2025-04-12 10:30 AM', image: 'images/sq.png' },
    { id: 'london', type: 'flight', destination: 'london', price: '$900', description: 'Flight to London', dateTime: '2025-04-15 02:45 PM', image: 'images/brit.png' },
    { id: 'new york', type: 'flight', destination: 'new york', price: '$1800', description: 'Flight to New York', dateTime: '2025-04-18 07:15 PM', image: 'images/etihad.png' },
    
    { id: 'paris', type: 'accommodation', destination: 'paris', price: '$500', description: 'Hotel de Luxe', additionalInfo: 'Central location near Eiffel Tower', image: 'images/luxe.jpg' },
    { id: 'tokyo', type: 'accommodation', destination: 'tokyo', price: '$800', description: 'Tokyo Inn', additionalInfo: 'Free Wi-Fi and breakfast', image: 'images/inn.jpg' },
    { id: 'london', type: 'accommodation', destination: 'london', price: '$650', description: 'Leonardo Royal Hotel', additionalInfo: 'Great view of Big Ben', image: 'images/royal.jpeg' },
    { id: 'new york', type: 'accommodation', destination: 'new york', price: '$750', description: 'Hyatt Centric', additionalInfo: 'Near Times Square', image: 'images/hyatt.jpg' },
];

function toggleDetails(type, destination) {
    const detailsSection = document.getElementById(`${type}-${destination}-details`);
    const button = detailsSection.closest('.deal-info').querySelector('button');

    if (detailsSection.classList.contains('show')) {
        detailsSection.classList.remove('show');
        detailsSection.style.maxHeight = '0'; 
        detailsSection.style.opacity = '0';
        detailsSection.style.display = 'none'; 
        button.textContent = 'View Details';
    } else {
        detailsSection.classList.add('show');
        detailsSection.style.maxHeight = '200px';
        detailsSection.style.opacity = '1';
        detailsSection.style.display = 'block'; 
        button.textContent = 'Hide Details';
    }
}

function displayFlightDeals() { displayDeals('flight'); }
function displayAccommodationDeals() { displayDeals('accommodation'); }

function displayDeals(type) {
    const container = document.getElementById(type + 's');
    container.innerHTML = '';

    const deals = getDealsByType(type);

    deals.forEach(deal => {
        const dealElement = document.createElement('div');
        dealElement.classList.add(`${type}-deal`);
        
        dealElement.innerHTML = `
            <div class="image-container">
                <img src="${deal.image}" alt="Image of ${deal.name}">
            </div>
            <div class="${type}-details">
                <h3>${deal.name}</h3>
                <p><strong>Price:</strong> ${deal.price}</p>
                <p><strong>Location:</strong> ${deal.location}</p>
                <p><strong>Description:</strong> ${deal.description}</p>

                <div id="${type}-${deal.id}-details" class="deal-details" style="display: none;">
                    <p><strong>Additional Info:</strong> ${deal.additionalInfo}</p>
                </div>

                <button class="view-details-btn" onclick="toggleDetails('${type}', '${deal.id}')">View Details</button>
                
                <button class="add-to-cart-btn" onclick="addToCart('${type}', '${deal.name}', '${deal.price}')">Add to Cart</button>
            </div>
        `;
        
        container.appendChild(dealElement);
    });
}

function validateDates() {
    const startDate = new Date(document.getElementById('startDate').value);
    const endDate = new Date(document.getElementById('endDate').value);

    if (endDate <= startDate) {
        alert("The end date must be later than your start date.");
        const correctedEndDate = new Date(startDate);
        correctedEndDate.setDate(correctedEndDate.getDate() + 1);
        document.getElementById('endDate').value = correctedEndDate.toISOString().split('T')[0];
    }
}

document.addEventListener("DOMContentLoaded", function () {
    let today = new Date().toISOString().split("T")[0];

    document.getElementById("startDate").setAttribute("min", today);
    document.getElementById("endDate").setAttribute("min", today);
});

let cart = [];

function initializeCart() {
    const savedCart = JSON.parse(localStorage.getItem("cart")) || [];
    cart = savedCart;
    updateCart();
}

function addToCart(type, name, price) {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    cart.push({ type, name, price });
    localStorage.setItem("cart", JSON.stringify(cart));

    updateCartCount();
}

function updateCart() {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    localStorage.setItem("cart", JSON.stringify(cart));

    document.getElementById("cart-count").textContent = cart.length;
}

function updateCartCount() {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    const cartCountElement = document.getElementById("cart-count");
    if (cartCountElement) {
        cartCountElement.textContent = cart.length;
    }
}

document.getElementById('cart-icon').addEventListener('click', function() {
    window.location.href = 'cart.html';
});

function displayCart() {
    const cartItemsContainer = document.getElementById("cart-items");
    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    if (cart.length === 0) {
        cartItemsContainer.innerHTML = "<p>Your cart is empty.</p>";
        return;
    }

    cartItemsContainer.innerHTML = cart.map((item, index) => `
        <div class="cart-item">
            <p><strong>${item.type}</strong>: ${item.name} - ${item.price}</p>
            <button class="remove-item" data-index="${index}">-</button>
        </div>
    `).join("");

    document.querySelectorAll(".remove-item").forEach(button => {
        button.addEventListener("click", function() {
            removeItem(this.getAttribute("data-index"));
        });
    });
}

function removeItem(index) {
    let cart = JSON.parse(localStorage.getItem("cart")) || [];
    cart.splice(index, 1);
    localStorage.setItem("cart", JSON.stringify(cart));
    displayCart();
    updateCart();
}

document.getElementById("clear-cart").addEventListener("click", function() {
    localStorage.removeItem("cart"); 
    displayCart(); 
    updateCart(); 
});

document.addEventListener("DOMContentLoaded", function() {
    displayCart();  
    updateCartCount();  

    const clearCartButton = document.getElementById("clear-cart");
    if (clearCartButton) {
        clearCartButton.addEventListener("click", function() {
            localStorage.removeItem("cart");
            displayCart();
            updateCartCount();
        });
    }
});