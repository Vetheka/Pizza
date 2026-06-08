<!DOCTYPE html>
<html>
<head>
    <title>POS Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #e9f7ef;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0px 4px 20px rgba(0, 128, 0, 0.1);
            width: 360px;
            border-top: 8px solid #28a745;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #28a745;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #333;
        }

        select,
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            margin-top: 25px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #218838;
        }

        .error-message {
            color: red;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>POS System Login</h2>
        <form method="post" action="login_process.php">
            <label for="role">Login as:</label>
            <select name="role" required>
                <option value="cashier">Cashier</option>
                <option value="manager">Manager</option>
                <option value="owner">Owner</option>
            </select>

            <label for="password">Password:</label>
            <input type="password" name="password" required>

            <button type="submit">Login</button>
        </form>

        <?php
        if (isset($_GET['error'])) {
            echo "<div class='error-message'>" . htmlspecialchars($_GET['error']) . "</div>";
        }
        ?>
    </div>
</body>
</html>
