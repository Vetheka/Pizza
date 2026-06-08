<?php
session_start();
include('../db.php');

if ($_SESSION['role'] !== 'manager') {
    header("Location: ../login.php");
    exit();
}

// Fetch staff accounts with salary
$stmt = $conn->query("
    SELECT users.id, users.role, staff.name, staff.email, staff.phone, staff.address, staff.salary 
    FROM users 
    JOIN staff ON users.id = staff.user_id 
    WHERE users.role != 'owner'
");
$staffAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rolePriority = [
    'owner' => 3,
    'manager' => 2,
    'cashier' => 1,
];

usort($staffAccounts, function($a, $b) use ($rolePriority) {
    return $rolePriority[$b['role']] <=> $rolePriority[$a['role']];
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Staff Management</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f9fff9;
        margin: 0;
        padding: 20px;
        color: #333;
    }
    .header {
        background-color: #218838;
        color: #fff;
        padding: 20px 30px;
        text-align: center;
        position: relative;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        margin-bottom: 30px;
        border-radius: 6px;
    }
    .header h1 {
        margin: 0;
        font-weight: 700;
        font-size: 2rem;
    }
    .logout-btn {
        position: absolute;
        right: 30px;
        top: 50%;
        transform: translateY(-50%);
        background: #dc3545;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s ease;
        text-decoration: none;
    }
    .logout-btn:hover {
        background: #b02a37;
    }

    form#staffForm {
        background: white;
        padding: 20px 25px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        max-width: 900px;
        margin: 0 auto 40px auto;
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        justify-content: center;
    }
    form#staffForm input[type="text"],
    form#staffForm input[type="email"],
    form#staffForm input[type="password"],
    form#staffForm input[type="number"],
    form#staffForm select {
        flex: 1 1 150px;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    form#staffForm input:focus,
    form#staffForm select:focus {
        border-color: #218838;
        outline: none;
    }
    form#staffForm button {
        background-color: #218838;
        color: white;
        border: none;
        padding: 12px 25px;
        font-weight: 600;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s ease;
        flex: 0 0 auto;
        min-width: 120px;
    }
    form#staffForm button:hover {
        background-color: #19692c;
    }
    #cancelUpdateBtn {
        background-color: #6c757d;
    }
    #cancelUpdateBtn:hover {
        background-color: #565e64;
    }

    /* Search container */
    .search-container {
        max-width: 900px;
        margin: 0 auto 20px auto;
        display: flex;
        gap: 10px;
    }
    #searchInput {
        flex: 1;
        padding: 10px 15px;
        font-size: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        transition: border-color 0.3s ease;
    }
    #searchInput:focus {
        border-color: #218838;
        outline: none;
    }
    #searchBtn {
        padding: 10px 25px;
        background-color: #218838;
        border: none;
        color: white;
        font-weight: 600;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    #searchBtn:hover {
        background-color: #19692c;
    }

    h3 {
        max-width: 900px;
        margin: 0 auto 10px auto;
        font-weight: 700;
        color: #218838;
        text-align: center;
    }

    table {
        width: 100%;
        max-width: 900px;
        margin: 0 auto 40px auto;
        border-collapse: collapse;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
        background: white;
    }
    th, td {
        padding: 12px 15px;
        text-align: center;
        border-bottom: 1px solid #eee;
        font-size: 0.95rem;
    }
    th {
        background-color: #e0f2e9;
        font-weight: 700;
        color: #19692c;
        user-select: none;
    }
    tbody tr:hover {
        background-color: #f1fdf3;
    }

    /* Role badges */
    .role-manager {
        background-color: #cce5ff;
        color: #004085;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        font-size: 0.85rem;
    }
    .role-cashier {
        background-color: #fff3cd;
        color: #856404;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        font-size: 0.85rem;
    }
    .role-owner {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        font-size: 0.85rem;
    }
    .action-link {
        background: none;
        border: none;
        color: #007bff;
        text-decoration: underline;
        cursor: pointer;
        font-size: 14px;
        padding: 0;
        margin-right: 10px;
    }

    .action-link:hover {
        color: #0056b3;
        text-decoration: none;
    }

    .delete-link {
        color: #dc3545;
    }

    .delete-link:hover {
        color: #a71d2a;
    }

    /* Action buttons */
    .update-btn, .delete-btn {
        padding: 6px 12px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 0 3px;
        transition: background-color 0.3s ease;
    }
    .update-btn {
        background-color: #007bff;
        color: white;
    }
    .update-btn:hover {
        background-color: #0056b3;
    }
    .delete-btn {
        background-color: #dc3545;
        color: white;
    }
    .delete-btn:hover {
        background-color: #a71d2a;
    }
    form.inline {
        display: inline;
        margin: 0;
        padding: 0;
    }
    .back-btn {
        position: absolute;
        left: 30px;
        top: 50%;
        transform: translateY(-50%);
        background: #ffc107;
        color: #212529;
        border: none;
        padding: 8px 16px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        transition: background 0.3s ease;
    }

    .back-btn:hover {
        background: #e0a800;
    }
</style>
</head>
<body>

<div class="header">
    <h1>Staff Management</h1>
    <a href="../logout.php" class="logout-btn">Logout</a>
    <a href="manager.php" class="back-btn"> Back</a>
</div>

<!-- Staff Form -->
<form method="post" action="save_staff.php" id="staffForm" autocomplete="off">
    <input type="hidden" name="id" id="staffId">
    <input type="text" name="name" id="name" placeholder="Name" required>
    <input type="email" name="email" id="email" placeholder="Email" required>
    <input type="text" name="phone" id="phone" placeholder="Phone" required>
    <input type="text" name="address" id="address" placeholder="Address" required>
    <input type="number" name="salary" id="salary" placeholder="Salary" min="0" step="0.01" required>
    <select name="role" id="role" required>
        <option value="cashier">Cashier</option>
        <option value="manager">Manager</option>
    </select>
    <button type="submit" id="submitBtn">Add Staff</button>
    <button type="button" id="cancelUpdateBtn" style="display:none;">Cancel</button>
</form>

<!-- Search bar -->
<div class="search-container">
    <input type="text" id="searchInput" placeholder="Search staff by name or email">
    <button id="searchBtn">Search</button>
</div>

<h3>Staff Accounts</h3>

<table id="staffTable">
<thead>
    <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Salary</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php foreach ($staffAccounts as $staff): ?>
<tr>
    <td><?= htmlspecialchars($staff['name']) ?></td>
    <td><?= htmlspecialchars($staff['email']) ?></td>
    <td><?= htmlspecialchars($staff['phone']) ?></td>
    <td><?= htmlspecialchars($staff['address']) ?></td>
    <td>$<?= number_format($staff['salary'], 2) ?></td>
    <td>
        <span class="role-<?= htmlspecialchars($staff['role']) ?>">
            <?= ucfirst(htmlspecialchars($staff['role'])) ?>
        </span>
    </td>
    <td>
        <button class="update-btn" 
            data-id="<?= $staff['id'] ?>" 
            data-name="<?= htmlspecialchars($staff['name'], ENT_QUOTES) ?>"
            data-email="<?= htmlspecialchars($staff['email'], ENT_QUOTES) ?>"
            data-phone="<?= htmlspecialchars($staff['phone'], ENT_QUOTES) ?>"
            data-address="<?= htmlspecialchars($staff['address'], ENT_QUOTES) ?>"
            data-salary="<?= $staff['salary'] ?>"
            data-role="<?= $staff['role'] ?>"
        >Update</button>

        <form class="inline" method="post" action="delete_staff.php" onsubmit="return confirm('Delete this staff?');">
            <input type="hidden" name="id" value="<?= $staff['id'] ?>">
            <button type="submit" class="delete-btn">Delete</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<script>
const staffAccounts = <?= json_encode($staffAccounts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const form = document.getElementById('staffForm');
const submitBtn = document.getElementById('submitBtn');
const cancelBtn = document.getElementById('cancelUpdateBtn');

function clearForm() {
    form.reset();
    document.getElementById('staffId').value = '';
    submitBtn.textContent = 'Add Staff';
    cancelBtn.style.display = 'none';
    // Show password field again
    document.getElementById('password').required = true;
    document.getElementById('password').parentElement.style.display = 'inline-block';
}

function populateForm(staff) {
    document.getElementById('staffId').value = staff.id;
    document.getElementById('name').value = staff.name;
    document.getElementById('email').value = staff.email;
    document.getElementById('phone').value = staff.phone;
    document.getElementById('address').value = staff.address;
    document.getElementById('salary').value = staff.salary;
    document.getElementById('role').value = staff.role;
    // When updating, password is not required and hidden
    document.getElementById('password').required = false;
    document.getElementById('password').value = '';
    document.getElementById('password').parentElement.style.display = 'none';

    submitBtn.textContent = 'Update Staff';
    cancelBtn.style.display = 'inline-block';
}

document.querySelectorAll('.update-btn').forEach(button => {
    button.addEventListener('click', () => {
        const id = button.getAttribute('data-id');
        const staff = staffAccounts.find(s => s.id == id);
        if (staff) {
            populateForm(staff);
        }
    });
});

cancelBtn.addEventListener('click', () => {
    clearForm();
});

// Search functionality
document.getElementById('searchBtn').addEventListener('click', () => {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const tbody = document.querySelector('#staffTable tbody');
    tbody.querySelectorAll('tr').forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        if (name.includes(query) || email.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Also allow Enter key to trigger search
document.getElementById('searchInput').addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
        document.getElementById('searchBtn').click();
    }
});
</script>

</body>
</html>
