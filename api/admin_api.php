<?php
// PHP এরর লগিং চালু করা হচ্ছে। কোনো সমস্যা হলে এই ফাইলের পাশে error.log ফাইলে তা লেখা থাকবে।
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');
error_reporting(E_ALL);
ini_set('display_errors', 0); // ব্যবহারকারীকে এরর দেখাবে না

// API রেসপন্সের জন্য হেডার সেট করা
header('Content-Type: application/json');

// ডাটাবেস সংযোগ ফাইল এবং সেশন শুরু
include 'db.php';
session_start();

// শুধুমাত্র POST রিকোয়েস্ট গ্রহণ করা হবে
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die(json_encode(['success' => false, 'error' => 'POST method required.']));
}

$action = $_POST['action'] ?? '';

// --- প্রমাণীকরণ (Authentication) সম্পর্কিত অ্যাকশন ---

// লগইন অ্যাকশন
if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        die(json_encode(['success' => false, 'error' => 'Email and password are required.']));
    }
    
    $stmt = $conn->prepare("SELECT password FROM admins WHERE email = ?");
    if ($stmt === false) {
        error_log("Login prepare failed: " . $conn->error);
        die(json_encode(['success' => false, 'error' => 'Database error.']));
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result(); // ফলাফল বাফার করুন

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password); // ফলাফলকে একটি ভ্যারিয়েবলে বাইন্ড করুন
        $stmt->fetch(); // ভ্যারিয়েবলে ডেটা আনুন

        // ডাটাবেসের হ্যাশের সাথে ব্যবহারকারীর দেওয়া পাসওয়ার্ড যাচাই করুন
        if (password_verify($password, $hashed_password)) {
            // সফল হলে সেশন তৈরি করুন
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
            echo json_encode(['success' => true]);
        } else {
            // পাসওয়ার্ড না মিললে
            echo json_encode(['success' => false, 'error' => 'Invalid credentials.']);
        }
    } else {
        // ইমেল খুঁজে না পাওয়া গেলে
        echo json_encode(['success' => false, 'error' => 'Invalid credentials.']);
    }

    $stmt->close();
    $conn->close();
    exit();
}

// লগআউট অ্যাকশন
if ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
    exit();
}

// প্রমাণীকরণের স্ট্যাটাস চেক
if ($action === 'check_auth') {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        echo json_encode(['logged_in' => true]);
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['logged_in' => false]);
    }
    exit();
}

// --- নিরাপত্তা বেষ্টনী ---
// নিচের সব অ্যাকশনের জন্য লগইন করা আবশ্যক
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401); // Unauthorized
    die(json_encode(['success' => false, 'error' => 'Authentication required. Please login.']));
}

// --- ডেটা ম্যানেজমেন্ট অ্যাকশন (শুধুমাত্র লগইন করা অ্যাডমিনদের জন্য) ---

switch ($action) {
    case 'save_settings':
        foreach ($_POST as $key => $value) {
            if ($key !== 'action') {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
            }
        }
        echo json_encode(['success' => true]);
        break;

    case 'add_category':
        $name = $_POST['name'] ?? '';
        if (!empty($name)) {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Category name is required.']);
        }
        break;

    case 'delete_category':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid category ID.']);
        }
        break;

    case 'add_video':
        $timestamp = (string)round(microtime(true) * 1000);
        $fullCollectionAds = (int)($_POST['fullCollectionAds'] ?? 0);
        $stmt = $conn->prepare("INSERT INTO videos (title, category, thumb, url, fullCollectionUrl, fullCollectionAds, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $_POST['title'], $_POST['category'], $_POST['thumb'], $_POST['url'], $_POST['fullCollectionUrl'], $fullCollectionAds, $timestamp);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'get_video':
        $id_param = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("SELECT id, title, category, thumb, url, fullCollectionUrl, fullCollectionAds FROM videos WHERE id = ?");
        $stmt->bind_param("i", $id_param);
        $stmt->execute();
        $stmt->store_result();
        $video_data = null;
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $title, $category, $thumb, $url, $fullCollectionUrl, $fullCollectionAds);
            $stmt->fetch();
            $video_data = [
                'id' => $id,
                'title' => $title,
                'category' => $category,
                'thumb' => $thumb,
                'url' => $url,
                'fullCollectionUrl' => $fullCollectionUrl,
                'fullCollectionAds' => $fullCollectionAds
            ];
        }
        echo json_encode($video_data);
        break;

    case 'update_video':
        $id = (int)($_POST['id'] ?? 0);
        $fullCollectionAds = (int)($_POST['fullCollectionAds'] ?? 0);
        $stmt = $conn->prepare("UPDATE videos SET title=?, category=?, thumb=?, url=?, fullCollectionUrl=?, fullCollectionAds=? WHERE id=?");
        $stmt->bind_param("sssssii", $_POST['title'], $_POST['category'], $_POST['thumb'], $_POST['url'], $_POST['fullCollectionUrl'], $fullCollectionAds, $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;

    case 'delete_video':
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
        }
        break;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Invalid action provided.']);
        break;
}

$conn->close();
?>