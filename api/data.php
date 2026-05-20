<?php
include 'db.php';

$response = [
    'settings' => [],
    'categories' => [],
    'videos' => []
];

// সেটিংস আনা হচ্ছে
$sql = "SELECT * FROM settings";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $response['settings'][$row['setting_key']] = $row['setting_value'];
}

// ক্যাটাগরি আনা হচ্ছে
$sql = "SELECT name FROM categories ORDER BY name ASC";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    $response['categories'][] = $row;
}

// ভিডিও আনা হচ্ছে
$sql = "SELECT * FROM videos ORDER BY id DESC";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()) {
    // ভিডিওর কমেন্ট সংখ্যা যোগ করা হচ্ছে
    $video_id = $row['id'];
    $comment_count_sql = "SELECT COUNT(id) as count FROM comments WHERE video_id = $video_id";
    $comment_result = $conn->query($comment_count_sql);
    $comment_count = $comment_result->fetch_assoc()['count'];
    $row['commentCount'] = $comment_count;

    $response['videos'][] = $row;
}

echo json_encode($response);

$conn->close();
?>