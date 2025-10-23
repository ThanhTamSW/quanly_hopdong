<?php
/**
 * Script cap nhat Clipdrop API Key
 */

$envFile = __DIR__ . '/.env';

// IMPORTANT: Thay 'YOUR_CLIPDROP_API_KEY_HERE' bang API key thuc cua ban
// Get key: https://clipdrop.co/apis
$newClipdropKey = 'YOUR_CLIPDROP_API_KEY_HERE';

// Neu ban da co key, uncomment dong duoi va paste key vao:
// $newClipdropKey = '03ac237e...';

echo "Dang cap nhat Clipdrop API KEY...\n\n";

// Kiem tra xem da set API key chua
if ($newClipdropKey === 'YOUR_CLIPDROP_API_KEY_HERE') {
    echo "LOI: Ban chua cap nhat API key!\n\n";
    echo "HUONG DAN:\n";
    echo "   1. Mo file update_clipdrop_key.php\n";
    echo "   2. Tim dong: \$newClipdropKey = 'YOUR_CLIPDROP_API_KEY_HERE';\n";
    echo "   3. Comment dong do va uncomment dong duoi:\n";
    echo "      // \$newClipdropKey = 'YOUR_CLIPDROP_API_KEY_HERE';\n";
    echo "      \$newClipdropKey = '03ac237e289...';\n";
    echo "   4. Paste key cua ban vao\n";
    echo "   5. Luu file va chay lai: php update_clipdrop_key.php\n\n";
    echo "Lay key tai: https://clipdrop.co/apis\n\n";
    exit(1);
}

if (!file_exists($envFile)) {
    echo "Khong tim thay file .env\n";
    exit(1);
}

// Doc file .env
$content = file_get_contents($envFile);

// Cap nhat Clipdrop key
if (preg_match('/CLIPDROP_API_KEY=/', $content)) {
    $content = preg_replace(
        '/CLIPDROP_API_KEY=(.*)/',
        'CLIPDROP_API_KEY=' . $newClipdropKey,
        $content
    );
    echo "Da cap nhat Clipdrop API key\n";
} else {
    $content .= "\nCLIPDROP_API_KEY=" . $newClipdropKey . "\n";
    echo "Da them Clipdrop API key\n";
}

// Chuyen Image Provider ve clipdrop
if (preg_match('/IMAGE_PROVIDER=/', $content)) {
    $content = preg_replace(
        '/IMAGE_PROVIDER=(.*)/',
        'IMAGE_PROVIDER=clipdrop',
        $content
    );
    echo "Da chuyen IMAGE_PROVIDER ve 'clipdrop'\n";
} else {
    $content .= "\nIMAGE_PROVIDER=clipdrop\n";
    echo "Da them IMAGE_PROVIDER=clipdrop\n";
}

// Luu lai
file_put_contents($envFile, $content);

echo "\n" . str_repeat('=', 60) . "\n";
echo "CAP NHAT THANH CONG!\n";
echo str_repeat('=', 60) . "\n\n";

echo "CAU HINH MOI:\n";
echo "   - Image Provider: Clipdrop (AI Generated)\n";
echo "   - Clipdrop Key: " . substr($newClipdropKey, 0, 15) . "...\n";
echo "   - AI Provider: Groq\n\n";

echo "BAN CO THE:\n";
echo "   1. Truy cap blog_admin.php\n";
echo "   2. Tao bai viet AI voi anh\n";
echo "   3. Anh se duoc tao boi Clipdrop AI (chat luong cao)\n\n";

echo "HOAN TAT!\n";
?>

