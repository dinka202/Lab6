document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const progress = document.getElementById('progress');
    const progressPercent = document.getElementById('progressPercent');
    const progressFill = document.getElementById('progressFill');
    const result = document.getElementById('result');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        uploadArea.classList.add('highlight');
    }

    function unhighlight() {
        uploadArea.classList.remove('highlight');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length === 0) return;

        const file = files[0];
        uploadFile(file);
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        progress.style.display = 'block';
        result.style.display = 'none';

        const xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percent = (e.loaded / e.total) * 100;
                progressPercent.textContent = Math.round(percent) + '%';
                progressFill.style.width = percent + '%';
            }
        });

        xhr.addEventListener('load', function() {
            progress.style.display = 'none';

            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    showSuccess(response.message, response.document);
                } else {
                    showError(response.message);
                }
            } catch (error) {
                showError('Ошибка обработки ответа сервера');
            }
        });

        xhr.addEventListener('error', function() {
            progress.style.display = 'none';
            showError('Ошибка загрузки файла');
        });

        xhr.open('POST', 'upload.php');
        xhr.send(formData);
    }

    function showSuccess(message, document) {
        result.innerHTML = `
            <div class="alert success">
                <h3>✅ ${message}</h3>
                <p><strong>Загруженный документ:</strong> ${document.name}</p>
                <p><strong>Размер:</strong> ${formatFileSize(document.size)}</p>
                <p><strong>Дата загрузки:</strong> ${document.uploaded_at}</p>
                <div class="actions">
                    <a href="list.html" class="btn">Перейти к списку документов</a>
                    <button onclick="checkDocument('${document.id}')" class="btn btn-secondary">
                        Проверить на уникальность
                    </button>
                </div>
            </div>
        `;
        result.style.display = 'block';
    }

    function checkDocument(documentId) {
        window.location.href = `check.php?id=${documentId}`;
    }

    function showError(message) {
        result.innerHTML = `
            <div class="alert error">
                <h3>❌ Ошибка</h3>
                <p>${message}</p>
                <button onclick="resetForm()" class="btn btn-secondary">Попробовать снова</button>
            </div>
        `;
        result.style.display = 'block';
    }

    function resetForm() {
        result.style.display = 'none';
        fileInput.value = '';
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});