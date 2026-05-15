<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$uploadDir = 'uploads/';
$dataDir = 'data/';

require_once __DIR__ . '/vendor/autoload.php';
use PhpZip\ZipFile;

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

$action = $_GET['action'] ?? $_POST['action'] ?? 'upload';

switch ($action) {
    case 'upload':
        handleUpload();
        break;
    case 'list':
        getFileList();
        break;
    case 'delete':
        deleteFile();
        break;
}

function handleUpload() {
    if (empty($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => 'Файл не выбран']);
        return;
    }

    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];

    // Проверка расширения
    $allowedExtensions = ['doc', 'docx'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Разрешены только файлы .doc и .docx'
        ]);
        return;
    }

    $uniqueName = uniqid() . '_' . $fileName;
    $filePath = $GLOBALS['uploadDir'] . $uniqueName;

    if (move_uploaded_file($fileTmp, $filePath)) {
        $textContent = extractTextFromDocx($filePath);

        $textFilePath = $GLOBALS['dataDir'] . uniqid() . '.txt';
        file_put_contents($textFilePath, $textContent);

        $metadata = [
            'id' => uniqid(),
            'name' => $fileName,
            'original_name' => $uniqueName,
            'size' => $fileSize,
            'uploaded_at' => date('Y-m-d H:i:s'),
            'text_file' => $textFilePath
        ];

        $metadataFile = $GLOBALS['dataDir'] . $metadata['id'] . '.json';
        file_put_contents($metadataFile, json_encode($metadata));

        echo json_encode([
            'success' => true,
            'message' => 'Файл успешно загружен',
            'document' => $metadata
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка при загрузке файла'
        ]);
    }
}

function extractTextFromDocx($filePath) {
    try {
        $zip = new \PhpZip\ZipFile();

        $zip->openFile($filePath);

        $entries = $zip->getEntries();

        $content = '';
        foreach ($entries as $entry) {
            if ($entry->getName() === 'word/document.xml') {
                $content = $zip->getEntryContents($entry->getName());
                break;
            }
        }

        $zip->close();

        if ($content !== '') {
            $content = strip_tags($content);
            $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
            $content = preg_replace('/\s+/', ' ', $content);
            return trim($content);
        }
    } catch (\Exception $e) {
        error_log('Ошибка при работе с ZIP: ' . $e->getMessage());
    }

    return '';
}

function getFileList() {
    global $dataDir;

    $files = [];
    $jsonFiles = glob($dataDir . '*.json');

    foreach ($jsonFiles as $jsonFile) {
        $metadata = json_decode(file_get_contents($jsonFile), true);
        if ($metadata) {
            $files[] = [
                'id' => $metadata['id'],
                'name' => $metadata['name'],
                'size' => $metadata['size'],
                'uploaded_at' => $metadata['uploaded_at']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
}

function deleteFile() {
    global $uploadDir, $dataDir;

    $documentId = $_POST['id'] ?? '';

    if (empty($documentId)) {
        echo json_encode(['success' => false, 'message' => 'ID документа не указан']);
        return;
    }

    $metadataFile = $dataDir . $documentId . '.json';
    if (!file_exists($metadataFile)) {
        echo json_encode(['success' => false, 'message' => 'Документ не найден']);
        return;
    }

    $metadata = json_decode(file_get_contents($metadataFile), true);

    // Удаление файлов
    if (file_exists($uploadDir . $metadata['original_name'])) {
        unlink($uploadDir . $metadata['original_name']);
    }
    if (file_exists($metadata['text_file'])) {
        unlink($metadata['text_file']);
    }
    unlink($metadataFile);

    echo json_encode(['success' => true, 'message' => 'Документ удалён']);
}