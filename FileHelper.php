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

    protected static function _returnFalse($error, $throwExceptions)
    {
        if ($throwExceptions)
        {
            throw new Exception($error);
        }

        return false;
    }

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
            
                return static::_returnFalse($error, $throwExceptions);
            }
        }
        else
        {
            $error = $path . ' path not found.';

            return static::_returnFalse($error);
        }

        return true;
    }

    public static function delete($dir, $throwExceptions = true, &$error = null)
    {
        if (is_file($dir) || is_link($dir))
        {
            if (!unlink($dir))
            {
                $error = 'Can\'t delete: ' . $dir;

                return static::_returnFalse($error, $throwExceptions);
            }

            return true;
        }

        if (!is_dir($dir))
        {
            return static::_returnFalse($error, $throwExceptions);
        }
        
        if (!($handle = opendir($dir)))
        {
            $error = 'Can\'t open dir: ' . $dir;

            return static::_returnFalse($error, $throwExceptions);
        }
        
        while (($file = readdir($handle)) !== false)
        {
            if ($file === '.' || $file === '..')
            {
                continue;
            }
        
            $path = $dir . DIRECTORY_SEPARATOR . $file;
    
            if (!static::delete($path, $error))
            {
                return static::_returnFalse($error, $throwExceptions);
            }
        }
        
        if (!closedir($handle))
        {
            $error = 'Can\'t close directory: ' . $dir;

            return static::_returnFalse($error, $throwExceptions);
        }

        if (!rmdir($dir))
        {
            $error = 'Can\'t remove directory: '. $dir; 

            return static::_returnFalse($error, $throwExceptions);
        }

        return true;
    }    

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
                return static::_returnFalse($error, $throwExceptions);
            }
        }
        
        try
        {
            if (!mkdir($path, $mode))
            {
                $error = $path . ' mkdir error.';

                static::_returnFalse($error, $throwExceptions);
            }
        }
        catch(Exception $e)
        {
            if (!is_dir($path))
            {
                $error = "Failed to create directory \"$path\": " . $e->getMessage();

                return static::_returnFalse($error, $throwExceptions);
            }
        }

        return static::setPermission($path, $mode, $throwExceptions, $error);
    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     *
     * @author Aidan Lister <aidan@php.net>
     * @link http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
     *
     * @author denis303 <mail@denis303.com>
     * @link http://denis303.com
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

                return static::_returnFalse($error, $throwExceptions);
            }

            return true;
        }

        // Simple copy for a file
        if (is_file($source))
        {
            $dir = pathinfo($dest, PATHINFO_DIRNAME);

            if (!$dir)
            {
                return static::_returnFalse('Can\'t get dirname: ' . $dest, $throwExceptions);
            }

            if (!static::createDirectory($dir, $permission, true, $throwExceptions, $error))
            {
                return static::_returnFalse($error, $throwExceptions);
            }

            $result = copy($source, $dest);

            if (!$result)
            {
                $error = $source . ' to ' . $dest . ' copy error.';

                return static::_returnFalse($error, $throwExceptions);
            }

            return true;
        }

        // Make destination directory
        if (!is_dir($dest))
        {
            $result = static::createDirectory($dest, $permissions, true, $throwExceptions, $error);
            
            if (!$result)
            {
                return static::_returnFalse($error, $throwExceptions);
            }
        }

        // Loop through the folder
        $dir = dir($source);

        if (!$dir)
        {
            if (!$return)
            {
                $error = $source . ' dir error.';

                return static::_returnFalse($error, $throwExceptions);
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
                if (!$dir->close())
                {
                    $error = 'Can\'t close dir: ' . $source;

                    return static::_returnFalse($error, $throwExceptions);
                }

                return static::_returnFalse($error, $throwExceptions);
            }
        }

        // Clean up
        if (!$dir->close())
        {
            return static::_returnFalse('Can\'t close dir: ' . $source, $throwExceptions);
        }

        return true;
    }

}