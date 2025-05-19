<?php
// Start session
session_start();

// Set the correct Content-Type header
header('Content-Type: application/json');

require_once 'includes/db_connect.php';

try {
    // Begin the transaction
    $conn->begin_transaction();

    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate input
    if (
        empty($data['transaction_id']) ||
        empty($data['amount']) ||
        empty($data['currency']) ||
        empty($data['status']) ||
        empty($data['payment_date'])
    ) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit();
    }

    // Extract data
    $transactionId = $data['transaction_id'];
    $amount = $data['amount'];
    $currency = $data['currency'];
    $status = $data['status'];
    $paymentDate = $data['payment_date'];
    $cart = $data['cart'];

    // Function to extract numerical part of the ID
    function extractNumericalId($id)
    {
        return preg_replace('/[^0-9]/', '', $id);
    }

    // Get user ID from session
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        $conn->rollback(); // Rollback in case of error
        exit();
    }
    $userId = $_SESSION['user_id'];
    $trip_id = 1;

    try {
        // Prepare the SQL statement for payments
        $sql = "INSERT INTO payments (user_id, amount, currency, payment_method, transaction_id, status, payment_date, trip_id) 
            VALUES (?, ?, ?, ?, ?, ?, STR_TO_DATE(?, '%Y-%m-%dT%H:%i:%sZ'),?)";

        $stmt = $conn->prepare($sql);

        // Bind parameters
        $paymentMethod = 'PayPal'; // Hardcoded as 'PayPal'
        $stmt->bind_param(
            "idsssssi", // Types: int, decimal, string, string, string, string, string, int
            $userId,
            $amount,
            $currency,
            $paymentMethod,
            $transactionId,
            $status,
            $paymentDate,
            $trip_id
        );

        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception("Error inserting payment record: " . $stmt->error);
        }
        $paymentId = (int) $conn->insert_id;

        // Close the statement
        $stmt->close();

        foreach ($cart as $item) {
            $id = extractNumericalId($item['id']);
            // Prepare the SQL statement
            $sql = "INSERT INTO ";
            if ($item['type'] == 'flight' || $item['type'] == 'attraction') {
                $sql .= "bookings (user_id, booking_type, item_id, quantity, total_price, payment_id, start_date) 
                    VALUES (?, ?, ?, ?, ?, ?, STR_TO_DATE(?, '%Y-%m-%d'))";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "isiidis",
                    $userId,
                    $item['type'],
                    $id,
                    $item['guests'],
                    $item['subtotal'],
                    $paymentId,
                    $item['date']
                );
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting booking record: " . $stmt->error);
                }
                $stmt->close();
            } else if ($item['type'] == 'room') {
                $sql .= "bookings (user_id, booking_type, item_id, quantity, total_price, payment_id, start_date, end_date) 
                    VALUES (?, ?, ?, ?, ?, ?, STR_TO_DATE(?, '%Y-%m-%d'), STR_TO_DATE(?, '%Y-%m-%d'))";
                $stmt = $conn->prepare($sql);
                $quantity = $item['guests'] * $item['nights'];
                $stmt->bind_param(
                    "isiidiss",
                    $userId,
                    $item['type'],
                    $id,
                    $quantity,
                    $item['subtotal'],
                    $paymentId,
                    $item['checkIn'],
                    $item['checkOut']
                );
                if (!$stmt->execute()) {
                    throw new Exception("Error inserting booking record: " . $stmt->error);
                }
                $stmt->close();
            }
        }

        // Commit the transaction if all inserts succeed
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'All records inserted successfully.']);
    } catch (Exception $e) {
        // Rollback the transaction in case of any error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }

    // Close the connection
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
}
