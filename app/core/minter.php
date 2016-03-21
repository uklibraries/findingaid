<?php
class Minter
{
    private $counter;
    private $base;

    public function __construct($base)
    {
        $this->counter = 0;
        $this->base = $base;
    }

    public function mint()
    {
        $this->counter++;
        return "{$this->base}_{$this->counter}";
    }
}
