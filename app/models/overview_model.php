<?php
class OverviewModel extends Model
{
    protected $id = null;
    public $path = null;
    public $exists = false;

    public function __construct($id)
    {
        $this->id = $id;
        $header_file = $this->ppath() . DIRECTORY_SEPARATOR . "header.xml";
        if (file_exists($header_file)) {
            $this->xml = new SimpleXMLElement(file_get_contents($header_file));
            $this->exists = true;
        }
    }

    public function headerXML()
    {
        return $this->xml->asXML();
    }

    public function bioghist()
    {
        $result = array();
        foreach ($this->xml->collection_overview->bioghist->p as $p) {
            $result[] = array('text' => $p);
        }
        return $result;
    }

    public function scopecontent() {
        $result = array();
        foreach ($this->xml->collection_overview->scopecontent->p as $p) {
            $result[] = array('text' => $p);
        }
        return $result;
    }

    public function title()
    {
        return $this->xml->descriptive_summary->title;
    }
}
