<?php
$dirs = glob('app/Domains/*/Models');
foreach ($dirs as $dir) {
    $files = glob($dir . '/*.php');
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if (preg_match('/protected \$table\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $tableMatches)) {
            $table = $tableMatches[1];
        } else {
            // Default table name convention
            $basename = basename($file, '.php');
            $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $basename)) . 's';
            if (substr($table, -2) === 'ys') $table = substr($table, 0, -2) . 'ies';
        }
        
        $fillable = [];
        if (preg_match('/protected \$fillable\s*=\s*\[(.*?)\];/s', $content, $fillableMatches)) {
            $lines = explode(',', $fillableMatches[1]);
            foreach ($lines as $line) {
                if (preg_match('/[\'"]([^\'"]+)[\'"]/', $line, $field)) {
                    $fillable[] = $field[1];
                }
            }
        }
        
        $primaryKey = 'id';
        if (preg_match('/protected \$primaryKey\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $pkMatches)) {
            $primaryKey = $pkMatches[1];
        }
        
        echo "Table: $table\n";
        echo "PK: $primaryKey\n";
        echo "Fields: " . implode(', ', $fillable) . "\n\n";
    }
}
