<?php

class File
{
    protected $name;
    protected $dir;
    protected $extension;
    protected $renameMethod;

    public function __construct($path)
    {
        if (!file_exists($path)) {
            throw new Exception('Could not construct File object: provided path does not exist' . E_ERROR);
        }

        $this->name = getFileName($path);
        $this->dir = dirname($path);
        $this->setExtension(getExtension($path));
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($newName)
    {
        $newName = trim($newName);
        if ($newName) {
            $this->name = $newName;
        }
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function setDir($dir)
    {
        $dir = trim($dir);
        if ($dir) {
            $this->dir = $dir;
        }
    }

    public function getExtension()
    {
        return $this->extension;
    }

    private function setExtension($ext)
    {
        $ext = trim($ext, '.');
        $this->extension = $ext ? ".$ext" : '';
    }

    public function getFilename()
    {
        return $this->name . $this->getExtension();
    }

    public function getPath()
    {
        return joinPath($this->dir, $this->getFilename());
    }
}
