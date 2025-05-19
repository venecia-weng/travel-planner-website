<?php
session_start();
$page_title = 'Checkout - RoundTours';
include 'header.php';
require_once 'currency_functions.php';

// Get current currency
$current_currency = getCurrentCurrency() ?: 'SGD';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h3 class="mb-0">Checkout</h3>
                </div>
                <div class="card-body">
                    <form id="checkout-form">
                        <div id="credit-card-payment">
                            <h4 class="mb-3">Billing Details</h4>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">First name</label>
                                    <input type="text" class="form-control" id="firstName" placeholder="" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Last name</label>
                                    <input type="text" class="form-control" id="lastName" placeholder="" required>
                                </div>

                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" placeholder="you@example.com" required>
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" placeholder="1234 Main St" required>
                                </div>

                                <div class="col-md-5">
                                    <label for="country" class="form-label">Country</label>
                                    <select class="form-select" id="country" required>
                                        <option value="">Choose...</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="United States">United States</option>
                                        <option value="Thailand">Thailand</option>
                                        <!-- Add more countries as needed -->
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="state" class="form-label">State/Province</label>
                                    <input type="text" class="form-control" id="state">
                                </div>

                                <div class="col-md-3">
                                    <label for="zip" class="form-label">Postal code</label>
                                    <input type="text" class="form-control" id="zip" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h4 class="mb-3" style="display: none">Card Details</h4>
                            <div class="row gy-3" style="display: none">
                                <div class="col-md-6">
                                    <label for="cc-name" class="form-label">Name on card</label>
                                    <input type="text" class="form-control" id="cc-name" placeholder="" required>
                                    <small class="text-muted">Full name as displayed on card</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="cc-number" class="form-label">Credit card number</label>
                                    <input type="text" class="form-control" id="cc-number" placeholder="" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="cc-expiration" class="form-label">Expiration</label>
                                    <input type="text" class="form-control" id="cc-expiration" placeholder="MM/YY" required>
                                </div>

                                <div class="col-md-3">
                                    <label for="cc-cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cc-cvv" placeholder="" required>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Order Summary</h4>
                </div>
                <div class="card-body">
                    <div id="order-items">
                        <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading cart items...</p>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="order-subtotal">-</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Taxes</span>
                        <span id="order-taxes">-</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-2 fw-bold">
                        <span>Total</span>
                        <span id="order-total">-</span>
                    </div>

                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            All prices shown in <strong><?php echo $current_currency; ?></strong>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#currencyModal" style="color: darkblue">Change</a>
                        </small>
                    </div>
                    <div class="payment-buttons mb-3">
                        <div id="paypal-button-container"></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Need Help?</h5>
                    <p class="mb-0"><i class="fas fa-phone me-2"></i> Call us at: +65 1234 5678</p>
                    <p class="mb-0"><i class="fas fa-envelope me-2"></i> Email: support@roundtours.com</p>
                </div>
            </div>
        </div>


    </div>
</div>

<!-- Currency Selection Modal -->
<div class="modal fade" id="currencyModal" tabindex="-1" aria-labelledby="currencyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="currencyModalLabel">Select Currency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php
                    $currencies = [
                        'SGD' => 'Singapore Dollar',
                        'USD' => 'US Dollar',
                        'EUR' => 'Euro',
                        'THB' => 'Thai Baht'
                    ];

                    foreach ($currencies as $code => $name):
                    ?>
                        <a href="change_currency.php?currency=<?php echo $code; ?>"
                            class="list-group-item list-group-item-action currency-link <?php echo ($current_currency === $code) ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $code; ?></strong> - <?php echo $name; ?>
                                </div>
                                <?php if ($current_currency === $code): ?>
                                    <i class="fas fa-check text-success"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
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
        /* Slightly darker blue background */
        color: #004085;
        /* Darker blue text for better contrast */
    }

    .item-type-room {
        background-color: #d4edda;
        /* Slightly darker green background */
        color: #155724;
        /* Darker green text for better contrast */
    }

    .item-type-attraction {
        background-color: #fff3cd;
        /* Keep the same background */
        color: #856404;
        /* Darker yellow text for better contrast */
    }

    input[type="radio"]:checked+.card-body {
        background-color: #f8f9fe;
        border-color: #0d6efd;
    }

    .payment-buttons {
        /* padding-inline: 30px; */
        padding-top: 10px;
    }
</style>

<script src="https://www.paypal.com/sdk/js?client-id=AdqEQGLMhwrYMh11A8A1tfCFhmMVIqnZV6VND5v0LkjeGcopx3yW2ZALUWbkulJ6fqZO81sWa7DyFp-o&currency=SGD&components=buttons,funding-eligibility"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let orderTotal = 0; // Store total amount for payment

        // Load cart items from localStorage
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const orderItemsContainer = document.getElementById('order-items');
        const subtotalElement = document.getElementById('order-subtotal');
        const taxesElement = document.getElementById('order-taxes');
        const totalElement = document.getElementById('order-total');

        // Helper function to format currency
        function formatCurrency(amount) {
            return `${parseFloat(amount).toFixed(2)} <?php echo $current_currency; ?>`;
        }

        // Calculate total with conversion
        function calculateOrderTotal() {
            // Convert all items to current currency and calculate totals
            let totalPromises = [];

            cart.forEach((item) => {
                totalPromises.push(new Promise((resolve) => {
                    // If item is already in target currency or has a convertedSubtotal
                    if (item.currency === '<?php echo $current_currency; ?>' || item.convertedSubtotal) {
                        resolve(item.convertedSubtotal || item.subtotal);
                    } else {
                        // Convert currency via AJAX
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'convert.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (this.status === 200) {
                                // Extract the numerical value from the response
                                const numValue = parseFloat(this.responseText.split(' ')[0].replace(/,/g, ''));
                                resolve(isNaN(numValue) ? 0 : numValue);
                            } else {
                                resolve(0);
                            }
                        };

                        const params = 'amount=' + item.subtotal + '&fromCurrency=' + item.currency + '&currency=<?php echo $current_currency; ?>';
                        xhr.send(params);
                    }
                }));
            });

            // Once all conversions are done
            Promise.all(totalPromises).then(values => {
                const subtotal = values.reduce((sum, val) => sum + val, 0);
                const taxes = subtotal * 0.07; // 7% tax rate
                const total = subtotal + taxes;

                // Store total for payment
                orderTotal = total;

                subtotalElement.textContent = formatCurrency(subtotal);
                taxesElement.textContent = formatCurrency(taxes);
                totalElement.textContent = formatCurrency(total);
            });
        }

        // Function to complete order for both payment methods
        function completeOrder(paymentDetails) {
            // Show success message
            alert('Thank you for your purchase! Your order has been placed.');

            // Redirect to order confirmation
            window.location.href = 'order_confirmation.php';
        }

        // Display cart items
        if (cart.length === 0) {
            orderItemsContainer.innerHTML = '<p class="text-muted">Your cart is empty.</p>';
            subtotalElement.textContent = formatCurrency(0);
            taxesElement.textContent = formatCurrency(0);
            totalElement.textContent = formatCurrency(0);
        } else {
            // Display order items
            orderItemsContainer.innerHTML = '';

            cart.forEach((item, index) => {
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

                const itemHTML = `
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-0">${item.name} ${itemTypeBadge}</h6>
                            ${detailsHtml}
                        </div>
                        <div>
                            <span id="item-subtotal-${index}">Loading...</span>
                        </div>
                    </div>
                </div>
            `;

                orderItemsContainer.innerHTML += itemHTML;

                // Convert and display each item's subtotal
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'convert.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        document.getElementById(`item-subtotal-${index}`).textContent = this.responseText;
                    }
                };

                const params = 'amount=' + item.subtotal + '&fromCurrency=' + item.currency + '&currency=<?php echo $current_currency; ?>';
                xhr.send(params);
            });

            // Calculate and display totals
            calculateOrderTotal();
        }

        // Handle currency change
        const currencyLinks = document.querySelectorAll('.currency-link');
        currencyLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const currencyUrl = this.getAttribute('href');

                // Change currency via AJAX
                fetch(currencyUrl)
                    .then(response => {
                        if (response.ok) {
                            // Reload page to reflect new currency
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error changing currency:', error);
                    });
            });
        });

        paypal.Buttons({
            // fundingSource: paypal.FUNDING.PAYPAL,
            createOrder: function(data, actions) {
                let parts = document.getElementById('order-total').textContent.split(" "); // Split by space
                let value = parseFloat(parts[0]); // Convert the first part to a number
                let currency = parts[1]; // The second part is the string
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: value,
                            currency_code: currency
                        },
                        description: 'Payment for items'
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    console.log('Payment completed:\n');
                    console.log(details);
                    console.log(JSON.stringify(details));

                    // Extract relevant data from the PayPal response
                    const transactionId = details.id;
                    const amount = details.purchase_units[0].amount.value;
                    const currency = details.purchase_units[0].amount.currency_code;
                    const status = details.status;
                    const paymentDate = details.create_time;
                    // Send the data to the server using AJAX
                    fetch('process_payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                transaction_id: transactionId,
                                amount: amount,
                                currency: currency,
                                status: status,
                                payment_date: paymentDate,
                                cart: cart
                            })
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                console.log('Payment record inserted successfully.');
                                completeOrder({
                                    payment_method: 'PayPal',
                                    status: 'completed'
                                })
                                // Optionally, redirect to a success page or update the UI
                            } else {
                                console.error('Error inserting payment record:', result.message);
                                alert('An error occurred while processing the payment.');
                            }
                        })
                        .catch(error => {
                            console.error('AJAX error:', error);
                            alert('An error occurred while communicating with the server.');
                        });
                });
            },
            onError: function(err) {
                console.error('PayPal error:', err);
                alert('An error occurred during the payment process.');
            }
        }).render('#paypal-button-container');
    });
</script>

<?php include 'footer.php'; ?>