<?php

class FileHandler
{
    private $debug;
    private $debugMethod;
    private $exceptionThrower;
    private $copyIgnores;

    public function __construct($debug = false, $debugMethod = '', $exceptionHandler = '')
    {
        $this->debug = (bool)$debug;
        $this->setDebugMethod($debugMethod);
        $this->setExceptionThrower($exceptionHandler);
    }

    public function setDebugMethod($method = '')
    {
        $defaultMethod = function ($msg) {
            error_log($msg);
        };

        $this->debugMethod = isCallable($method) ? $method : ($this->debugMethod ?: $defaultMethod);
    }

    public function enableDebug()
    {
        $this->debug = true;
    }

    public function disableDebug()
    {
        $this->debug = false;
    }

    public function debugLog($msg)
    {
        $output = $this->debugMethod;
        $output("[Debug] $msg");
    }

    public function setExceptionThrower($thrower = '')
    {
        $defaultThrower = function ($error) {
            throw new Exception($error, E_ERROR);
        };

        $this->exceptionThrower = isCallable($thrower) ? $thrower : ($this->exceptionThrower ?: $defaultThrower);
    }

    public function throwException($error)
    {
        $thrower = $this->exceptionThrower;
        $thrower($error);
    }

    private function isIgnored($filepath)
    {
        foreach ($this->copyIgnores as $ignore) {
            if (strpos($filepath, $ignore) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Copies the contents of source into dest
     *
     * @param string $source The source file or directory
     * @param string $dest The target directory
     * @param string $ignore Specifies files starting with this name to be ignored (optional)
     * @return void
     * @throws Exception
     */
    public function copy($source, $dest, array $ignores = [])
    {
        $source = realpath($source);
        $dest = realpath($dest);
        if ($this->copyIgnores === null) {
            $this->copyIgnores = $ignores;
        }

        foreach ([$source, $dest] as $item) {
            if (!file_exists($item)) {
                $this->throwException("Unable to copy: $item is not a file or directory");
            }
        }

        // Is file?
        if (is_file($source)) {
            if ($this->isIgnored($source, $this->copyIgnores)) {
                return;
            }

            if ($this->debug) {
                $this->debugLog("Copying $source to $dest");
            } elseif (@!copy($source, $dest)) {
                $this->throwException("Unable to copy file $source");
            }

            return;
        }

        // Is folder
        $contents = dirContents($source);
        foreach ($contents as $content) {
            if ($this->isIgnored($content, $this->copyIgnores)) {
                continue;
            }

            $sourceContent = "$source/$content";
            $destContent = "$dest/$content";

            // Is file: copy file
            if (is_file($sourceContent)) {
                if ($this->debug) {
                    $this->debugLog("Copying $source to $dest");
                } elseif (@!copy($sourceContent, $destContent)) {
                    $this->throwException("Unable to copy file $sourceContent");
                }

                continue;
            }

            // Copy folder and recurse
            if ($this->debug) {
                $this->debugLog("Creating directory $destContent");
            } elseif (@!mkdir($destContent)) {
                $this->throwException("Unable to copy folder $sourceContent");
            }

            $this->copy($sourceContent, $destContent);
        }


    }

    /**
     * Moves (renames) a file or folder to the target location
     *
     * @param string $oldName The path of the file/folder to be moved
     * @param string $newName The new path
     * @throws Exception
     */
    public function move($source = '', $dest = '')
    {
        if ($source === '' || $dest === '') {
            $this->throwException('Rename expects two non empty strings as parameters');
        }

        if ($this->debug) {
            $this->debugLog("Moving $source to $dest");
        } elseif (!file_exists($source)) {
            $this->throwException("Could not rename $source: path does not exist");
        } elseif (@!rename($source, $dest)) {
            $this->throwException("Could not rename $source to $dest");
        }
    }

    /**
     * Alias for $this->move()
     *
     * @param array ...$args See $this->move() for the expected arguments
     * @throws Exception
     */
    public function rename(...$args)
    {
        try {
            $this->move(...$args);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Creates a new directory
     *
     * @param string $dir The specified location
     * @param string folder The name of the new folder
     * @throws Exception
     */
    public function makeDir($dir, $folder)
    {
        if ($this->debug) {
            $this->debugLog("Creating directory $dir");
        } elseif (@!mkdir(joinPath($dir, $folder))) {
            $this->throwException("Unable to create folder $folder under $dir");
        }
    }
}