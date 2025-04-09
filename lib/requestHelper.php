<?php

/* file helpers */
function countRequestedFiles(array $files): int
{
    if (!isset($files['name'])) {
        return 0;
    }

    if (is_array($files['name'])) {
        return count(array_filter($files['name'], fn($name) => $name !== ''));
    }

    return $files['name'] !== '' ? 1 : 0;
}

function getTotalRequestedFileSize(array $files): int
{
    if (!isset($files['size']))
        return 0;

    // multiple file upload
    if (is_array($files['size'])) {
        return array_sum(array_filter($files['size'], fn($size) => $size > 0));
    }

    // single file
    return $files['size'] > 0 ? $files['size'] : 0;
}
