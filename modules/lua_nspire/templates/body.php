<?php
$codeEditorConfig = [
    'showReformatButton' => false,
    'showCanonicalizeButton' => false,
    'downloadButtonOnclick' => 'downloadTnsFile(); return false',
    'downloadButtonTitle' => 'Convert this code to a tns file',
    'downloadButtonLabel' => 'Download program (.tns)',
    'currentSourceDownloadLabel' => 'Download current source file (.lua)',
];

require __DIR__ . '/../../_shared/templates/code_editor_body.php';
