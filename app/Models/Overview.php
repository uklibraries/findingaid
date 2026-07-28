<?php

namespace App\Models;

use SimpleXMLElement;
use App\Core\Model;

class Overview extends Model
{
    public $path = null;
    public $exists = false;

    public function __construct(protected $id)
    {
        $header_file = $this->ppath() . DIRECTORY_SEPARATOR . "header.xml";
        if (file_exists($header_file)) {
            $this->xml = new SimpleXMLElement(file_get_contents($header_file));
            $this->exists = true;
        }
    }

    public function bioghist()
    {
        $result = [];
        foreach ($this->xml->collection_overview->bioghist->p as $p) {
            $result[] = ['text' => $p];
        }
        return $result;
    }

    public function scopecontent()
    {
        $result = [];
        foreach ($this->xml->collection_overview->scopecontent->p as $p) {
            $result[] = ['text' => $p];
        }
        return $result;
    }

    public function title()
    {
        return $this->xml->descriptive_summary->title;
    }
}
