<?php
$codeEditorConfig = [
    'showReformatButton' => false,
    'downloadButtonOnclick' => 'downloadTnsFile(); return false',
    'downloadButtonTitle' => 'Convert this code to a tns file',
    'downloadButtonLabel' => 'Download program (.tns)',
    'currentSourceDownloadLabel' => 'Download current source file (.py)',
    'postDownloadButtonsHtml' => <<<'HTML'
<button id="transferButton" class="btn btn-primary btn-sm hasTooltip disabled" disabled title="Can't run it yet, however" onclick="transferToEmu(); return false"><span class="glyphicon glyphicon-share" aria-hidden="true"></span> Transfer to emulator <span class="loadingicon hidden"> <span class="glyphicon glyphicon-refresh spinning"></span></span></button>
HTML,
];

require __DIR__ . '/../../_shared/templates/code_editor_body.php';
