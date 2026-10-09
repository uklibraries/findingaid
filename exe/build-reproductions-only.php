<?php

// Compile the renovation restriction list using the viewer ID in each finding aid URL.
try {
    if ($argc !== 3 || $argv[1] === $argv[2] || $argv[2] === __FILE__) {
        throw new RuntimeException('Usage: php build-reproductions-only.php input.csv output.php');
    }
    $handle = fopen($argv[1], 'r');
    if ($handle === false) {
        throw new RuntimeException('Cannot open input CSV');
    }
    $headers = fgetcsv($handle, 0, ',', '"', '');
    if (!is_array($headers) || !in_array('ead_location', $headers, true)) {
        throw new RuntimeException('CSV must contain an ead_location column');
    }
    $collections = [];
    $line = 1;
    while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
        ++$line;
        if ($row === [null]) {
            continue;
        }
        if (count($headers) !== count($row)) {
            throw new RuntimeException("Wrong column count on row $line");
        }
        $data = array_combine($headers, $row);
        $url = parse_url(trim($data['ead_location']));
        $query = [];
        parse_str($url['query'] ?? '', $query);
        $id = $query['id'] ?? null;
        if ($id === null && preg_match('~^/catalog/([a-z0-9]+)(?:/|$)~', $url['path'] ?? '', $matches)) {
            $id = $matches[1];
        }
        if (!is_string($id) || !preg_match('/^[a-z0-9]+$/', $id)) {
            throw new RuntimeException("Missing or invalid finding aid ID on row $line");
        }
        $collections[$id] = true;
    }
    fclose($handle);
    if (!$collections) {
        throw new RuntimeException('CSV contains no collections');
    }
    ksort($collections);
    $php = "<?php\n\n// Generated from microfilm.csv; do not edit.\nreturn " . var_export($collections, true) . ";\n";
    if (file_put_contents($argv[2], $php) === false) {
        throw new RuntimeException('Cannot write output PHP');
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
