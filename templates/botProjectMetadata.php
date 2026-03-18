<?php
/*
* Part of TI-Planet's Project Builder
* (C) Adrien "Adriweb" Bertrand
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*/

if (!isset($pm))
{
    die('Ahem ahem');
}

require_once 'utils.php';

$currProject = $pm->getCurrentProject();
$currProjectAuthor = $currProject->getAuthor();
$projectName = $currProject->getInternalName();
$projectDescription = trim($currProject->getName());
$moduleDescription = $currProject::PROJECT_MODULE_DESCRIPTION;
$projectType = $currProject->getType();
$shareMode = !$currProject->isMultiuser()
    ? 'Private'
    : ($currProject->isMulti_ReadWrite()
        ? ($currProject->isMulti_ReadWrite_CustomRestricted() ? 'Public read / restricted write' : 'Public read / write')
        : 'Public read only');
$availableFiles = method_exists($currProject, 'getAvailableSrcFiles') ? $currProject->getAvailableSrcFiles() : [];
$fileCount = count($availableFiles);

$projectURL = 'https://tiplanet.org/pb/?id=' . rawurlencode($currProject->getPID());
$authorURL = 'https://tiplanet.org/forum/memberlist.php?mode=viewprofile&u=' . $currProjectAuthor->getID();
$iconURL = $currProject->getIconURL();
if (strpos($iconURL, 'data:') === 0)
{
    $iconURL = null;
} elseif (strpos($iconURL, '//') === false)
{
    $iconURL = 'https://tiplanet.org/' . ltrim($iconURL, '/');
}

$metaDescription = $projectDescription !== ''
    ? $projectDescription
    : "{$moduleDescription}. Shared via TI-Planet Project Builder with {$fileCount} source file" . ($fileCount === 1 ? '' : 's') . '.';
$pageTitle = $projectName . ' | ' . $moduleDescription . ' | TI-Planet Project Builder';

$programmingLanguage = null;
switch ($projectType)
{
    case 'native_eZ80':
        $programmingLanguage = 'C/C++';
        break;
    case 'python_eZ80':
    case 'python_nspire':
        $programmingLanguage = 'Python';
        break;
    case 'lua_nspire':
        $programmingLanguage = 'Lua';
        break;
    case 'basic_eZ80':
        $programmingLanguage = 'TI-Basic';
        break;
}

$schemaType = $projectType === 'bbcode' ? 'CreativeWork' : 'SoftwareSourceCode';
$hasPart = [];
foreach ($availableFiles as $i => $fileName)
{
    $fileItem = [
        '@type' => $projectType === 'bbcode' ? 'CreativeWork' : 'SoftwareSourceCode',
        'name' => $fileName,
        'url' => $projectURL . '#file-' . rawurlencode($fileName),
    ];
    if ($programmingLanguage !== null)
    {
        $fileItem['programmingLanguage'] = $programmingLanguage;
    }
    $hasPart[] = $fileItem;
}

$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebSite',
            '@id' => 'https://tiplanet.org/pb/',
            'url' => 'https://tiplanet.org/pb/',
            'name' => 'TI-Planet Project Builder',
        ],
        [
            '@type' => 'WebPage',
            '@id' => $projectURL . '#webpage',
            'url' => $projectURL,
            'name' => $pageTitle,
            'description' => $metaDescription,
            'isPartOf' => [ '@id' => 'https://tiplanet.org/pb/' ],
            'about' => [ '@id' => $projectURL . '#project' ],
        ],
        [
            '@type' => $schemaType,
            '@id' => $projectURL . '#project',
            'url' => $projectURL,
            'name' => $projectName,
            'description' => $metaDescription,
            'author' => [
                '@type' => 'Person',
                'name' => $currProjectAuthor->getName(),
                'url' => $authorURL,
            ],
            'dateCreated' => gmdate('c', $currProject->getCreatedTstamp()),
            'dateModified' => gmdate('c', $currProject->getUpdatedTstamp()),
            'image' => $iconURL,
            'genre' => $projectType,
            'keywords' => implode(', ', array_filter([
                'TI-Planet Project Builder',
                $projectType,
                $moduleDescription,
                $shareMode,
                $programmingLanguage,
            ])),
            'isAccessibleForFree' => true,
            'hasPart' => $hasPart,
        ],
    ],
];
if ($projectDescription !== '')
{
    $jsonLd['@graph'][2]['alternateName'] = $projectDescription;
}
if ($programmingLanguage !== null)
{
    $jsonLd['@graph'][2]['programmingLanguage'] = $programmingLanguage;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlentities($pageTitle, ENT_QUOTES) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlentities($metaDescription, ENT_QUOTES) ?>">
    <link rel="canonical" href="<?= htmlentities($projectURL, ENT_QUOTES) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlentities($pageTitle, ENT_QUOTES) ?>">
    <meta property="og:description" content="<?= htmlentities($metaDescription, ENT_QUOTES) ?>">
    <meta property="og:url" content="<?= htmlentities($projectURL, ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlentities($iconURL, ENT_QUOTES) ?>">
    <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?></script>
    <style>
        body {
            margin: 24px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #222;
            background: #fff;
        }
        main {
            max-width: 900px;
        }
        h1, h2 {
            margin-bottom: 0.4em;
        }
        p {
            margin: 0 0 1em;
        }
        dl {
            margin: 1em 0 1.5em;
        }
        dt {
            font-weight: 700;
        }
        dd {
            margin: 0 0 0.6em 0;
        }
        img {
            max-width: 64px;
            max-height: 64px;
            vertical-align: middle;
            margin-right: 12px;
        }
        ul.files {
            padding-left: 20px;
        }
        code {
            font-family: Menlo, Consolas, monospace;
        }
    </style>
</head>
<body>
<main>
    <p><img src="<?= htmlentities($iconURL, ENT_QUOTES) ?>" alt=""><strong><?= htmlentities($projectType, ENT_QUOTES) ?></strong></p>
    <h1><?= htmlentities($projectName, ENT_QUOTES) ?></h1>
    <p><?= htmlentities($metaDescription, ENT_QUOTES) ?></p>

    <dl>
        <dt>Module</dt>
        <dd><?= htmlentities($moduleDescription, ENT_QUOTES) ?></dd>
        <dt>Author</dt>
        <dd><a href="<?= htmlentities($authorURL, ENT_QUOTES) ?>"><?= htmlentities($currProjectAuthor->getName(), ENT_QUOTES) ?></a></dd>
        <dt>Sharing</dt>
        <dd><?= htmlentities($shareMode, ENT_QUOTES) ?></dd>
        <dt>Created</dt>
        <dd><?= gmdate('Y-m-d H:i:s', $currProject->getCreatedTstamp()) ?> UTC</dd>
        <dt>Updated</dt>
        <dd><?= gmdate('Y-m-d H:i:s', $currProject->getUpdatedTstamp()) ?> UTC</dd>
        <dt>Files</dt>
        <dd><?= $fileCount ?></dd>
        <?php if ($programmingLanguage !== null) { ?>
        <dt>Language</dt>
        <dd><?= htmlentities($programmingLanguage, ENT_QUOTES) ?></dd>
        <?php } ?>
    </dl>

    <h2>Project Files</h2>
    <?php if ($fileCount > 0) { ?>
    <ul class="files">
        <?php foreach ($availableFiles as $fileName) { ?>
        <li id="file-<?= rawurlencode($fileName) ?>"><code><?= htmlentities($fileName, ENT_QUOTES) ?></code></li>
        <?php } ?>
    </ul>
    <?php } else { ?>
    <p>No source files are currently listed for this project.</p>
    <?php } ?>

    <p>Metadata page for crawlers and previews. Interactive editing is available on the standard Project Builder view.</p>
</main>
</body>
</html>
