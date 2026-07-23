<?php

namespace App\Models;

use SimpleXMLElement;
use App\Core\Model;

class Component extends Model
{
    protected $subcomponents = [];
    private $basename;
    public $config;
    public $links;
    public $has_image_overflow = false;
    public $xml;

    public function __construct(protected $id, private $component_id)
    {
        global $g_config;
        $this->basename = $this->id . '_' . $this->component_id . '.xml';
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
            $this->subcomponents[] = new Component($this->id, $cid);
        }
    }

    public function links()
    {
        global $g_minter;
        $pieces = [];
        if (count($this->xpath('did/dao')) > 0) {
            $pieces = $this->xpath('did/dao');
        }
        $results = [];
        $links_raw = [];
        foreach ($pieces as $piece) {
            $dao = $piece['entityref'];
            $links_file = $this->ppath() . DIRECTORY_SEPARATOR . $dao . '.json';
            if (file_exists($links_file)) {
                $links_raw = json_decode(file_get_contents($links_file), true);
                break;
            }
        }
        $links = [];
        $thumb_count = 0;
        $ref_count = 0;
        $image_threshold = 5;
        $base_title = trim(strip_tags($this->title()));
        $total_images = 0;
        foreach ($links_raw as $link_raw) {
            foreach ($link_raw['links'] as $use => $href) {
                if (str_replace(' ', '_', $use) === 'thumbnail') {
                    $total_images++;
                }
            }
        }
        foreach ($links_raw as $link_raw) {
            $link = [];
            foreach ($link_raw['links'] as $use => $href) {
                $use = str_replace(' ', '_', $use);
                switch ($use) {
                    case 'thumbnail':
                        $thumb_count++;
                        if ($thumb_count <= $image_threshold) {
                            $field = 'image';
                        } else {
                            $field = 'image_overflow';
                        }
                        if (empty($link[$field])) {
                            $link[$field] = [];
                        }
                        $link[$field]['thumb'] = $href;
                        if ($total_images > 1) {
                            if ($base_title !== '') {
                                $link[$field]['alt'] = $base_title
                                    . ' (image ' . $thumb_count . ' of ' . $total_images . ')';
                            } else {
                                $link[$field]['alt'] = 'image ' . $thumb_count . ' of ' . $total_images;
                            }
                        } else {
                            $link[$field]['alt'] = $base_title !== '' ? $base_title : 'image';
                        }
                        break;
                    case 'reference_image':
                        $ref_count++;
                        if ($ref_count <= $image_threshold) {
                            $field = 'image';
                        } else {
                            $field = 'image_overflow';
                        }
                        if (empty($link[$field])) {
                            $link[$field] = [];
                        }
                        $link[$field]['full'] = $href;
                        $link[$field]['href_id'] = $g_minter->mint();
                        break;
                    case 'reference_audio':
                        if (empty($link['audio'])) {
                            $link['audio'] = [];
                        }
                        $link['audio']['href'] = $href;
                        $link['audio']['href_id'] = $g_minter->mint();
                        $link['audio']['play_label'] = $base_title !== ''
                            ? 'Play audio: ' . $base_title
                            : 'Play audio';
                        break;
                    case 'reference_video':
                        if (empty($link['video'])) {
                            $link['video'] = [];
                        }
                        $link['video']['href'] = $href;
                        $link['video']['href_id'] = $g_minter->mint();
                        $link['video']['play_label'] = $base_title !== ''
                            ? 'Play video: ' . $base_title
                            : 'Play video';
                        break;
                    default:
                        break;
                }
            }
            $links[] = $link;
        }
        $this->has_image_overflow =
            ($thumb_count > $image_threshold) || ($ref_count > $image_threshold);
        return $links;
    }

    public function title()
    {
        $pieces = [];
        if (count($this->xpath('did/unitdate')) > 0) {
            $pieces = array_merge(
                $pieces,
                $this->xpath('did/unittitle'),
                $this->xpath('did/unitdate')
            );
        } else {
            $pieces = array_merge($pieces, $this->xpath('did/unittitle'));
        }
        $segments = [];
        foreach ($pieces as $piece) {
            $segments[] = fa_render($piece);
        }
        return implode(', ', $segments);
    }

    public function containerLists()
    {
        $container_lists = [];
        $order = [];
        $containers = [];
        $contents_config = $this->config->get('contents');
        $buckets = [];
        $bucket = [];
        $cache = [];
        $tagged = null;

        $aspects = [];
        $section = [];
        $section_ids = [];
        $section_id_for = [];
        foreach ($this->xpath($contents_config['container']) as $container) {
            $attributes = $container->attributes();
            $aspect = [
                'type'    => $this->containerType($attributes),
                'content' => (string)$container,
            ];

            if (isset($attributes['id'])) {
                $id = trim($attributes['id']);
            } else {
                $id = md5((string) $container->asXML());
            }
            $aspect['id'] = $id;

            if (isset($attributes['parent'])) {
                $pid = trim($attributes['parent']);
                $ancestor = $section_id_for[$pid];
                $section_id_for[$id] = $ancestor;
                $section[$ancestor][] = $aspect;
            } else {
                $section_id_for[$id] = $id;
                $section[$id] = [$aspect];
                $section_ids[] = $id;
            }
        }

        foreach ($section_ids as $id) {
            $bucket = [];
            foreach ($section[$id] as $thing) {
                $bucket[] = $thing;
            }
            $buckets[] = $bucket;
        }

        if (count($buckets) > 0) {
            $requests_config = $this->config->get('requests');
            $active = $requests_config['active'];
            $inactive = $requests_config['inactive'];
            foreach ($buckets as $bucket) {
                if (count($bucket) > 0) {
                    $request_target = "fa-request-target-" . md5(json_encode($bucket));
                    $container_list_pieces = [];
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
                    $full_container_list = fa_brevity($summary . ': ' . $this->title(), FA_AEON_MAX);
                    array_shift($container_list_pieces);
                    $rest = implode(', ', $container_list_pieces);
                    $container_list = [
                        'id'             => $request_target,
                        'summary'        => $summary,
                        'volume'         => $volume,
                        'container'      => $rest,
                        'container_list' => $full_container_list,
                        'active'         => $active,
                        'inactive'       => $inactive,
                    ];
                    $container_lists[] = $container_list;
                }
            }
        }
        return $container_lists;
    }

    public function bioghistHead()
    {
        $contents_config = $this->config->get('contents');
        return $this->renderParagraphs($this->xpath($contents_config['bioghist_head']));
    }

    public function scopecontentHead()
    {
        $contents_config = $this->config->get('contents');
        return $this->renderParagraphs($this->xpath($contents_config['scopecontent_head']));
    }

    public function processinfoHead()
    {
        $contents_config = $this->config->get('contents');
        return $this->renderParagraphs($this->xpath($contents_config['processinfo_head']));
    }

    public function bioghist()
    {
        $contents_config = $this->config->get('contents');
        return $this->renderParagraphs($this->xpath($contents_config['bioghist']));
    }

    public function scopecontent()
    {
        $contents_config = $this->config->get('contents');
        return $this->renderParagraphs($this->xpath($contents_config['scopecontent']));
    }

    public function processinfo()
    {
        $contents_config = $this->config->get('contents');
        return $this->renderParagraphs($this->xpath($contents_config['processinfo']));
    }

    public function renderParagraphs($p_list)
    {
        $render = [];
        foreach ($p_list as $p) {
            $render[] = ['p' => fa_render($p)];
        }
        return $render;
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

    private function containerType($attributes)
    {
        if (isset($attributes['type'])) {
            $type = trim($attributes['type']);
            if ($type === 'othertype') {
                if (isset($attributes['label'])) {
                    return trim($attributes['label']);
                } else {
                    return 'container';
                }
            } else {
                return $type;
            }
        } elseif (isset($attributes['label'])) {
            return trim($attributes['label']);
        } else {
            return 'container';
        }
    }
}
