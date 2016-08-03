<?php
class ComponentModel extends Model
{
    protected $id;
    protected $subcomponents = array();
    private $component_id;
    private $basename;

    public function __construct($id, $component_id)
    {
        global $g_config;
        $this->id = $id;
        $this->component_id = $component_id;
        $this->basename = $id . '_' . $this->component_id . '.xml';
        $this->config = $g_config;
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
        global $g_minter;
        $pieces = array();
        if (count($this->xpath('did/dao')) > 0) {
            $pieces = $this->xpath('did/dao');
        }
        $results = array();
        $links_raw = array();
        foreach ($pieces as $piece) {
            $dao = $piece['entityref'];
            $links_file = $this->ppath() . DIRECTORY_SEPARATOR . $dao . '.json';
            if (file_exists($links_file)) {
                $links_raw = json_decode(file_get_contents($links_file), true);
                break;
            }
        }
        $links = array();
        $thumb_count = 0;
        $ref_count = 0;
        $image_threshold = 5;
        foreach ($links_raw as $link_raw) {
            $link = array();
            foreach ($link_raw['links'] as $use => $href) {
                $use = str_replace(' ', '_', $use);
                switch ($use) {
                case 'thumbnail':
                    $thumb_count++;
                    if ($thumb_count <= $image_threshold) {
                        $field = 'image';
                    }
                    else {
                        $field = 'image_overflow';
                    }
                    if (empty($link[$field])) {
                        $link[$field] = array();
                    }
                    $link[$field]['thumb'] = $href;
                    break;
                case 'reference_image':
                    $ref_count++;
                    if ($ref_count <= $image_threshold) {
                        $field = 'image';
                    }
                    else {
                        $field = 'image_overflow';
                    }
                    if (empty($link[$field])) {
                        $link[$field] = array();
                    }
                    $link[$field]['full'] = $href;
                    $link[$field]['href_id'] = $g_minter->mint();
                    break;
                case 'reference_audio':
                    if (empty($link['audio'])) {
                        $link['audio'] = array();
                    }
                    $link['audio']['href'] = $href;
                    $link['audio']['href_id'] = $g_minter->mint();
                    break;
                case 'reference_video':
                    if (empty($link['video'])) {
                        $link['video'] = array();
                    }
                    $link['video']['href'] = $href;
                    $link['video']['href_id'] = $g_minter->mint();
                    break;
                default:
                    break;
                }
            }
            $links[] = $link;
        }
        if (($thumb_count > $image_threshold) || ($ref_count > $image_threshold)) {
            $links[] = array('extra' => true);
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
        $buckets = array();
        $bucket = array();
        $cache = array();
        $tagged = null;

        $simple = true;
        foreach ($this->xpath($contents_config['container']) as $container) {
            $attributes = $container->attributes();
            if (isset($attributes['parent'])) {
                $simple = false;
                break;
            }
        }

        if ($simple) {
            foreach ($this->xpath($contents_config['container']) as $container) {
                $attributes = $container->attributes();
                $aspect = array(
                    'type'    => $this->container_type($attributes),
                    'content' => (string)$container,
                );
                $bucket[] = $aspect;
            }
            $buckets[] = $bucket;
        }
        else {
            $aspects = array();
            $section = array();
            $section_ids = array();
            foreach ($this->xpath($contents_config['container']) as $container) {
                $attributes = $container->attributes();
                $aspect = array(
                    'type'    => $this->container_type($attributes),
                    'content' => (string)$container,
                );

                if (isset($attributes['id'])) {
                    $id = trim($attributes['id']);
                }
                else {
                    $id = md5($container->asXML());
                }
                $aspect['id'] = $id;

                if (isset($attributes['parent'])) {
                    $id = trim($attributes['parent']);
                    $aspect['id'] = $id;
                    $section[$id][] = $aspect;
                }
                else {
                    $section[$id] = array($aspect);
                    $section_ids[] = $id;
                }
            }

            foreach ($section_ids as $id) {
                $bucket = array();
                foreach ($section[$id] as $thing) {
                    $bucket[] = $thing;
                }
                $buckets[] = $bucket;
            }
        }

        if (count($buckets) > 0) {
            $requests_config = $this->config->get('requests');
            $active = $requests_config['active'];
            $inactive = $requests_config['inactive'];
            foreach ($buckets as $bucket) {
                if (count($bucket) > 0) {
                    $request_target = "fa-request-target-" . md5(json_encode($bucket));
                    $container_list_pieces = array();
                    $first = true;
                    foreach ($bucket as $aspect) {
                        $piece = $aspect['type'] . ' ' . $aspect['content'];
                        if ($first) {
                            $piece = ucfirst($piece);
                            $first = false;
                        }
                        $container_list_pieces[] = $piece;
                    }
                    $volume = $container_list_pieces[0];
                    $summary = implode(', ', $container_list_pieces);
                    $full_container_list = $summary . ': '. $this->title();
                    array_shift($container_list_pieces);
                    $rest = implode(', ', $container_list_pieces);
                    $container_list = array(
                        'id'             => $request_target,
                        'summary'        => $summary,
                        'volume'         => $volume,
                        'container'      => $rest,
                        'container_list' => $full_container_list,
                        'active'         => $active,
                        'inactive'       => $inactive,
                    );
                    $container_lists[] = $container_list;
                }
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

    private function container_type($attributes)
    {
        if (isset($attributes['type'])) {
            $type = trim($attributes['type']);
            if ($type === 'othertype') {
                if (isset($attributes['label'])) {
                    return trim($attributes['label']);
                }
                else {
                    return 'container';
                }
            }
            else {
                return $type;
            }
        }
        else if (isset($attributes['label'])) {
            return trim($attributes['label']);
        }
        else {
            return 'container';
        }
    }
}
