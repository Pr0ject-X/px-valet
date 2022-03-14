<?php

declare(strict_types=1);

namespace Pr0jectX\PxValet;

use Pr0jectX\Px\DefaultPluginFoundation;

/**
 * Define the Valet plugin foundation base.
 */
class Valet extends DefaultPluginFoundation
{
    /**
     * Get template directory files.
     *
     * @param string $filename
     *
     * @return array /SplFileInfo[]
     *   An array of template files within the directory.
     */
    public static function getTemplateDirFiles(string $filename): array
    {
        $directory = static::getTemplateFilePath($filename);

        if (!is_dir($directory)) {
            return [];
        }
        $files = [];

        /** @var \DirectoryIterator $file */
        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            }
            $files[] = $file->getFileInfo();
        }

        return $files;
    }
}
