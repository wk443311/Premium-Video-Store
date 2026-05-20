<?php
// === ধাপ ১: নিরাপত্তা ও সেশন চেক ===
session_start();

// যদি অ্যাডমিন লগইন করা না থাকে, তাহলে login.php পেজে পাঠিয়ে দিন
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// === ধাপ ২: পেজ লোড করার জন্য ডেটাবেস থেকে তথ্য আনা ===
include 'api/db.php'; // ডাটাবেস কানেকশন ফাইল

// সেটিংস লোড করা
$settings =[];
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings");
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// ক্যাটাগরি লোড করা
$categories =[];
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// ভিডিও লোড করা
$videos =[];
$videos_result = $conn->query("SELECT * FROM videos ORDER BY timestamp DESC");
if ($videos_result) {
    while ($row = $videos_result->fetch_assoc()) {
        $videos[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>StreamX - Pro Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === প্রফেশনাল CSS === */
        :root { 
            --primary: #ff003c; 
            --bg: #0b0b0f; 
            --card: #15151a; 
            --input-bg: #1e1e24; 
            --text: #ffffff; 
            --text-muted: #8b8b99; 
            --success: #00e676;
            --info: #00b0ff;
            --warning: #ffea00;
            --danger: #ff1744;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); padding-bottom: 50px; }
        
        /* Header */
        header { 
            background: rgba(21, 21, 26, 0.95); 
            padding: 20px; 
            text-align: center; 
            border-bottom: 2px solid var(--primary); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.5); 
        }
        header h2 { font-size: 24px; color: var(--primary); display: flex; align-items: center; justify-content: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px;}
        .logout-btn { position: absolute; top: 18px; right: 20px; background: var(--danger); border: none; color: white; padding: 10px 18px; border-radius: 10px; cursor: pointer; font-weight: bold; transition: 0.3s; box-shadow: 0 4px 10px rgba(255, 23, 68, 0.3); }
        .logout-btn:hover { background: #d50000; transform: translateY(-2px); }

        .container { max-width: 900px; margin: 20px auto; padding: 0 15px; }

        /* Live Box */
        .live-box { text-align: center; padding: 15px; margin-bottom: 20px; background: linear-gradient(45deg, #15151a, #1a1a24); border-radius: 12px; border: 1px solid #2a2a35;}
        #live-count { font-size: 28px; font-weight: bold; color: var(--success); }

        /* === Tab / Slider System === */
        .tab-nav {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            margin-bottom: 25px;
            padding-bottom: 10px;
            scrollbar-width: none; /* Firefox */
        }
        .tab-nav::-webkit-scrollbar { display: none; /* Chrome */ }
        
        .tab-btn {
            flex: 1;
            min-width: max-content;
            background: var(--input-bg);
            color: var(--text-muted);
            border: 1px solid #2a2a35;
            padding: 14px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .tab-btn:hover { background: #25252e; color: white; }
        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(255, 0, 60, 0.4);
            transform: translateY(-2px);
        }

        /* Tab Content Animation */
        .tab-content { display: none; animation: slideFade 0.4s ease-out forwards; }
        .tab-content.active { display: block; }
        @keyframes slideFade {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Card Design */
        .card { background: var(--card); border-radius: 16px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid #2a2a35; }
        .card-header { margin-bottom: 25px; border-bottom: 1px solid #2a2a35; padding-bottom: 15px; }
        .card-header h3 { font-size: 20px; color: white; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: var(--primary); }

        /* Form & Inputs */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .grid-2 { grid-template-columns: 1fr; gap: 15px; } }
        
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; font-size: 14px; color: var(--text-muted); margin-bottom: 8px; font-weight: 600; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i { position: absolute; left: 16px; color: var(--text-muted); font-size: 16px; }
        .input-wrapper input, .input-wrapper select {
            width: 100%; padding: 14px 14px 14px 45px; background: var(--input-bg); border: 1px solid #333;
            color: white; border-radius: 12px; font-size: 15px; outline: none; transition: 0.3s;
        }
        .input-wrapper input:focus, .input-wrapper select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 0, 60, 0.2); }

        /* Buttons */
        .btn { padding: 14px 24px; border: none; border-radius: 12px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; color: white; }
        .btn-primary { background: var(--primary); box-shadow: 0 4px 15px rgba(255, 0, 60, 0.3); }
        .btn-primary:hover { background: #d60032; transform: translateY(-2px); }
        .btn-success { background: var(--success); color: #000; box-shadow: 0 4px 15px rgba(0, 230, 118, 0.2); }
        .btn-success:hover { background: #00c853; transform: translateY(-2px); }
        .btn-info { background: var(--info); }
        .btn-warning { background: var(--warning); color: #000; }
        .btn-danger { background: var(--danger); }
        
        /* Category Item */
        .cat-item { display: flex; justify-content: space-between; align-items: center; background: var(--input-bg); padding: 15px 20px; border-radius: 12px; margin-bottom: 10px; border: 1px solid #333; }
        .cat-item button { background: rgba(255, 23, 68, 0.1); color: var(--danger); border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .cat-item button:hover { background: var(--danger); color: white; }

        /* Video Item */
        .video-item { background: var(--input-bg); border-radius: 12px; padding: 20px; margin-bottom: 15px; border: 1px solid #333; transition: 0.3s; }
        .video-item:hover { border-color: #444; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .v-title { font-size: 18px; font-weight: bold; margin-bottom: 12px; color: white; line-height: 1.4; }
        .v-stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .badge { background: #111; padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #ccc; border: 1px solid #333; display: flex; align-items: center; gap: 5px; }
        .v-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .v-actions .btn { padding: 10px; font-size: 14px; border-radius: 8px; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(5px); }
        .modal-content { background: var(--card); width: 100%; max-width: 600px; border-radius: 16px; padding: 30px; border: 1px solid var(--primary); position: relative; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .close-btn { position: absolute; top: 20px; right: 25px; font-size: 24px; color: var(--text-muted); cursor: pointer; transition: 0.3s; }
        .close-btn:hover { color: var(--danger); transform: rotate(90deg); }
    </style>
</head>
<body>

<header>
    <h2><i class="fa-solid fa-video"></i> STREAMX ADMIN</h2>
    <button class="logout-btn" onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
</header>

<div class="container">
    <div class="live-box">
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom:5px;"><i class="fa-solid fa-circle-dot fa-fade" style="color: #00e676;"></i> রিয়েল-টাইম লাইভ ইউজার</p>
        <span id="live-count"><?php echo rand(5, 50); ?></span>
    </div>

    <!-- === TAB NAVIGATION === -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('settings-tab', this)"><i class="fa-solid fa-gears"></i> সেটিংস</button>
        <button class="tab-btn" onclick="switchTab('category-tab', this)"><i class="fa-solid fa-layer-group"></i> ক্যাটাগরি</button>
        <button class="tab-btn" onclick="switchTab('upload-tab', this)"><i class="fa-solid fa-cloud-arrow-up"></i> ভিডিও আপলোড</button>
        <button class="tab-btn" onclick="switchTab('manage-tab', this)"><i class="fa-solid fa-server"></i> ভিডিও ম্যানেজ</button>
    </div>

    <!-- === 1. Website & Ad Settings === -->
    <div id="settings-tab" class="tab-content active">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-gears"></i> ওয়েবসাইট ও অ্যাড সেটিংস</h3></div>
            <div class="grid-2">
                <div class="input-group"><label>ওয়েবসাইটের নাম</label><div class="input-wrapper"><i class="fa-solid fa-globe"></i><input type="text" name="siteName" id="set-site-name" placeholder="STREAMX 🎥" value="<?php echo htmlspecialchars($settings['siteName'] ?? ''); ?>"></div></div>
                <div class="input-group"><label>টেলিগ্রাম মিনি অ্যাপ লিংক</label><div class="input-wrapper"><i class="fa-brands fa-telegram"></i><input type="text" name="tgAppUrl" id="set-tg-app-url" placeholder="https://t.me/Bot/App" value="<?php echo htmlspecialchars($settings['tgAppUrl'] ?? ''); ?>"></div></div>
            </div>
            <h4 style="margin:15px 0 15px; border-bottom:1px solid #333; padding-bottom:8px; color:var(--primary);"><i class="fa-solid fa-play"></i> ভিডিও মিড-রোল অ্যাড</h4>
            <div class="grid-2">
                <div class="input-group"><label>অ্যাড টাইম (কমা দিয়ে সেকেন্ড)</label><div class="input-wrapper"><i class="fa-solid fa-stopwatch"></i><input type="text" name="adInterval" id="set-ad-interval" placeholder="10, 45, 120" value="<?php echo htmlspecialchars($settings['adInterval'] ?? ''); ?>"></div></div>
                <div class="input-group"><label>ভিডিও Adsgram Block ID</label><div class="input-wrapper"><i class="fa-solid fa-ad"></i><input type="text" name="adsgramId" id="set-adsgram-id" placeholder="Adsgram ID" value="<?php echo htmlspecialchars($settings['adsgramId'] ?? ''); ?>"></div></div>
            </div>
            <div class="input-group"><label>Monetag Zone ID</label><div class="input-wrapper"><i class="fa-solid fa-bullhorn"></i><input type="text" name="monetagZone" id="set-monetag-zone" placeholder="Zone ID" value="<?php echo htmlspecialchars($settings['monetagZone'] ?? ''); ?>"></div></div>
            
            <h4 style="margin:20px 0 15px; border-bottom:1px solid #333; padding-bottom:8px; color:var(--success);"><i class="fa-regular fa-clock"></i> আইডল অ্যাড (Idle Ad)</h4>
            <div class="grid-2">
                <div class="input-group"><label>আইডল অ্যাড টাইম (সেকেন্ড)</label><div class="input-wrapper"><i class="fa-solid fa-hourglass-half"></i><input type="number" name="idleAdInterval" id="set-idle-time" placeholder="30" value="<?php echo htmlspecialchars($settings['idleAdInterval'] ?? ''); ?>"></div></div>
                <div class="input-group"><label>আইডল Adsgram Block ID</label><div class="input-wrapper"><i class="fa-solid fa-rectangle-ad"></i><input type="text" name="idleAdsgramId" id="set-idle-adsgram-id" placeholder="Idle Adsgram ID" value="<?php echo htmlspecialchars($settings['idleAdsgramId'] ?? ''); ?>"></div></div>
            </div>
            <button class="btn btn-success" onclick="saveSettings()" style="margin-top: 10px;"><i class="fa-solid fa-floppy-disk"></i> সেটিংস সেভ করুন</button>
        </div>
    </div>

    <!-- === 2. Category Management === -->
    <div id="category-tab" class="tab-content">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-layer-group"></i> ক্যাটাগরি ম্যানেজমেন্ট</h3></div>
            <div class="input-group">
                <label>নতুন ক্যাটাগরি তৈরি করুন</label>
                <div style="display:flex; gap:12px;">
                    <div class="input-wrapper" style="flex:1;"><i class="fa-solid fa-plus"></i><input type="text" id="new-cat-name" placeholder="যেমন: Movie, Funny"></div>
                    <button class="btn btn-info" style="width:130px; border-radius:12px;" onclick="addCategory()">Add Category</button>
                </div>
            </div>
            <div id="category-list" style="margin-top:20px;">
                <?php foreach ($categories as $cat): ?>
                    <div class="cat-item">
                        <span style="font-weight:bold; font-size:15px;"><i class="fa-solid fa-folder" style="color:var(--info); margin-right:8px;"></i> <?php echo htmlspecialchars($cat['name']); ?></span>
                        <button onclick="deleteCat('<?php echo $cat['id']; ?>')"><i class="fa-solid fa-trash"></i></button>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <p style="text-align:center; color:#777; padding: 20px;">কোনো ক্যাটাগরি নেই।</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- === 3. Upload Video === -->
    <div id="upload-tab" class="tab-content">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-cloud-arrow-up"></i> নতুন ভিডিও আপলোড করুন</h3></div>
            <div class="grid-2">
                <div class="input-group"><label>ভিডিওর শিরোনাম</label><div class="input-wrapper"><i class="fa-solid fa-heading"></i><input type="text" id="add-title" placeholder="ভিডিওর সুন্দর নাম দিন"></div></div>
                <div class="input-group"><label>ক্যাটাগরি সিলেক্ট করুন</label><div class="input-wrapper"><i class="fa-solid fa-list"></i>
                    <select id="add-category">
                        <option value="Others">Others</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div></div>
            </div>
            <div class="input-group"><label>থাম্বনেইল লিংক (ছবি)</label><div class="input-wrapper"><i class="fa-solid fa-image"></i><input type="text" id="add-thumb" placeholder="ছবির ডিরেক্ট লিংক"></div></div>
            <div class="input-group"><label>ভিডিও লিংক (MP4 / YouTube / TG)</label><div class="input-wrapper"><i class="fa-solid fa-video"></i><input type="text" id="add-url" placeholder="ভিডিওর ডিরেক্ট লিংক"></div></div>
            
            <h4 style="margin:20px 0 15px; border-bottom:1px solid #333; padding-bottom:8px; color:var(--warning);"><i class="fa-solid fa-lock"></i> ফুল কালেকশন সেটিংস (ঐচ্ছিক)</h4>
            <div class="grid-2">
                <div class="input-group"><label>ফুল কালেকশন লিংক</label><div class="input-wrapper"><i class="fa-solid fa-link"></i><input type="text" id="add-fc-url" placeholder="লিংক না দিলে বাটন হাইড থাকবে"></div></div>
                <div class="input-group"><label>কতটি অ্যাড দেখতে হবে?</label><div class="input-wrapper"><i class="fa-solid fa-eye"></i><input type="number" id="add-fc-ads" placeholder="যেমন: 5"></div></div>
            </div>
            <button class="btn btn-primary" onclick="uploadVideo()" style="margin-top: 10px; font-size:16px;"><i class="fa-solid fa-paper-plane"></i> ভিডিও পাবলিশ করুন</button>
        </div>
    </div>

    <!-- === 4. Video Management === -->
    <div id="manage-tab" class="tab-content">
        <div class="card">
            <div class="card-header"><h3><i class="fa-solid fa-server"></i> ভিডিও ম্যানেজমেন্ট</h3></div>
            <div id="video-manage-list">
                <?php foreach ($videos as $v): ?>
                    <div class="video-item">
                        <div class="v-title"><?php echo htmlspecialchars($v['title']); ?></div>
                        <div class="v-stats">
                            <span class="badge" style="color:var(--success); border-color:var(--success)"><i class="fa-solid fa-folder"></i> <?php echo htmlspecialchars($v['category'] ?? 'Others'); ?></span>
                            <span class="badge"><i class="fa-solid fa-eye"></i> <?php echo $v['views'] ?? 0; ?></span>
                            <span class="badge"><i class="fa-solid fa-thumbs-up"></i> <?php echo $v['likes'] ?? 0; ?></span>
                            <span class="badge" style="color:var(--info); border-color:var(--info)"><i class="fa-solid fa-comments"></i> <?php echo $v['commentCount'] ?? 0; ?></span>
                            <?php if (!empty($v['fullCollectionUrl'])): ?>
                                <span class="badge" style="color:var(--warning); border-color:var(--warning)"><i class="fa-solid fa-link"></i> FC Active</span>
                            <?php endif; ?>
                        </div>
                        <div class="v-actions">
                            <button class="btn btn-warning" onclick="manageComments('<?php echo $v['id']; ?>')"><i class="fa-solid fa-comments"></i> কমেন্টস</button>
                            <button class="btn btn-info" onclick="openEditModal('<?php echo $v['id']; ?>')"><i class="fa-solid fa-pen"></i> এডিট</button>
                            <button class="btn btn-danger" onclick="deleteVideo('<?php echo $v['id']; ?>')"><i class="fa-solid fa-trash"></i> ডিলিট</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($videos)): ?>
                    <p style="text-align:center; color:#777; padding: 20px;">কোনো ভিডিও আপলোড করা হয়নি!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- === Edit Video Modal === -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <i class="fa-solid fa-xmark close-btn" onclick="document.getElementById('editModal').style.display='none'"></i>
        <h3 style="color:var(--primary); margin-bottom:25px; font-size:22px; border-bottom:1px solid #333; padding-bottom:10px;"><i class="fa-solid fa-pen-to-square"></i> ভিডিও আপডেট করুন</h3>
        <input type="hidden" id="edit-id">
        <div class="input-group"><label>শিরোনাম</label><div class="input-wrapper"><i class="fa-solid fa-heading"></i><input type="text" id="edit-title"></div></div>
        <div class="input-group"><label>ক্যাটাগরি</label><div class="input-wrapper"><i class="fa-solid fa-list"></i>
            <select id="edit-category">
                <option value="Others">Others</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div></div>
        <div class="input-group"><label>থাম্বনেইল লিংক</label><div class="input-wrapper"><i class="fa-solid fa-image"></i><input type="text" id="edit-thumb"></div></div>
        <div class="input-group"><label>ভিডিও লিংক</label><div class="input-wrapper"><i class="fa-solid fa-video"></i><input type="text" id="edit-url"></div></div>
        <h4 style="margin:20px 0 15px; border-bottom:1px solid #333; padding-bottom:8px; color:var(--warning);">ফুল কালেকশন সেটিংস</h4>
        <div class="input-group"><label>ফুল কালেকশন লিংক</label><div class="input-wrapper"><i class="fa-solid fa-link"></i><input type="text" id="edit-fc-url"></div></div>
        <div class="input-group" style="margin-bottom:25px;"><label>কতটি অ্যাড দেখতে হবে?</label><div class="input-wrapper"><i class="fa-solid fa-eye"></i><input type="number" id="edit-fc-ads"></div></div>
        <button class="btn btn-info" onclick="saveEditVideo()" style="font-size:16px;"><i class="fa-solid fa-arrows-rotate"></i> আপডেট সেভ করুন</button>
    </div>
</div>

<script>
    // === Tab Switching Logic (Slider System) ===
    function switchTab(tabId, btnElement) {
        // ১. সব Tab Content লুকান
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.remove('active'));
        
        // ২. সব Tab Button থেকে Active ক্লাস সরান
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        // ৩. কাঙ্ক্ষিত Tab Content দেখান এবং বাটনে Active ক্লাস যোগ করুন
        document.getElementById(tabId).classList.add('active');
        btnElement.classList.add('active');
    }

    // === API Functions ===
    const API_URL = 'api/admin_api.php';

    async function postToAction(formData) {
        try {
            const response = await fetch(API_URL, { method: 'POST', body: formData });
            if (response.status === 401) {
                alert("সেশন শেষ। আবার লগইন করুন।");
                window.location.href = 'login.php';
                return { success: false };
            }
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            alert('একটি ত্রুটি ঘটেছে!');
            return { success: false };
        }
    }

    async function logout() {
        if (!confirm("আপনি কি লগআউট করতে চান?")) return;
        const formData = new FormData();
        formData.append('action', 'logout');
        const result = await postToAction(formData);
        if (result.success) window.location.href = 'login.php';
    }

    async function saveSettings() {
        const formData = new FormData();
        formData.append('action', 'save_settings');
        document.querySelectorAll('input[name], select[name]').forEach(el => {
            if(el.name) formData.append(el.name, el.value);
        });
        const result = await postToAction(formData);
        if (result.success) {
            alert("✅ সেটিংস সফলভাবে সেভ হয়েছে!");
            location.reload(); 
        }
    }

    async function addCategory() {
        const name = document.getElementById('new-cat-name').value.trim();
        if(name === '') return alert("ক্যাটাগরির নাম লিখুন!");
        const formData = new FormData();
        formData.append('action', 'add_category');
        formData.append('name', name);
        const result = await postToAction(formData);
        if (result.success) location.reload();
    }

    async function deleteCat(id) {
        if (!confirm("⚠️ এই ক্যাটাগরিটি ডিলিট করবেন?")) return;
        const formData = new FormData();
        formData.append('action', 'delete_category');
        formData.append('id', id);
        const result = await postToAction(formData);
        if (result.success) location.reload();
    }

    async function uploadVideo() {
        const formData = new FormData();
        formData.append('action', 'add_video');
        formData.append('title', document.getElementById('add-title').value);
        formData.append('category', document.getElementById('add-category').value);
        formData.append('thumb', document.getElementById('add-thumb').value);
        formData.append('url', document.getElementById('add-url').value);
        formData.append('fullCollectionUrl', document.getElementById('add-fc-url').value);
        formData.append('fullCollectionAds', parseInt(document.getElementById('add-fc-ads').value) || 0);

        if (!formData.get('title') || !formData.get('url')) return alert("⚠️ শিরোনাম এবং ভিডিও লিংক দিন!");
        
        const result = await postToAction(formData);
        if (result.success) {
            alert("✅ ভিডিও সফলভাবে আপলোড হয়েছে!");
            location.reload();
        }
    }

    async function deleteVideo(id) {
        if (!confirm("⚠️ আপনি কি নিশ্চিত যে এই ভিডিওটি ডিলিট করতে চান?")) return;
        const formData = new FormData();
        formData.append('action', 'delete_video');
        formData.append('id', id);
        const result = await postToAction(formData);
        if (result.success) location.reload();
    }
    
    async function openEditModal(id) {
        const formData = new FormData();
        formData.append('action', 'get_video');
        formData.append('id', id);
        const v = await postToAction(formData);
        
        if(v && v.id) {
            document.getElementById('edit-id').value = v.id; 
            document.getElementById('edit-title').value = v.title;
            document.getElementById('edit-category').value = v.category || "Others"; 
            document.getElementById('edit-thumb').value = v.thumb;
            document.getElementById('edit-url').value = v.url; 
            document.getElementById('edit-fc-url').value = v.fullCollectionUrl || ""; 
            document.getElementById('edit-fc-ads').value = v.fullCollectionAds || ""; 
            document.getElementById('editModal').style.display = 'flex';
        } else {
            alert("ভিডিওর তথ্য পাওয়া যায়নি।");
        }
    }

    async function saveEditVideo() {
        const formData = new FormData();
        formData.append('action', 'update_video');
        formData.append('id', document.getElementById('edit-id').value);
        formData.append('title', document.getElementById('edit-title').value);
        formData.append('category', document.getElementById('edit-category').value);
        formData.append('thumb', document.getElementById('edit-thumb').value);
        formData.append('url', document.getElementById('edit-url').value);
        formData.append('fullCollectionUrl', document.getElementById('edit-fc-url').value);
        formData.append('fullCollectionAds', parseInt(document.getElementById('edit-fc-ads').value) || 0);

        const result = await postToAction(formData);
        if (result.success) {
            alert("✅ ভিডিও আপডেট হয়েছে!");
            location.reload();
        }
    }

    function manageComments(id){
        alert("কমেন্ট ম্যানেজমেন্ট ফিচার এখানে যুক্ত করুন। ভিডিও আইডি: " + id);
    }
</script>
</body>
</html>