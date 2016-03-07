<?php
class FindingaidModel extends Model
{
    protected $id = null;
    public $path = null;

    public function __construct($id)
    {
        $this->id = $id;
        $header_file = $this->ppath() . DIRECTORY_SEPARATOR . "header.xml";
        if (file_exists($header_file)) {
            $this->xml = new SimpleXMLElement(file_get_contents($header_file));
        }
        $metadata_file = $this->ppath() . DIRECTORY_SEPARATOR . "$id.xml";
        if (file_exists($metadata_file)) {
            $this->metadata = new SimpleXMLElement(file_get_contents($metadata_file));
        }
    }

    public function repository()
    {
        $repositories = $this->xml->xpath('//meta/repository');
        if (count($repositories) > 0) {
            $repository = $repositories[0];
            return dom_import_simplexml($repository)->textContent;
        }
        else {
            return 'Unknown repository';
        }
    }

    public function title()
    {
        return $this->xml->descriptive_summary->title;
    }

    public function id()
    {
        return $this->id;
    }

    public function metadata($path)
    {
        return $this->metadata->xpath($path);
    }

    public function unitid()
    {
        $unitid = $this->metadata('//archdesc/did/unitid');
        if (count($unitid) > 0) {
            $unitid = $unitid[0];
        }
        else {
            return "no unitid";
        }
        return $unitid;
    }

    public function unittitle()
    {
        $title = $this->metadata('//archdesc/did//unittitle');
        if (count($title) > 0) {
            $title = $title[0];
        }
        return dom_import_simplexml($title)->textContent;
    }

    public function unitdate()
    {
        $date = $this->metadata('//archdesc/did//unitdate');
        if (count($date) > 0) {
            $date = $date[0];
        }
        return dom_import_simplexml($date)->textContent;
    }
}
