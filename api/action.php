<?php
include 'db.php';

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'react' && $id > 0) {
    $type = $_POST['type'] ?? ''; // 'like' or 'dislike'
    $column = ($type === 'like') ? 'likes' : 'dislikes';
    
    $sql = "UPDATE videos SET $column = $column + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
}
elseif ($action === 'view' && $id > 0) {
    $sql = "UPDATE videos SET views = views + 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    // ভিউয়ের জন্য কোনো রেসপন্স না পাঠালেও চলে
}
elseif ($action === 'get_comments' && $id > 0) {
    $sql = "SELECT userName, text, adminReply FROM comments WHERE video_id = ? ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = [];
    while($row = $result->fetch_assoc()){
        $comments[] = $row;
    }
    echo json_encode($comments);
    $stmt->close();
}
elseif ($action === 'post_comment' && $id > 0) {
    $userName = $_POST['userName'] ?? 'Unknown User';
    $text = $_POST['text'] ?? '';
    $timestamp = round(microtime(true) * 1000);

    if (!empty($text)) {
        $sql = "INSERT INTO comments (video_id, userName, text, timestamp) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $id, $userName, $text, $timestamp);
        if ($stmt->execute()) {
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false]);
        }
        $stmt->close();
    }
}

$conn->close();
?>