<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Savoré Restaurant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    <link rel="stylesheet" href="../assets/style.css" />
    <style>
        .reset-password-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #181818;
            padding: 20px;
        }

        .reset-password-card {
            background: #181818;
            border: 1px solid #bfa46b;
            border-radius: 10px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .reset-password-card h1 {
            color: #bfa46b;
            font-family: "Georgia", serif;
            margin-bottom: 10px;
        }

        .reset-password-card h2 {
            color: #bfa46b;
            font-family: "Georgia", serif;
            font-size: 1.5em;
            margin-bottom: 20px;
        }

        .reset-password-form input {
            width: 100%;
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #bfa46b;
            background: #222;
            color: #fff;
            font-size: 16px;
            box-sizing: border-box;
        }

        .reset-password-form input::placeholder {
            color: #bfa46b;
            opacity: 0.8;
        }

        .reset-password-form button {
            width: 100%;
            padding: 12px;
            background: #bfa46b;
            color: #181818;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .reset-password-form button:hover {
            background: #e5c97b;
        }

        .reset-password-form button:disabled {
            background: #666;
            cursor: not-allowed;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .message.success {
            background-color: #4caf50;
            color: white;
        }

        .message.error {
            background-color: #f44336;
            color: white;
        }

        .back-link {
            display: inline-block;
            color: #bfa46b;
            text-decoration: none;
            margin-top: 20px;
        }

        .back-link:hover {
            color: #e5c97b;
        }
    </style>
</head>

<body>
    <div class="reset-password-container">
        <div class="reset-password-card">
            <h1>Savoré Restaurant</h1>
            <h2>Reset Password</h2>

            <div id="message-container"></div>

            <form id="reset-password-form" class="reset-password-form" style="display: none;">
                <input type="password" id="new-password" placeholder="New Password" required />
                <input type="password" id="confirm-password" placeholder="Confirm New Password" required />
                <button type="submit" id="reset-btn">Reset Password</button>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fa fa-arrow-left"></i> Back to Sign In
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');
            const messageContainer = document.getElementById('message-container');
            const resetForm = document.getElementById('reset-password-form');

            if (!token) {
                showMessage('Invalid or missing reset token.', 'error');
                return;
            }
            fetch('../auth/verify_reset_token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'token=' + encodeURIComponent(token)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resetForm.style.display = 'block';
                        showMessage('Enter your new password below.', 'success');
                    } else {
                        showMessage(data.message || 'Invalid or expired reset token.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred while verifying the reset token.', 'error');
                });
            resetForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                if (newPassword !== confirmPassword) {
                    showMessage('Passwords do not match.', 'error');
                    return;
                }

                if (newPassword.length < 6) {
                    showMessage('Password must be at least 6 characters long.', 'error');
                    return;
                }

                if (!newPassword.match(/^(?=.*[A-Za-z])(?=.*\d)/)) {
                    showMessage('Password must contain at least one letter and one number.', 'error');
                    return;
                }

                const resetBtn = document.getElementById('reset-btn');
                const originalText = resetBtn.textContent;
                resetBtn.textContent = 'Resetting...';
                resetBtn.disabled = true;

                fetch('../auth/process_password_reset.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'token=' + encodeURIComponent(token) + '&new_password=' + encodeURIComponent(newPassword)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('Password reset successful! You can now sign in with your new password.', 'success');
                            resetForm.style.display = 'none';
                        } else {
                            showMessage(data.message || 'Failed to reset password.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessage('An error occurred while resetting your password.', 'error');
                    })
                    .finally(() => {
                        resetBtn.textContent = originalText;
                        resetBtn.disabled = false;
                    });
            });

            function showMessage(message, type) {
                messageContainer.innerHTML = '<div class="message ' + type + '">' + message + '</div>';
            }
        });
    </script>
</body>

</html>