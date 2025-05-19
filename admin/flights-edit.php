<?php
// Start session
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../admin-login.php');
    exit;
}

// Include database connection
require_once '../includes/db_connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: flights-view.php');
    exit;
}

$flight_id = intval($_GET['id']);

$query = "Select f.*, a.* from flights f inner join airlines a on f.airline_id = a.airline_id where f.flight_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $flight_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: flights-view.php');
    exit;
}

$flight = $result->fetch_assoc();
$stmt->close();

// Get airlines for dropdown
$airlines_query = "SELECT * FROM airlines";
$airlines_result = $conn->query($airlines_query);
$airlines = [];
if ($airlines_result) {
    while ($row = $airlines_result->fetch_assoc()) {
        $airlines[] = $row;
    }
}

// Set page title
$page_title = 'Edit Flights - RoundTours';
$active_page = 'flights';

$errors = [];
$flight_data = [
    'airline_id' => $flight['airline_id'],
    'flight_number' => $flight['flight_number'],
    'origin' => $flight['origin'],
    'destination' => $flight['destination'],
    'departure_date_time' => $flight['departure_date_time'],
    'arrival_date_time' => $flight['arrival_date_time'],
    'economy_price' => $flight['economy_price'],
    'premium_economy_price' => $flight['premium_economy_price'],
    'business_price' => $flight['business_price'],
    'first_class_price' => $flight['first_class_price'],
    'economy_seats_available' => $flight['economy_seats_available'],
    'premium_economy_seats_available' => $flight['premium_economy_seats_available'],
    'business_seats_available' => $flight['business_seats_available'],
    'first_class_seats_available' => $flight['first_class_seats_available']
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $flight_data = [
        'airline_id' => !empty($_POST['airline_id']) ? intval($_POST['airline_id']) : null,
        'flight_number' => trim($_POST['flight_number'] ?? ''),
        'origin' => trim($_POST['origin'] ?? ''),
        'destination' => trim($_POST['destination'] ?? ''),
        'departure_date_time' => !empty($_POST['departure_date_time']) ? trim($_POST['departure_date_time']) : null,
        'arrival_date_time' => !empty($_POST['arrival_date_time']) ? trim($_POST['arrival_date_time']) : null,

        // Prices - convert to float
        'economy_price' => !empty($_POST['economy_price']) ? floatval($_POST['economy_price']) : null,
        'premium_economy_price' => !empty($_POST['premium_economy_price']) ? floatval($_POST['premium_economy_price']) : null,
        'business_price' => !empty($_POST['business_price']) ? floatval($_POST['business_price']) : null,
        'first_class_price' => !empty($_POST['first_class_price']) ? floatval($_POST['first_class_price']) : null,

        // Seats available - convert to integer
        'economy_seats_available' => !empty($_POST['economy_seats_available']) ? intval($_POST['economy_seats_available']) : null,
        'premium_economy_seats_available' => !empty($_POST['premium_economy_seats_available']) ? intval($_POST['premium_economy_seats_available']) : null,
        'business_seats_available' => !empty($_POST['business_seats_available']) ? intval($_POST['business_seats_available']) : null,
        'first_class_seats_available' => !empty($_POST['first_class_seats_available']) ? intval($_POST['first_class_seats_available']) : null
    ];

    // Validate form data
    $errors = [];

    // Validate basic flight information
    if (empty($flight_data['airline_id'])) {
        $errors[] = "Airline ID is required";
    }

    if (empty($flight_data['flight_number'])) {
        $errors[] = "Flight number is required";
    }

    if (empty($flight_data['origin'])) {
        $errors[] = "Origin is required";
    }

    if (empty($flight_data['destination'])) {
        $errors[] = "Destination is required";
    }

    if (empty($flight_data['departure_date_time'])) {
        $errors[] = "Departure date and time is required";
    }

    if (empty($flight_data['arrival_date_time'])) {
        $errors[] = "Arrival date and time is required";
    }

    // Validate prices
    if ($flight_data['economy_price'] === null || $flight_data['economy_price'] === '') {
        $errors[] = "Economy class price is required";
    } elseif (!is_numeric($flight_data['economy_price']) || $flight_data['economy_price'] < 0) {
        $errors[] = "Economy class price must be a positive number";
    }

    if ($flight_data['premium_economy_price'] === null || $flight_data['premium_economy_price'] === '') {
        $errors[] = "Premium economy class price is required";
    } elseif (!is_numeric($flight_data['premium_economy_price']) || $flight_data['premium_economy_price'] < 0) {
        $errors[] = "Premium economy class price must be a positive number";
    }

    if ($flight_data['business_price'] === null || $flight_data['business_price'] === '') {
        $errors[] = "Business class price is required";
    } elseif (!is_numeric($flight_data['business_price']) || $flight_data['business_price'] < 0) {
        $errors[] = "Business class price must be a positive number";
    }

    if ($flight_data['first_class_price'] === null || $flight_data['first_class_price'] === '') {
        $errors[] = "First class price is required";
    } elseif (!is_numeric($flight_data['first_class_price']) || $flight_data['first_class_price'] < 0) {
        $errors[] = "First class price must be a positive number";
    }

    // Validate seats available
    if ($flight_data['economy_seats_available'] === null || $flight_data['economy_seats_available'] === '') {
        $errors[] = "Economy class seats available is required";
    } elseif (!is_numeric($flight_data['economy_seats_available']) || $flight_data['economy_seats_available'] < 0) {
        $errors[] = "Economy class seats must be a positive integer";
    }

    if ($flight_data['premium_economy_seats_available'] === null || $flight_data['premium_economy_seats_available'] === '') {
        $errors[] = "Premium economy class seats available is required";
    } elseif (!is_numeric($flight_data['premium_economy_seats_available']) || $flight_data['premium_economy_seats_available'] < 0) {
        $errors[] = "Premium economy class seats must be a positive integer";
    }

    if ($flight_data['business_seats_available'] === null || $flight_data['business_seats_available'] === '') {
        $errors[] = "Business class seats available is required";
    } elseif (!is_numeric($flight_data['business_seats_available']) || $flight_data['business_seats_available'] < 0) {
        $errors[] = "Business class seats must be a positive integer";
    }

    if ($flight_data['first_class_seats_available'] === null || $flight_data['first_class_seats_available'] === '') {
        $errors[] = "First class seats available is required";
    } elseif (!is_numeric($flight_data['first_class_seats_available']) || $flight_data['first_class_seats_available'] < 0) {
        $errors[] = "First class seats must be a positive integer";
    }

    // Additional validation for date/time consistency
    if (!empty($flight_data['departure_date_time']) && !empty($flight_data['arrival_date_time'])) {
        $departure = new DateTime($flight_data['departure_date_time']);
        $arrival = new DateTime($flight_data['arrival_date_time']);

        if ($arrival <= $departure) {
            $errors[] = "Arrival date/time must be after departure date/time";
        }
    }

    // Insert data if no errors
    if (empty($errors)) {

        $sql = "UPDATE flights set airline_id = ?, flight_number = ?, origin = ?, destination = ?, departure_date_time = ?, 
            arrival_date_time = ?, economy_price = ?, premium_economy_price = ?, business_price = ?, first_class_price = ?, 
            economy_seats_available = ?, premium_economy_seats_available = ?, business_seats_available = ?, first_class_seats_available = ?
            where flight_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssddddiiiii",
            $flight_data['airline_id'],
            $flight_data['flight_number'],
            $flight_data['origin'],
            $flight_data['destination'],
            $flight_data['departure_date_time'],
            $flight_data['arrival_date_time'],
            $flight_data['economy_price'],
            $flight_data['premium_economy_price'],
            $flight_data['business_price'],
            $flight_data['first_class_price'],
            $flight_data['economy_seats_available'],
            $flight_data['premium_economy_seats_available'],
            $flight_data['business_seats_available'],
            $flight_data['first_class_seats_available'],
            $flight_id
        );

        if ($stmt->execute()) {
            // Redirect to flights page
            header('Location: flights-view.php?updated=1');
            exit;
        } else {
            $errors[] = "Error editing flight: " . $conn->error;
        }
    }
}

// Include admin header
include 'admin-header.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Edit Flight</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="flights-view.php">Flights</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Flight</li>
            </ol>
        </nav>
    </div>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Basic Information</h4>

                        <div class="mb-3">
                            <label for="airline_id" class="form-label">Airline <span class="text-danger">*</span></label>
                            <select
                                class="form-control"
                                id="airline_id"
                                name="airline_id" value="<?php echo $flight['airline_id']; ?>"
                                required>
                                <option value="">Select Airline</option>
                                <?php foreach ($airlines as $airline): ?>
                                    <option
                                        value="<?php echo $airline['airline_id']; ?>"
                                        <?php echo ($flight_data['airline_id'] == $airline['airline_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($airline['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="flight_number" class="form-label">Flight Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="flight_number" name="flight_number" required
                                value="<?php echo htmlspecialchars($flight_data['flight_number']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="origin" class="form-label">Origin <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="origin" name="origin" required
                                value="<?php echo htmlspecialchars($flight_data['origin']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">Destination <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="destination" name="destination" required
                                value="<?php echo htmlspecialchars($flight_data['destination']); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="departure_date_time">Departure Date Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="departure_date_time" required
                                value="<?php echo isset($flight_data['departure_date_time']) ? date('Y-m-d\TH:i', strtotime($flight_data['departure_date_time'])) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="arrival_date_time">Arrival Date Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" name="arrival_date_time" required
                                value="<?php echo isset($flight_data['arrival_date_time']) ? date('Y-m-d\TH:i', strtotime($flight_data['arrival_date_time'])) : ''; ?>">
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="col-md-6">
                        <h4 class="mb-3">Prices and Seats</h4>

                        <?php
                        $flightClasses = [
                            'economy' => 'Economy',
                            'premium_economy' => 'Premium Economy',
                            'business' => 'Business',
                            'first_class' => 'First Class'
                        ];

                        foreach ($flightClasses as $key => $class) {
                            echo '<div class="mb-3">';
                            echo '    <label for="' . $key . '_price" class="form-label">' . $class . ' Price <span class="text-danger">*</span></label>';
                            echo '    <input type="text" class="form-control" id="' . $key . '_price" name="' . $key . '_price" value="' . htmlspecialchars($flight_data[$key . '_price'] ?? '') . '" required>';
                            echo '</div>';

                            echo '<div class="mb-3">';
                            echo '    <label for="' . $key . '_seats_available" class="form-label">' . $class . ' Seats Available <span class="text-danger">*</span></label>';
                            echo '    <input type="number" class="form-control" id="' . $key . '_seats_available" name="' . $key . '_seats_available" value="' . htmlspecialchars($flight_data[$key . '_seats_available'] ?? '') . '" required>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="flights-edit.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Flight</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>