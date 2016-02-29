<?php
class ComponentModel extends Model
{
    protected $id;
    protected $subcomponents = array();
    private $component_id;
    private $basename;

    public function __construct($id, $component_id)
    {
        $this->id = $id;
        $this->component_id = $component_id;
        $this->basename = $id . '_' . $this->component_id . '.xml';
        $this->config = new Config();
        $component_file = $this->ppath() . DIRECTORY_SEPARATOR . $this->basename;
        if (file_exists($component_file)) {
          $this->xml = new SimpleXMLElement(file_get_contents($component_file));
        }
        $this->links = $this->links();
        $contents_config = $this->config->get('contents');
        foreach ($this->xpath($contents_config['component']) as $c) {
            $cattrs = $c->attributes();
            $cid = $cattrs['id'];
            $this->subcomponents[] = new ComponentModel($id, $cid);
        }
    }

    public function links()
    {
        $pieces = array();
        if (count($this->xpath('did/dao')) > 0) {
            $pieces = $this->xpath('did/dao');
        }
        $results = array();
        foreach ($pieces as $piece) {
            $dao = $piece['entityref'];
            $links_file = $this->ppath() . DIRECTORY_SEPARATOR . $dao . '.json';
            if (file_exists($links_file)) {
                $links_raw = json_decode(file_get_contents($links_file), true);
                break;
            }
        }
        $links = array();
        foreach ($links_raw as $link_raw) {
            $link = array();
            foreach ($link_raw['links'] as $use => $href) {
                $use = str_replace(' ', '_', $use);
                if ($use === 'thumbnail') {
                    $link['thumb'] = $href;
                }
                else {
                    $link['full'] = $href;
                }
            }
            $links[] = $link;
        }
        return $links;
    }

    public function title()
    {
        $pieces = array();
        if (count($this->xpath('did/unitdate')) > 0) {
            $pieces = array_merge(
                $pieces,
                $this->xpath('did/unittitle'),
                $this->xpath('did/unitdate')
            );
        }
        else {
            $pieces = array_merge($pieces, $this->xpath('did/unittitle'));
        }
        $results = array();
        foreach ($pieces as $piece) {
            $results[] = dom_import_simplexml($piece)->textContent;
        }
        return implode(', ', $results);
    }

    public function container_lists()
    {
        $container_lists = array();
        $order = array();
        $containers = array();
        $contents_config = $this->config->get('contents');
        foreach ($this->xpath($contents_config['container']) as $container) {
            $attributes = $container->attributes();
            if (isset($attributes['parent'])) {
                # subordinate container
                $parent = trim($attributes['parent']);
                $type = strtolower($attributes['type']);
                $number = (string)$container;
                $containers["child-$parent"] = array(
                    'id' => "child-$parent",
                    'type' => $type,
                    'number' => $number,
                    'child' => null,
                );
                $containers[$parent]['child'] = "child-$parent";
            }
            elseif (isset($attributes['id'])) {
                # base container
                $id = trim($attributes['id']);
                $type = strtolower($attributes['type']);
                $number = (string)$container;
                $order[] = $id;
                $containers[$id] = array(
                    'id' => $id,
                    'type' => $type,
                    'number' => $number,
                    'child' => null,
                );
            }
            else {
                # unaligned container
            }
        }
        if (count($order) > 0) {
            $requests_config = $this->config->get('requests');
            $inactive = $requests_config['inactive'];
            $active = $requests_config['active'];
            foreach ($order as $id) {
                $list = array();
                $container = $containers[$id];
                $list[] = ucfirst($container['type']) . ' '  . $container['number'];
                $request_target = "fa-request-target-$id";
                while (isset($container['child'])) {
                    $container = $containers[$container['child']];
                    $list[] = "{$container['type']} {$container['number']}";
                    $request_target = "fa-request-target-$id";
                }
                $top = $list[0];
                $summary = implode(', ', $list);
                $full_container_list = $summary . ': ' . $this->title();
                array_shift($list);
                $rest = implode(', ', $list);
                $container_list = array(
                    'id' => $request_target,
                    'summary' => $summary,
                    'volume' => $top,
                    'container' => $rest,
                    'container_list' => $full_container_list,
                    'active' => $active,
                    'inactive' => $inactive,
                );
                $container_lists[] = $container_list;
            }
        }
        return $container_lists;
    }

    public function scopecontent()
    {
        $scopecontent = array();
        $contents_config = $this->config->get('contents');
        foreach ($this->xpath($contents_config['scopecontent']) as $p) {
            $scopecontent[] = array(
                'p' => dom_import_simplexml($p)->textContent,
            );
        }
        return $scopecontent;
    }

    public function subcomponents()
    {
        return $this->subcomponents;
    }

    public function xml()
    {
        return $this->xml;
    }

    public function level()
    {
        $attributes = $this->xml->attributes();
        return $attributes['level'];
    }
}
