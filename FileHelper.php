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

    public static function copySymlink($source, $dest, $throwExceptions = true, &$error = null)
    {
        if (!is_link($source))
        {
            return static::_returnFalse($source . ' not symlink.', $throwExceptions);
        }

        if (!symlink(readlink($source), $dest))
        {
            $error = 'Can\'t create symlink from ' . $source . ' to ' . $dest;

            return static::_returnFalse($error, $throwExceptions);
        }

        return true;
    }

    public static function copyFile($source, $dest, $permissions = 0755, $throwExceptions = true, &$error = null)
    {
        if (!is_file($source))
        {
            return static::_returnFalse($source . ' not file.', $throwExceptions);
        }

        $dir = pathinfo($dest, PATHINFO_DIRNAME);

        if (!$dir)
        {
            $error = 'Can\'t get PATHINFO_DIRNAME: ' . $dest;

            return static::_returnFalse($error, $throwExceptions);
        }

        if (!static::createDirectory($dir, $permission, true, $throwExceptions, $error))
        {
            return static::_returnFalse($error, $throwExceptions);
        }

        if (!copy($source, $dest))
        {
            $error = 'Can\'t copy file from ' . $source . ' to ' . $dest;

            return static::_returnFalse($error, $throwExceptions);
        }

        return true;
    }

    public function copyDirectory($source, $dest, $permission = 0755, $throwExceptions, &$error = null)
    {
        if (!is_dir($dest))
        {        
            if (!static::createDirectory($dest, $permissions, true, $throwExceptions, $error))
            {
                return static::_returnFalse($error, $throwExceptions);
            }
        }

        $items = static::readDirectory($source, $throwExceptions, $error);

        if ($items === false)
        {
            return static::_returnFalse($error, $throwExceptions);
        }

        foreach($items as $file)
        {
            if (!static::copy($source . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file, $permissions, $throwExceptions, $error))
            {
                return static::_returnFalse($error, $throwExceptions);
            }
        }

        return true;
    }

    public static function copy($source, $dest, $permissions = 0755, $throwExceptions = true, &$error = null)
    {
        if (is_link($source))
        {
            return static::copySymlink($source, $dest);
        }

        if (is_file($source))
        {
            return static::copyFile($source, $dest, $permission, $throwExceptions, $error);
        }

        if (is_dir($source))
        {
            return static::copyDirectory($source, $dest, $permission, $throwExceptions, $error);
        }

        $error = 'File not found: ' . $source;

        return static::_returnFalse($error, $throwExceptions);
    }

    public static function readDirectory($source, $throwExceptions = true, &$error = null)
    {
        $dir = dir($source);

        if (!$dir)
        {
            $error = 'Can\'t open directory: ' . $dir;

            return static::_returnFalse($error, $throwExceptions);
        }

        $items = [];
        
        while(false !== ($file = $dir->read()))
        {
            if ($file == '.' || $file == '..')
            {
                continue;
            }

            $items[] = $file;
        }

        if (!$dir->close())
        {
            $error = 'Can\'t close directory: ' . $source;

            return static::_returnFalse($error, $throwExceptions);
        }

        return $items;
    }

}