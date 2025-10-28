<?php
// index.php
require_once 'config.php';
Config::init();
?>
<!DOCTYPE html>
<html>
<head>
    <title>APK Crypt System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #0f0f0f; color: white; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #00ff00; margin-bottom: 10px; }
        .upload-area { border: 2px dashed #00ff00; padding: 40px; text-align: center; border-radius: 10px; margin-bottom: 20px; }
        .upload-area.dragover { background: #1a1a1a; }
        .btn { background: #00ff00; color: black; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn:disabled { background: #666; cursor: not-allowed; }
        .status { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .status.success { background: #1a3a1a; border: 1px solid #00ff00; }
        .status.error { background: #3a1a1a; border: 1px solid #ff0000; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 APK Crypt System</h1>
            <p>Upload APK files to crypt them with 2-hour expiry</p>
        </div>
        
        <div class="upload-area" id="uploadArea">
            <h3>📁 Drop APK File Here</h3>
            <p>Or click to select file</p>
            <input type="file" id="apkFile" accept=".apk" hidden>
            <button class="btn" onclick="document.getElementById('apkFile').click()">Select APK File</button>
        </div>
        
        <div id="status" class="status hidden"></div>
        
        <div id="userInfo" style="text-align: center; margin-top: 20px;">
            <p>👤 User ID: <span id="userId">Not set</span></p>
            <input type="number" id="setUserId" placeholder="Enter your User ID" style="padding: 8px; margin: 10px; border-radius: 5px; border: 1px solid #00ff00; background: #1a1a1a; color: white;">
            <button class="btn" onclick="setUserId()">Set User ID</button>
        </div>
    </div>

    <script>
        let userId = localStorage.getItem('apk_user_id') || '';
        document.getElementById('userId').textContent = userId || 'Not set';
        
        function setUserId() {
            const newId = document.getElementById('setUserId').value;
            if (newId) {
                userId = newId;
                localStorage.setItem('apk_user_id', userId);
                document.getElementById('userId').textContent = userId;
                document.getElementById('setUserId').value = '';
                showStatus('User ID set successfully!', 'success');
            }
        }
        
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('apkFile');
        
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFile(e.dataTransfer.files[0]);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                handleFile(e.target.files[0]);
            }
        });
        
        function handleFile(file) {
            if (!userId) {
                showStatus('Please set your User ID first!', 'error');
                return;
            }
            
            if (file.name.toLowerCase().endsWith('.apk')) {
                uploadFile(file);
            } else {
                showStatus('Please select an APK file!', 'error');
            }
        }
        
        function uploadFile(file) {
            const formData = new FormData();
            formData.append('apk_file', file);
            formData.append('user_id', userId);
            formData.append('username', 'Web User');
            
            showStatus('🔄 Processing APK...', 'success');
            
            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showStatus('✅ ' + data.message + '<br>⏰ Expires: ' + data.expiry, 'success');
                    if (data.download_url) {
                        const downloadBtn = document.createElement('a');
                        downloadBtn.href = data.download_url;
                        downloadBtn.className = 'btn';
                        downloadBtn.style.marginTop = '10px';
                        downloadBtn.textContent = 'Download Crypted APK';
                        downloadBtn.target = '_blank';
                        document.getElementById('status').appendChild(downloadBtn);
                    }
                } else {
                    showStatus('❌ ' + data.error, 'error');
                }
            })
            .catch(error => {
                showStatus('❌ Upload failed: ' + error, 'error');
            });
        }
        
        function showStatus(message, type) {
            const status = document.getElementById('status');
            status.innerHTML = message;
            status.className = 'status ' + type;
            status.classList.remove('hidden');
        }
    </script>
</body>
</html>
