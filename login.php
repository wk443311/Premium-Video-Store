<?php
// সেশন শুরু করা হচ্ছে
session_start();

// যদি অ্যাডমিন আগে থেকেই লগইন করা থাকেন, তাহলে তাকে অ্যাডমিন প্যানেলে পাঠিয়ে দিন
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: video_admin.php");
    exit();
}

$error_message = '';
$email_value = '';

// ফর্ম সাবমিট করা হলে এই অংশটুকু কাজ করবে
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ডাটাবেস কানেকশন ফাইল ইনক্লুড করা হচ্ছে
    include 'api/db.php'; 

    if ($conn) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $email_value = htmlspecialchars($email); // ইমেইল বক্সে আগের ইমেইল ধরে রাখার জন্য

        if (empty($email) || empty($password)) {
            $error_message = 'ইমেল এবং পাসওয়ার্ড দিতে হবে।';
        } else {
            // ডাটাবেসের admins টেবিল থেকে ইমেল চেক করা হচ্ছে
            $stmt = $conn->prepare("SELECT password FROM admins WHERE email = ?");
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                // যদি ইমেইলটি ডাটাবেসে পাওয়া যায়
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($db_password);
                    $stmt->fetch();

                    // পাসওয়ার্ড যাচাই করা হচ্ছে (আপনার সুবিধার জন্য হ্যাশ এবং সাধারণ টেক্সট দুটোই সাপোর্ট করবে)
                    if (password_verify($password, $db_password) || $password === $db_password) {
                        
                        // লগইন সফল হলে সেশন তৈরি করুন
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_email'] = $email;
                        
                        // অ্যাডমিন প্যানেলে রিডাইরেক্ট করুন
                        header("Location: video_admin.php");
                        exit();
                        
                    } else {
                        $error_message = 'পাসওয়ার্ড সঠিক নয়!'; // Invalid password
                    }
                } else {
                    $error_message = 'এই ইমেইলটি ডাটাবেসে পাওয়া যায়নি!'; // Invalid email
                }
                $stmt->close();
            } else {
                $error_message = 'ডাটাবেস কোয়েরি এরর!';
            }
        }
        $conn->close();
    } else {
        $error_message = 'ডাটাবেস কানেকশন ব্যর্থ হয়েছে। api/db.php চেক করুন।';
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - StreamX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff003c;
            --bg: #0d0d12;
            --card: #181820;
            --input-bg: #22222a;
            --text: #ffffff;
            --text-muted: #a0a0b0;
            --danger: #ff1744;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        body {
            background: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        .login-card {
            background: var(--card);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            border: 1px solid #2a2a35;
            text-align: center;
        }
        .login-card h2 {
            font-size: 24px;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .input-group label {
            display: block;
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 8px;
            font-weight: bold;
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-wrapper i {
            position: absolute;
            left: 15px;
            color: var(--text-muted);
            font-size: 16px;
        }
        .input-wrapper input {
            width: 100%;
            padding: 14px 14px 14px 45px;
            background: var(--input-bg);
            border: 1px solid #333;
            color: white;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }
        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 0, 60, 0.2);
        }
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: 0.3s;
            color: white;
            background: var(--primary);
        }
        .btn:hover {
            background: #d60032;
        }
        .error-msg {
            margin-top: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            color: white;
            background-color: var(--danger);
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-card">
        <h2><i class="fa-solid fa-shield-halved"></i> Admin Login</h2>
        
        <!-- ফর্মটি POST মেথডে একই পেজে সাবমিট হবে -->
        <form method="POST" action="">
            <div class="input-group">
                <label for="email">ইমেল</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="admin@example.com" value="<?php echo $email_value; ?>" required>
                </div>
            </div>
            
            <div class="input-group">
                <label for="password">পাসওয়ার্ড</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fa-solid fa-right-to-bracket"></i> লগইন করুন
            </button>
            
            <!-- যদি কোনো এরর থাকে তাহলে সেটি এখানে দেখাবে -->
            <?php if (!empty($error_message)): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>