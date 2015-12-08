<?php
class Model
{
    protected $xml = null;

    protected function ppath()
    {
        $array = array(
            ROOT,
            'xml',
            'pairtree_root',
        );
        $tree = $this->id;
        while (strlen($tree) >= 2) {
          $prefix = substr($tree, 0, 2);
          $tree = substr($tree, 2);
          $array[] = $prefix;
        }
        $array[] = $this->id;
        return implode(DIRECTORY_SEPARATOR, $array);
    }

    public function xpath($path)
    {
        return $this->xml->xpath($path);
    }
}
