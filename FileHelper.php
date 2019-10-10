<?php
/**
 * @author denis303 <mail@denis303.com>
 * @license MIT
 * @link http://denis303.com
 */
namespace denis303\php;

use Exception;

class FileHelper
{

    public static function setPermission($path, $permission, $throwExceptions = true, &$error = null)
    {
        if (is_file($path) || is_dir($path))
        {
            if (is_string($permission))
            {
                $permission = octdec($permission);
            }

            $result = chmod($path, $permission);

            if (!$result)
            {
                $error = $path . ' chmod ' . $permission . ' error.';

                if ($throwExceptions)
                {
                    throw new Exception($error);
                }

                return false;
            }
        }
        else
        {
            $error = $path . ' path not found.';

            if ($throwExceptions)
            {
                throw new Exception($error);
            }

            return false;
        }

        return true;
    }

    /**
     * Creates a new directory.
     *
     * This method is similar to the PHP `mkdir()` function except that
     * it uses `chmod()` to set the permission of the created directory
     * in order to avoid the impact of the `umask` setting.
     *
     * @author Qiang Xue <qiang.xue@gmail.com>
     * @author Alex Makarov <sam@rmcreative.ru>
     * @link http://www.yiiframework.com/
     * @copyright Copyright (c) 2008 Yii Software LLC
     * @license http://www.yiiframework.com/license/
     *
     * @author denis303 <mail@denis303.com>
     * @link http://denis303.com
     *
     * @param string $path path of the directory to be created.
     * @param int $mode the permission to be set for the created directory.
     * @param bool $recursive whether to create parent directories if they do not exist.
     *
     * @return bool whether the directory is created successfully
     *
     * @throws Exception if the directory could not be created (i.e. php error due to parallel changes)
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true, $throwExceptions = true, &$error = null)
    {
        if (is_dir($path))
        {
            return true;
        }

        $parentDir = dirname($path);
        
        // recurse if parent dir does not exist and we are not at the root of the file system.  
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path)
        {
            $result = static::createDirectory($parentDir, $mode, true, $throwExceptions, $error);

            if (!$result)
            {
                return false;
            }
        }
        
        try
        {
            if (!mkdir($path, $mode))
            {
                $error = $path . ' mkdir error.';

                if ($throwExceptions)
                {
                    throw new Exception($error);
                }
                else
                {
                    return false;
                }
            }
        }
        catch(Exception $e)
        {
            if (!is_dir($path))
            {
                $error = "Failed to create directory \"$path\": " . $e->getMessage();

                if ($throwExceptions)
                {
                    throw new Exception($error); // https://github.com/yiisoft/yii2/issues/9288
                }
                else
                {
                    return false;
                }
            }
        }

        return static::setPermission($path, $mode, $throwExceptions, $error);
    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     *
     * @author Aidan Lister <aidan@php.net>
     * @link http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
     * @author denis303 <mail@denis303.com>
     *
     * @param string $source Source path
     * @param string $dest Destination path
     * @param int $permissions New folder creation permissions
     *
     * @return bool Returns true on success, false on failure
     */
    public static function copy($source, $dest, $permissions = 0755, $throwExceptions = true, &$error = null)
    {
        // Check for symlinks
        if (is_link($source))
        {
            $result = symlink(readlink($source), $dest);
       
            if (!$result)
            {
                $error = $source . ' to ' . $dest . ' symlink error.';

                if ($throwExceptions)
                {
                    throw new Exception($error);
                }

                return false;
            }

            return true;
        }

        // Simple copy for a file
        if (is_file($source))
        {
            $dir = pathinfo($dest, PATHINFO_DIRNAME);

            if (!$dir)
            {
                throw new Exception('Target directory error.');
            }

            static::createDirectory($dir);

            $result = copy($source, $dest);

            if (!$result)
            {
                $error = $source . ' to ' . $dest . ' copy error.';

                if ($throwExceptions)
                {
                    throw new Exception($error);
                }

                return false;
            }

            return true;
        }

        // Make destination directory
        if (!is_dir($dest))
        {
            $result = static::createDirectory($dest, $permissions, true, true);
            
            if (!$result)
            {
                $error = $dest . ' create directory error.';

                if ($throwExceptions)
                {
                    throw new Exception($error);
                }

                return false;
            }
        }

        // Loop through the folder
        $dir = dir($source);

        if (!$dir)
        {
            if (!$return)
            {
                $error = $source . ' dir error.';

                if ($throwExceptions)
                {
                    throw new Exception($error);
                }

                return false;
            }
        }
        
        while(false !== ($entry = $dir->read()))
        {
            // Skip pointers
            if ($entry == '.' || $entry == '..')
            {
                continue;
            }

            // Deep copy directories
            $result = static::copy("$source/$entry", "$dest/$entry", $permissions, $throwExceptions, $error);
        
            if (!$result)
            {
                // Clean up
                $dir->close();

                return false;
            }
        }

        // Clean up
        $dir->close();

        return true;
    }

}