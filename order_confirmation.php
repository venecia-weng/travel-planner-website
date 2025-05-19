<?php
session_start();
$page_title = 'Order Confirmation - RoundTours';
include 'header.php';
require_once 'currency_functions.php';

// Get current currency
$current_currency = getCurrentCurrency() ?: 'SGD';

// Generate a random order number
$order_number = 'RT-' . date('Ymd') . '-' . rand(1000, 9999);

// Store order number in session
$_SESSION['last_order'] = $order_number;
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="success-icon mb-4">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <h2 class="mb-3">Thank You for Your Purchase!</h2>
                        <p class="lead mb-4">Your order has been placed and is being processed.</p>
                        <p class="order-info">
                            Order #: <strong><?php echo $order_number; ?></strong><br>
                            Date: <strong><?php echo date('F j, Y'); ?></strong>
                        </p>
                    </div>
                    
                    <div class="order-summary mb-4">
                        <h4 class="mb-3">Order Summary</h4>
                        <div id="confirmation-items">
                            <p class="text-muted">Your cart was empty or has been cleared.</p>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p>An email confirmation has been sent to your inbox.</p>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">Return to Home</a>
                            <a href="itinerary.php" class="btn btn-outline-primary ms-2">View My Itinerary</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .success-icon {
        font-size: 6rem;
        color: #28a745;
    }
    
    .order-info {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        display: inline-block;
    }
    
    .order-summary {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: left;
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
        background-color: #e3f2fd;
        color: #0d6efd;
    }

    .item-type-room {
        background-color: #e8f5e9;
        color: #28a745;
    }

    .item-type-attraction {
        background-color: #fff3cd;
        color: #ffc107;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Try to retrieve cart from localStorage before it's cleared
    const savedCart = localStorage.getItem('cart');
    const confirmationItems = document.getElementById('confirmation-items');
    
    if (savedCart) {
        const cart = JSON.parse(savedCart);
        
        if (cart.length > 0) {
            let itemsHTML = '';
            let totalAmount = 0;
            
            // Function to convert currency (simplified for confirmation page)
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
                
                const params = 'amount=' + amount + '&fromCurrency=' + fromCurrency + '&currency=<?php echo $current_currency; ?>';
                xhr.send(params);
            }
            
            // Process each item
            const processItems = async () => {
                for (let i = 0; i < cart.length; i++) {
                    const item = cart[i];
                    
                    // Determine item type display
                    const itemType = item.type || 'attraction';
                    const itemTypeBadge = `<span class="item-type-badge item-type-${itemType}">${itemType.charAt(0).toUpperCase() + itemType.slice(1)}</span>`;
                    
                    // Format details based on item type
                    let detailsHtml = '';
                    
                    if (item.type === 'flight') {
                        detailsHtml = `
                            <small class="text-muted d-block">
                                ${item.origin} to ${item.destination}
                            </small>
                            <small class="text-muted d-block">
                                ${item.date} at ${item.time} • ${item.guests} passenger(s)
                            </small>
                        `;
                    } else if (item.type === 'room') {
                        detailsHtml = `
                            <small class="text-muted d-block">
                                Check-in: ${item.checkIn} • Check-out: ${item.checkOut}
                            </small>
                            <small class="text-muted d-block">
                                ${item.nights} night(s) • ${item.guests} room(s)
                            </small>
                        `;
                    } else {
                        detailsHtml = `
                            <small class="text-muted d-block">
                                ${item.date} at ${item.time || 'N/A'}
                            </small>
                            <small class="text-muted d-block">
                                ${item.guests} guest(s)
                            </small>
                        `;
                    }
                    
                    // Convert item subtotal to current currency
                    await new Promise(resolve => {
                        convertCurrency(item.subtotal, item.currency, function(convertedValue) {
                            itemsHTML += `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="fw-bold">${item.name} ${itemTypeBadge}</div>
                                        ${detailsHtml}
                                    </div>
                                    <div>
                                        ${convertedValue}
                                    </div>
                                </div>
                            `;
                            resolve();
                        });
                    });
                }
                
                // Add total section
                const totalSection = `
                    <hr class="my-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Total</strong>
                        </div>
                        <div>
                            <strong id="total-amount">Calculating...</strong>
                        </div>
                    </div>
                `;
                
                itemsHTML += totalSection;
                confirmationItems.innerHTML = itemsHTML;
                
                // Calculate and display total
                let totalPromises = [];
                cart.forEach(item => {
                    totalPromises.push(new Promise(resolve => {
                        convertCurrency(item.subtotal, item.currency, function(convertedValue) {
                            // Extract just the numerical value
                            const numericValue = parseFloat(convertedValue.split(' ')[0].replace(/,/g, ''));
                            resolve(isNaN(numericValue) ? 0 : numericValue);
                        });
                    }));
                });
                
                Promise.all(totalPromises).then(values => {
                    const subtotal = values.reduce((sum, value) => sum + value, 0);
                    const taxes = subtotal * 0.07; // 7% tax rate
                    const total = subtotal + taxes;
                    
                    document.getElementById('total-amount').textContent = 
                        parseFloat(total).toFixed(2) + ' <?php echo $current_currency; ?>';
                });
            };
            
            // Process and display items
            processItems();
        }
    }
    
    // Clear the cart now that we've displayed the confirmation
    localStorage.removeItem('cart');
    
    // Update cart count in header
    const cartCountElements = document.querySelectorAll('.cart-count');
    if (cartCountElements.length > 0) {
        cartCountElements.forEach(element => {
            element.textContent = '0';
            element.style.display = 'none';
        });
    }
});
</script>

<?php include 'footer.php'; ?>