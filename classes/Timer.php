<?php

class Timer
{
    const PAUSED = 0;
    const RUNNING = 1;
    protected $start;
    protected $time;
    protected $state;
    protected $formatMethod;

    public function __construct($formatMethod = '')
    {
        $this->reset();
        $this->setFormatMethod($formatMethod);
    }

    public function setFormatMethod($method)
    {
        $this->formatMethod = isCallable($method) ? $method : null;
    }

    private function getResult($time)
    {
        if ($this->formatMethod) {
            $format = $this->formatMethod;
            $time = $format($time);
        }
        return $time;
    }

    public function reset()
    {
        $this->time = 0;
        $this->play();
    }

    public function pause()
    {
        $this->addInterval($this->getCurrentInterval());
        $this->start = self::getTime();
        $this->setState(self::PAuSED);
    }

    public function play()
    {
        $this->start = self::getTime();
        $this->setState(self::RUNNING);
    }

    private function getTime()
    {
        return microtime(true);
    }

    private function getCurrentInterval()
    {
        return $this->isRunning() ? self::getTime() - $this->start : 0;
    }

    public function getDuration()
    {
        $time = $this->getCurrentInterval() + $this->time;
        return $this->getResult($time);
    }

    private function addInterval($duration = 0)
    {
        $this->time += $duration;
    }

    public function getState()
    {
        return $this->state;
    }

    private function setState($state)
    {
        $this->state = $state;
    }

    public function isRunning()
    {
        return $this->state === self::RUNNING;
    }

    public function isPaused()
    {
        return $this->state === self::PAUSED;
    }
}
