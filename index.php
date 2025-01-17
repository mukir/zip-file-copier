<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle ZIP file download from another server
    $zipFileUrl = $_POST['zipFileUrl'] ?? '';

    if (!filter_var($zipFileUrl, FILTER_VALIDATE_URL)) {
        echo json_encode(['error' => 'Invalid URL.']);
        exit;
    }

    $savePath = __DIR__ . '/downloaded.zip'; // Path to save the ZIP file

    try {
        $ch = curl_init($zipFileUrl);
        $fp = fopen($savePath, 'w+');

        if ($ch === false || $fp === false) {
            throw new Exception('Failed to initialize download.');
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);
        fclose($fp);

        echo json_encode(['success' => 'File downloaded successfully.', 'path' => $savePath]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Copy ZIP File from Another Server</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Copy ZIP File from Another Server</h1>
    <form id="copyForm" class="mt-4">
        <div class="mb-3">
            <label for="zipFileUrl" class="form-label">Enter ZIP file URL:</label>
            <input type="url" id="zipFileUrl" name="zipFileUrl" class="form-control" placeholder="https://example.com/file.zip" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Copy File</button>
    </form>

    <div class="progress mt-4" style="height: 25px;">
        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
    </div>

    <div id="status" class="mt-3 text-center"></div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById('copyForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const zipFileUrl = document.getElementById('zipFileUrl').value;
        const progressBar = document.getElementById('progressBar');
        const statusDiv = document.getElementById('status');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Show progress
        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressBar.setAttribute('aria-valuenow', Math.round(percentComplete));
                progressBar.textContent = Math.round(percentComplete) + '%';
            }
        });

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            statusDiv.innerHTML = `<p class="text-success">${response.success}</p>`;
                            progressBar.textContent = '100%';
                            progressBar.style.width = '100%';
                            progressBar.classList.add('bg-success');
                        } else {
                            throw new Error(response.error);
                        }
                    } catch (error) {
                        statusDiv.innerHTML = `<p class="text-danger">${error.message}</p>`;
                        progressBar.classList.add('bg-danger');
                    }
                } else {
                    statusDiv.innerHTML = '<p class="text-danger">Failed to copy the file.</p>';
                    progressBar.classList.add('bg-danger');
                }
            }
        };

        xhr.send('zipFileUrl=' + encodeURIComponent(zipFileUrl));
    });
</script>
</body>
</html>
