<?php
class Minter
{
    private $counter;

    public function __construct(private $base)
    {
        $this->counter = 0;
    }

    public function mint()
    {
        $this->counter++;
        return "{$this->base}_{$this->counter}";
    }
}
