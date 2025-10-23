<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/db.php';

// Lấy thông tin Coach đang đăng nhập
$coach_id = $_SESSION['user_id'];
$coach_name = $_SESSION['full_name'];

// Lấy tham số ngày
$date_param = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Include header
include 'includes/header.php';
?>

<style>
    /* Override body background cho trang này */
    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
        .container {
            padding-top: 50px;
        }
        .report-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        .report-content {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            white-space: pre-line;
            font-size: 14px;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            margin: 20px 0;
        }
        .copy-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        .copy-btn.copied {
            background: linear-gradient(45deg, #6c757d, #495057);
            transform: scale(0.95);
        }
        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        .title {
            color: #333;
            font-weight: 700;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="report-card">
            <h2 class="title text-center">📊 Báo cáo lịch dạy</h2>
            <div class="text-center mb-3">
                <strong>Coach:</strong> <?= htmlspecialchars($coach_name) ?> | 
                <strong>Ngày:</strong> <?= date('d/m/Y', strtotime($date_param)) ?>
            </div>
            
            <div id="loadingSection" class="loading">
                <div class="spinner"></div>
                <p>Đang tải báo cáo...</p>
            </div>
            
            <div id="reportSection" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">📋 Nội dung báo cáo</h5>
                    <button class="copy-btn" onclick="copyReport()" id="copyBtn">📋 Copy</button>
                </div>
                <div class="report-content" id="reportContent"></div>
            </div>
            
            <div id="errorSection" style="display: none;" class="alert alert-danger">
                <h5>❌ Lỗi tải báo cáo</h5>
                <p id="errorMessage"></p>
                <button class="btn btn-primary" onclick="loadReport()">🔄 Thử lại</button>
            </div>
        </div>
    </div>
    
    <!-- Toast notification -->
    <div class="toast-container">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">✅ Thành công</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                Đã sao chép nội dung báo cáo!
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let reportData = null;
        
        // Tải báo cáo khi trang load
        document.addEventListener('DOMContentLoaded', function() {
            loadReport();
        });
        
        function loadReport() {
            const loadingSection = document.getElementById('loadingSection');
            const reportSection = document.getElementById('reportSection');
            const errorSection = document.getElementById('errorSection');
            
            // Hiển thị loading
            loadingSection.style.display = 'block';
            reportSection.style.display = 'none';
            errorSection.style.display = 'none';
            
            // Gọi API
            fetch(`api/coach_report.php?date=<?= $date_param ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    reportData = data;
                    displayReport(data);
                    
                    // Ẩn loading, hiển thị báo cáo
                    loadingSection.style.display = 'none';
                    reportSection.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Ẩn loading, hiển thị lỗi
                    loadingSection.style.display = 'none';
                    errorSection.style.display = 'block';
                    document.getElementById('errorMessage').textContent = error.message;
                });
        }
        
        function displayReport(data) {
            const reportContent = document.getElementById('reportContent');
            reportContent.textContent = data.reportText;
        }
        
        function copyReport() {
            if (!reportData) {
                alert('Không có dữ liệu báo cáo để copy!');
                return;
            }
            
            const reportText = reportData.reportText;
            const copyBtn = document.getElementById('copyBtn');
            
            navigator.clipboard.writeText(reportText).then(function() {
                // Thay đổi button
                copyBtn.innerHTML = '✅ Đã copy!';
                copyBtn.classList.add('copied');
                
                // Hiển thị toast
                const toast = new bootstrap.Toast(document.getElementById('toast'));
                toast.show();
                
                setTimeout(function() {
                    copyBtn.innerHTML = '📋 Copy';
                    copyBtn.classList.remove('copied');
                }, 2000);
            }, function(err) {
                alert('Lỗi khi copy: ' + err);
            });
        }
    </script>

<?php include 'includes/footer.php'; ?>
