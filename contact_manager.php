<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load environment variables
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Establish database connection
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed. Please try again later.");
}

// Initialize variables
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$offset = ($page - 1) * $limit;

// Insert contact
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars(trim($_POST['name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Invalid email format.');</script>");
    }

    $stmt = $conn->prepare("INSERT INTO contacts (name, phone, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $email);
    if ($stmt->execute()) {
        echo "<script>alert('Contact added successfully!');</script>";
    } else {
        echo "<script>alert('Failed to add contact.');</script>";
    }
    $stmt->close();
}

// Delete contact
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "<script>alert('Contact deleted successfully!');</script>";
    } else {
        echo "<script>alert('Failed to delete contact.');</script>";
    }
    $stmt->close();
}

// Retrieve contacts
$stmt = $conn->prepare("SELECT * FROM contacts LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$contacts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total number of contacts
$totalResult = $conn->query("SELECT COUNT(*) as total FROM contacts");
$totalContacts = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalContacts / $limit);

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Manager</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            margin: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contact Manager</h1>

        <!-- Form to Add Contact -->
        <div class="card mb-4">
            <div class="card-header">Add New Contact</div>
            <div class="card-body">
                <form method="POST" action="contact_manager.php">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Name" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control" placeholder="Phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Contact</button>
                </form>
            </div>
        </div>

        <!-- Display Contacts -->
        <div class="card">
            <div class="card-header">Contacts</div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contacts) > 0): ?>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($contact['id']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                    <td>
                                        <a href="contact_manager.php?delete=<?php echo $contact['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this contact?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No contacts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</body>
</html>
