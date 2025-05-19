<?php
$page_title = 'Shopping Cart - RoundTours';
include 'header.php';
require_once 'currency_functions.php';
?>

<div id="cart-wrapper">
    <main id="cart-container" class="container py-5">
        <div class="cart-content">
            <h1 class="mb-4">Your Shopping Cart</h1>
            <div id="cart-items">
                <p class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Loading cart...</p>
            </div>

            <div id="cart-summary" class="cart-summary" style="display: none;">
                <div class="cart-total">
                    <span>Total Amount:</span>
                    <span id="cart-total-amount">$0.00</span>
                </div>
                <div class="cart-buttons">
                    <button id="clear-cart" class="btn btn-danger">Clear Cart</button>
                    <button id="update-cart" class="btn btn-primary">Update Cart</button>
                    <button id="checkout" class="btn btn-success">Proceed to Checkout</button>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    #cart-wrapper {
        padding: 30px 15px;
    }

    .cart-content {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    #cart-items {
        margin: 20px 0;
    }

    .cart-item {
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 20px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .cart-item-header {
        display: flex;
        padding: 15px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
    }

    .cart-item-image {
        width: 100px;
        height: 70px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 15px;
    }

    .cart-item-title {
        flex: 1;
    }

    .cart-item-name {
        font-size: 18px;
        font-weight: 600;
        margin: 0 0 5px 0;
    }

    .cart-item-price {
        font-weight: 600;
        color: #4a4a4a;
    }

    .cart-item-remove {
        align-self: flex-start;
    }

    .cart-item-details {
        padding: 15px;
    }

    .booking-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #495057;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    .guest-control {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }

    .guest-control button {
        background: #f5f5f5;
        border: none;
        width: 40px;
        height: 40px;
        font-size: 18px;
        cursor: pointer;
    }

    .guest-control input {
        flex: 1;
        text-align: center;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        height: 40px;
        font-size: 16px;
    }

    .subtotal-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .subtotal-label {
        font-weight: 500;
    }

    .subtotal-amount {
        font-size: 18px;
        font-weight: 600;
        color: #28a745;
    }

    .cart-summary {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .cart-total {
        display: flex;
        justify-content: space-between;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .cart-buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .empty-cart {
        text-align: center;
        padding: 40px 0;
    }

    .empty-cart i {
        font-size: 60px;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-cart p {
        font-size: 18px;
        color: #888;
        margin-bottom: 20px;
    }

    .empty-cart a {
        display: inline-block;
        background: #007bff;
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
    }

    .form-text {
        font-size: 12px;
        color: #6c757d;
    }

    .item-type-badge {
        display: inline-block;
        padding: 3px 8px;
        font-size: 12px;
        font-weight: 500;
        border-radius: 12px;
        margin-left: 10px;
    }

    .item-type-flight {
        background-color: #cce5ff;
        color: #004085;
    }

    .item-type-room {
        background-color: #d4edda;
        color: #155724;
    }

    .item-type-attraction {
        background-color: #fff3cd;
        color: #856404;
    }

    @media (max-width: 768px) {
        .booking-form {
            grid-template-columns: 1fr;
        }

        .cart-buttons {
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<script>
    // Function to convert currency via AJAX
    function convertCurrency(amount, fromCurrency, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'convert.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                callback(this.responseText);
            } else {
                callback(amount + ' ' + fromCurrency);
            }
        };

        const params = 'amount=' + amount + '&fromCurrency=' + fromCurrency + '&currency=' + getSelectedCurrency();
        xhr.send(params);
    }

    function getSelectedCurrency() {
        return '<?php echo getCurrentCurrency() ?: "SGD"; ?>';
    }

    function formatCurrency(amount, currency) {
        // For client-side formatting without conversion
        return `${parseFloat(amount).toFixed(2)} ${currency || getSelectedCurrency()}`;
    }

    function displayCart() {
        const cartItemsContainer = document.getElementById("cart-items");
        const cartSummary = document.getElementById("cart-summary");
        const cart = getCartFromLocalStorage();

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <a href="destinations.php">Continue Shopping</a>
                </div>
            `;
            cartSummary.style.display = 'none';
            return;
        }

        // Show cart summary
        cartSummary.style.display = 'block';

        // Generate HTML for each cart item
        cartItemsContainer.innerHTML = '';

        cart.forEach((item, index) => {
            // Create item display based on its type
            let itemHtml = '';

            // Common header part
            const itemTypeBadgeClass = `item-type-${item.type || 'attraction'}`;
            const itemTypeBadgeText = item.type ? item.type.charAt(0).toUpperCase() + item.type.slice(1) : 'Attraction';

            const headerHtml = `
                <div class="cart-item-header">
                    <img src="${item.image || 'assets/images/placeholder.jpg'}" alt="${item.name}" class="cart-item-image">
                    <div class="cart-item-title">
                        <h3 class="cart-item-name">
                            ${item.name}
                            <span class="item-type-badge ${itemTypeBadgeClass}">${itemTypeBadgeText}</span>
                        </h3>
                        <div class="cart-item-price">
                            <span id="price-display-${index}">${formatCurrency(item.price, item.currency)}</span> per unit
                            <small class="text-muted">(Original: ${item.currency})</small>
                        </div>
                    </div>
                    <div class="cart-item-remove">
                        <button class="btn btn-danger btn-sm remove-item" data-index="${index}">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;

            // Item-specific details based on type
            let detailsHtml = '';

            // Get current date for min date attribute
            const today = new Date().toISOString().split('T')[0];

            if (item.type === 'flight') {
                // Flight-specific details
                detailsHtml = `
                    <div class="booking-form">
                        <div class="form-group">
                            <label class="form-label">From</label>
                            <div class="form-control bg-light">${item.origin}</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">To</label>
                            <div class="form-control bg-light">${item.destination}</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-date-${index}" class="form-label">Departure Date</label>
                            <input type="date" class="form-control booking-date" id="booking-date-${index}" 
                                value="${item.date}" min="${today}" data-index="${index}">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Departure Time</label>
                            <div class="form-control bg-light">${item.time}</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Class</label>
                            <div class="form-control bg-light">${item.seatClass}</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-guests-${index}" class="form-label">Number of Passengers</label>
                            <div class="guest-control">
                                <button class="decrease-guests" data-index="${index}">-</button>
                                <input type="number" class="booking-guests" id="booking-guests-${index}" 
                                    value="${item.guests}" min="1" data-index="${index}">
                                <button class="increase-guests" data-index="${index}">+</button>
                            </div>
                        </div>
                    </div>
                `;
            } else if (item.type === 'room') {
                // Room-specific details
                detailsHtml = `
                    <div class="booking-form">
                        <div class="form-group">
                            <label for="checkin-date-${index}" class="form-label">Check-in Date</label>
                            <input type="date" class="form-control checkin-date" id="checkin-date-${index}" 
                                value="${item.checkIn}" min="${today}" data-index="${index}">
                        </div>
                        
                        <div class="form-group">
                            <label for="checkout-date-${index}" class="form-label">Check-out Date</label>
                            <input type="date" class="form-control checkout-date" id="checkout-date-${index}" 
                                value="${item.checkOut}" min="${today}" data-index="${index}">
                        </div>
                        
                        <div class="form-group">
                            <label for="nights-${index}" class="form-label">Number of Nights</label>
                            <div class="guest-control">
                                <button class="decrease-nights" data-index="${index}">-</button>
                                <input type="number" class="nights" id="nights-${index}" 
                                    value="${item.nights || 1}" min="1" data-index="${index}" readonly>
                                <button class="increase-nights" data-index="${index}">+</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-guests-${index}" class="form-label">Number of Rooms</label>
                            <div class="guest-control">
                                <button class="decrease-guests" data-index="${index}">-</button>
                                <input type="number" class="booking-guests" id="booking-guests-${index}" 
                                    value="${item.guests}" min="1" data-index="${index}">
                                <button class="increase-guests" data-index="${index}">+</button>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Default attraction-specific details
                let timeOptions = '';
                const openingHours = item.openingHours || '9:00 AM - 5:00 PM';

                // Generate time options based on opening hours
                if (openingHours.match(/(\d+:\d+\s*[AP]M)\s*-\s*(\d+:\d+\s*[AP]M)/i)) {
                    const matches = openingHours.match(/(\d+:\d+\s*[AP]M)\s*-\s*(\d+:\d+\s*[AP]M)/i);
                    if (matches && matches.length === 3) {
                        const openingTime = matches[1].trim();
                        const closingTime = matches[2].trim();

                        // Parse opening and closing times
                        const openingDate = new Date(`01/01/2023 ${openingTime}`);
                        const closingDate = new Date(`01/01/2023 ${closingTime}`);

                        // Generate hourly time slots from opening time until 1 hour before closing
                        const timeSlot = new Date(openingDate);
                        const lastSlot = new Date(closingDate.getTime() - 60 * 60 * 1000);

                        // Include slots from opening time up to and including 1 hour before closing
                        while (timeSlot <= lastSlot) {
                            const formattedHour = timeSlot.getHours() % 12 || 12;
                            const ampm = timeSlot.getHours() >= 12 ? 'PM' : 'AM';
                            const formattedTime = `${formattedHour}:00 ${ampm}`;

                            timeOptions += `<option value="${formattedTime}" ${item.time === formattedTime ? 'selected' : ''}>${formattedTime}</option>`;

                            // Add an hour
                            timeSlot.setHours(timeSlot.getHours() + 1);
                        }
                    } else {
                        // Fallback if regex match but groups not captured properly
                        timeOptions = getDefaultTimeOptions(item.time);
                    }
                } else {
                    // Fallback options
                    timeOptions = getDefaultTimeOptions(item.time);
                }

                detailsHtml = `
                    <div class="booking-form">
                        <div class="form-group">
                            <label for="booking-date-${index}" class="form-label">Select Date</label>
                            <input type="date" class="form-control booking-date" id="booking-date-${index}" 
                                value="${item.date}" min="${today}" data-index="${index}">
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-time-${index}" class="form-label">Select Time</label>
                            <select class="form-control booking-time" id="booking-time-${index}" data-index="${index}">
                                ${timeOptions}
                            </select>
                            <small class="form-text text-muted">Opening hours: ${item.openingHours}</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="booking-guests-${index}" class="form-label">Number of Guests</label>
                            <div class="guest-control">
                                <button class="decrease-guests" data-index="${index}">-</button>
                                <input type="number" class="booking-guests" id="booking-guests-${index}" 
                                    value="${item.guests}" min="1" data-index="${index}">
                                <button class="increase-guests" data-index="${index}">+</button>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Subtotal section
            const subtotalHtml = `
                <div class="subtotal-section">
                    <div class="subtotal-label">Subtotal:</div>
                    <div class="subtotal-amount" style="color: darkgreen;"><span id="subtotal-${index}">${formatCurrency(item.subtotal, item.currency)}</span></div>
                </div>
            `;

            // Combine all parts
            itemHtml = `
                <div class="cart-item" data-index="${index}">
                    ${headerHtml}
                    <div class="cart-item-details">
                        ${detailsHtml}
                        ${subtotalHtml}
                    </div>
                </div>
            `;

            cartItemsContainer.innerHTML += itemHtml;
        });

        // Add event listeners for removing items
        document.querySelectorAll(".remove-item").forEach(button => {
            button.addEventListener("click", function() {
                removeItem(this.getAttribute("data-index"));
            });
        });

        // Add event listeners for changing guest count
        document.querySelectorAll(".decrease-guests").forEach(button => {
            button.addEventListener("click", function() {
                const index = this.getAttribute("data-index");
                const input = document.getElementById(`booking-guests-${index}`);
                const currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    updateGuests(index, currentValue - 1);
                }
            });
        });

        document.querySelectorAll(".increase-guests").forEach(button => {
            button.addEventListener("click", function() {
                const index = this.getAttribute("data-index");
                const input = document.getElementById(`booking-guests-${index}`);
                const currentValue = parseInt(input.value);
                input.value = currentValue + 1;
                updateGuests(index, currentValue + 1);
            });
        });

        // Add event listeners for manually entering guest count
        document.querySelectorAll(".booking-guests").forEach(input => {
            input.addEventListener("change", function() {
                const index = this.getAttribute("data-index");
                const value = parseInt(this.value);
                if (value < 1) {
                    this.value = 1;
                    updateGuests(index, 1);
                } else {
                    updateGuests(index, value);
                }
            });
        });

        // Add event listeners for room-specific controls
        document.querySelectorAll(".checkin-date, .checkout-date").forEach(input => {
            input.addEventListener("change", function() {
                const index = this.getAttribute("data-index");
                updateDates(index);
            });
        });

        document.querySelectorAll(".decrease-nights, .increase-nights").forEach(button => {
            button.addEventListener("click", function() {
                const index = this.getAttribute("data-index");
                const input = document.getElementById(`nights-${index}`);
                const currentValue = parseInt(input.value);

                if (button.classList.contains('decrease-nights') && currentValue > 1) {
                    input.value = currentValue - 1;
                } else if (button.classList.contains('increase-nights')) {
                    input.value = currentValue + 1;
                }

                updateNights(index, parseInt(input.value));
            });
        });

        // Update all prices to the selected currency
        updatePricesDisplay();
        updateCartTotal();
    }

    // Helper function to get default time options
    function getDefaultTimeOptions(selectedTime) {
        return `
            <option value="9:00 AM" ${selectedTime === '9:00 AM' ? 'selected' : ''}>9:00 AM</option>
            <option value="10:00 AM" ${selectedTime === '10:00 AM' ? 'selected' : ''}>10:00 AM</option>
            <option value="11:00 AM" ${selectedTime === '11:00 AM' ? 'selected' : ''}>11:00 AM</option>
            <option value="12:00 PM" ${selectedTime === '12:00 PM' ? 'selected' : ''}>12:00 PM</option>
            <option value="1:00 PM" ${selectedTime === '1:00 PM' ? 'selected' : ''}>1:00 PM</option>
            <option value="2:00 PM" ${selectedTime === '2:00 PM' ? 'selected' : ''}>2:00 PM</option>
            <option value="3:00 PM" ${selectedTime === '3:00 PM' ? 'selected' : ''}>3:00 PM</option>
            <option value="4:00 PM" ${selectedTime === '4:00 PM' ? 'selected' : ''}>4:00 PM</option>
        `;
    }

    function updatePricesDisplay() {
        const cart = getCartFromLocalStorage();

        cart.forEach((item, index) => {
            const priceElement = document.getElementById(`price-display-${index}`);
            const subtotalElement = document.getElementById(`subtotal-${index}`);

            if (priceElement) {
                convertCurrency(item.price, item.currency, function(convertedPrice) {
                    priceElement.textContent = convertedPrice;
                });
            }

            if (subtotalElement) {
                convertCurrency(item.subtotal, item.currency, function(convertedSubtotal) {
                    subtotalElement.textContent = convertedSubtotal;
                });
            }
        });
    }

    function updateGuests(index, guests) {
        const cart = getCartFromLocalStorage();
        const item = cart[index];

        // Update the item guests and subtotal in the cart data
        item.guests = guests;

        // For room items, multiply by nights
        if (item.type === 'room') {
            item.subtotal = item.price * guests * (item.nights || 1);
        } else {
            item.subtotal = item.price * guests;
        }

        localStorage.setItem("cart", JSON.stringify(cart));

        // Update the displayed subtotal with conversion
        convertCurrency(item.subtotal, item.currency, function(convertedSubtotal) {
            document.getElementById(`subtotal-${index}`).textContent = convertedSubtotal;
            updateCartTotal();
        });
    }

    function updateDates(index) {
        const cart = getCartFromLocalStorage();
        const item = cart[index];

        if (item.type === 'room') {
            const checkinInput = document.getElementById(`checkin-date-${index}`);
            const checkoutInput = document.getElementById(`checkout-date-${index}`);

            if (checkinInput && checkoutInput) {
                const checkinDate = new Date(checkinInput.value);
                const checkoutDate = new Date(checkoutInput.value);

                // Ensure checkout date is after checkin date
                if (checkoutDate <= checkinDate) {
                    // Set checkout to the day after checkin
                    const nextDay = new Date(checkinDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    checkoutInput.value = nextDay.toISOString().split('T')[0];
                }

                // Calculate number of nights
                const nights = Math.floor((new Date(checkoutInput.value) - new Date(checkinInput.value)) / (1000 * 60 * 60 * 24));

                // Update the item in cart
                item.checkIn = checkinInput.value;
                item.checkOut = checkoutInput.value;
                item.nights = nights;

                // Update nights display
                const nightsInput = document.getElementById(`nights-${index}`);
                if (nightsInput) {
                    nightsInput.value = nights;
                }

                // Update subtotal based on new nights value
                item.subtotal = item.price * item.guests * nights;

                localStorage.setItem("cart", JSON.stringify(cart));

                // Update displayed subtotal
                convertCurrency(item.subtotal, item.currency, function(convertedSubtotal) {
                    document.getElementById(`subtotal-${index}`).textContent = convertedSubtotal;
                    updateCartTotal();
                });
            }
        }
    }

    function updateNights(index, nights) {
        const cart = getCartFromLocalStorage();
        const item = cart[index];

        if (item.type === 'room') {
            const checkinInput = document.getElementById(`checkin-date-${index}`);
            const checkoutInput = document.getElementById(`checkout-date-${index}`);

            if (checkinInput && checkoutInput) {
                // Update checkout date based on new nights value
                const checkinDate = new Date(checkinInput.value);
                const newCheckoutDate = new Date(checkinDate);
                newCheckoutDate.setDate(checkinDate.getDate() + nights);

                // Update the checkout input
                checkoutInput.value = newCheckoutDate.toISOString().split('T')[0];

                // Update the item in cart
                item.checkOut = checkoutInput.value;
                item.nights = nights;

                // Update subtotal based on new nights value
                item.subtotal = item.price * item.guests * nights;

                localStorage.setItem("cart", JSON.stringify(cart));

                // Update displayed subtotal
                convertCurrency(item.subtotal, item.currency, function(convertedSubtotal) {
                    document.getElementById(`subtotal-${index}`).textContent = convertedSubtotal;
                    updateCartTotal();
                });
            }
        }
    }

    function updateCartTotal() {
        const cart = getCartFromLocalStorage();
        let totalPromises = [];

        cart.forEach((item) => {
            totalPromises.push(new Promise((resolve) => {
                convertCurrency(item.subtotal, item.currency, function(convertedValue) {
                    // Extract just the numerical value from the formatted string
                    const numericValue = parseFloat(convertedValue.split(' ')[0].replace(/,/g, ''));
                    resolve(isNaN(numericValue) ? 0 : numericValue);
                });
            }));
        });

        Promise.all(totalPromises).then(values => {
            const total = values.reduce((sum, value) => sum + value, 0);
            document.getElementById('cart-total-amount').textContent = formatCurrency(total);
        });
    }

    function getCartFromLocalStorage() {
        return JSON.parse(localStorage.getItem('cart')) || [];
    }

    function removeItem(index) {
        let cart = getCartFromLocalStorage();
        cart.splice(index, 1);
        localStorage.setItem('cart', JSON.stringify(cart));
        displayCart();
        updateCartCountDisplay();
    }

    function updateCartCountDisplay() {
        const cart = getCartFromLocalStorage();
        const cartCount = cart.length;

        // Update any cart counters in the header if they exist
        const cartCountElements = document.querySelectorAll('.cart-count');
        if (cartCountElements.length > 0) {
            cartCountElements.forEach(element => {
                element.textContent = cartCount;
                element.style.display = cartCount > 0 ? 'inline-block' : 'none';
            });
        }
    }

    function updateCartItem(index, field, value) {
        let cart = getCartFromLocalStorage();
        if (cart[index]) {
            cart[index][field] = value;

            // If we're updating guests, also update the subtotal
            if (field === 'guests') {
                if (cart[index].type === 'room') {
                    cart[index].subtotal = cart[index].price * value * (cart[index].nights || 1);
                } else {
                    cart[index].subtotal = cart[index].price * value;
                }
            }

            localStorage.setItem('cart', JSON.stringify(cart));
        }
    }

    function setupEventListeners() {
        // Clear cart button
        const clearCartBtn = document.getElementById('clear-cart');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear your cart?')) {
                    localStorage.removeItem('cart');
                    displayCart();
                    updateCartCountDisplay();
                }
            });
        }

        // Update cart button
        const updateCartBtn = document.getElementById('update-cart');
        if (updateCartBtn) {
            updateCartBtn.addEventListener('click', function() {
                let cart = getCartFromLocalStorage();

                // Update all item details from the form fields
                for (let i = 0; i < cart.length; i++) {
                    const item = cart[i];

                    if (item.type === 'flight') {
                        // Update flight-specific fields
                        const dateInput = document.getElementById(`booking-date-${i}`);
                        const guestsInput = document.getElementById(`booking-guests-${i}`);

                        if (dateInput && guestsInput) {
                            item.date = dateInput.value;
                            item.guests = parseInt(guestsInput.value);
                            item.subtotal = item.price * item.guests;
                        }
                    } else if (item.type === 'room') {
                        // Update room-specific fields
                        const checkinInput = document.getElementById(`checkin-date-${i}`);
                        const checkoutInput = document.getElementById(`checkout-date-${i}`);
                        const nightsInput = document.getElementById(`nights-${i}`);
                        const guestsInput = document.getElementById(`booking-guests-${i}`);

                        if (checkinInput && checkoutInput && nightsInput && guestsInput) {
                            item.checkIn = checkinInput.value;
                            item.checkOut = checkoutInput.value;
                            item.nights = parseInt(nightsInput.value);
                            item.guests = parseInt(guestsInput.value);
                            item.subtotal = item.price * item.guests * item.nights;
                        }
                    } else {
                        // Update attraction-specific fields
                        const dateInput = document.getElementById(`booking-date-${i}`);
                        const timeSelect = document.getElementById(`booking-time-${i}`);
                        const guestsInput = document.getElementById(`booking-guests-${i}`);

                        if (dateInput && timeSelect && guestsInput) {
                            item.date = dateInput.value;
                            item.time = timeSelect.value;
                            item.guests = parseInt(guestsInput.value);
                            item.subtotal = item.price * item.guests;
                        }
                    }
                }

                localStorage.setItem('cart', JSON.stringify(cart));

                // Show success message
                alert('Cart updated successfully!');

                // Refresh the display to show any changes
                displayCart();
            });
        }

        // Checkout button
        const checkoutBtn = document.getElementById('checkout');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function() {
                window.location.href = 'checkout.php';
            });
        }

        // Listen for date changes
        document.querySelectorAll('.booking-date').forEach(input => {
            input.addEventListener('change', function() {
                const index = this.getAttribute('data-index');
                updateCartItem(index, 'date', this.value);
            });
        });

        // Listen for time changes
        document.querySelectorAll('.booking-time').forEach(select => {
            select.addEventListener('change', function() {
                const index = this.getAttribute('data-index');
                updateCartItem(index, 'time', this.value);
            });
        });
    }

    // Initialization
    document.addEventListener('DOMContentLoaded', function() {
        displayCart();
        setupEventListeners();
        updateCartCountDisplay();

        // Add event listener for currency change using existing links in header
        const currencyLinks = document.querySelectorAll('#currencyDropdown + .dropdown-menu a');
        currencyLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const currencyUrl = this.getAttribute('href');

                // Send AJAX request to change the session currency
                fetch(currencyUrl)
                    .then(response => {
                        if (response.ok) {
                            // Refresh the page to show new currency
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error changing currency:', error);
                    });
            });
        });
    });
</script>

<?php include 'footer.php'; ?>