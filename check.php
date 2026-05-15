<?php
$documentId = $_GET['id'] ?? '';
$dataDir = 'data/';

if (empty($documentId)) {
    die('ID документа не указан');
}

$metadataFile = $dataDir . $documentId . '.json';
if (!file_exists($metadataFile)) {
    die('Документ не найден');
}

$metadata = json_decode(file_get_contents($metadataFile), true);
$currentText = file_get_contents($metadata['text_file']);

$allFiles = glob($dataDir . '*.json');
$results = [];

foreach ($allFiles as $file) {
    $otherMetadata = json_decode(file_get_contents($file), true);

    if ($otherMetadata['id'] === $documentId) continue;

    $otherText = file_get_contents($otherMetadata['text_file']);
    $similarity = calculateSimilarity($currentText, $otherText);

    $results[] = [
        'name' => $otherMetadata['name'],
        'similarity' => round($similarity * 100, 2) . '%'
    ];
}

function calculateSimilarity($text1, $text2) {
    $words1 = array_unique(preg_split('/\s+/', strtolower($text1)));
    $words2 = array_unique(preg_split('/\s+/', strtolower($text2)));

    $intersection = array_intersect($words1, $words2);
    $union = array_unique(array_merge($words1, $words2));

    if (count($union) === 0) return 0;

    return count($intersection) / count($union);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты проверки</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Результаты проверки на уникальность</h1>
    <p>Проверяемый документ: <strong><?php echo htmlspecialchars($metadata['name']); ?></strong></p>
    <div class="results">
        <?php if (count($results) > 0): ?>
            <table>
                <thead>
                <tr>
                    <th>Документ</th>
                    <th>Схожесть</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['name']); ?></td>
                        <td><?php echo $result['similarity']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Других документов для сравнения не найдено</p>
        <?php endif; ?>
    </div>
    <div class="actions">
        <a href="list.html" class="btn">Вернуться к списку</a>
    </div>
</div>
</body>
</html>
