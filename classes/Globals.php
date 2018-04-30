<?php

class Globals
{
    const DEFAULT_OPTIONS = [
        'backup' => false,
        'convert' => false,
        'debug' => false,
        'load_custom' => false,
    ];
    private $options;
    private $logger;
    private $fileHandler;
    private $settings;

    public function __construct($options)
    {
        $this->setOptions($options);
        $this->initGlobals();
    }

    private function setOptions($options)
    {
        $this->options = arrayDefault($options, self::DEFAULT_OPTIONS);
    }

    private function initGlobals()
    {
        $this->initLogger();
        $this->initFileHandler();
        $this->initSettings();
    }

    private function initLogger()
    {
        $this->logger = new TerminalLogger();
    }

    private function initFileHandler()
    {
        $debug = $this->getOption('debug');
        $this->fileHandler = new FileHandler($debug, [$this->logger, 'logLine']);
    }

    private function initSettings()
    {
        $customFile = $this->getOption('load_custom') ? joinPath(PROGRAM_DIR, CUSTOM_CONFIG) : '';
        $this->settings = new Settings($customFile);
    }

    public function __call($name, $args)
    {
        $name = lcfirst(substr($name, 3));
        return $this->$name;
    }

    public function getOption($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }
}
