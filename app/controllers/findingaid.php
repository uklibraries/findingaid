<?php
class Findingaid extends Controller
{
    private $templates;

    public function __construct($params = array())
    {
        parent::__construct($params);
        $templates = array();
    }

    public function show()
    {
        $id = $this->params['id'];
        $cache = true;
        if (isset($this->params['invalidate_cache'])) {
            $cache = ($this->params['invalidate_cache'] != 1);
        }

        if ($cache && get_cache($id) && !(php_sapi_name() === 'cli')) {
            echo get_from_cache($id);
            return;
        }

        /* First, fill out top-level metadata, including the
         * table of contents.
         */
        $m = new Mustache_Engine(array(
            'partials_loader' => new Mustache_Loader_FilesystemLoader(
                implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        APP,
                        'views',
                        'findingaid',
                    )
                )
            ),
        ));

        $model = new FindingaidModel($this->params['id']);

        $options = array(
            'panels' => array(),
            'title' => fa_brevity($model->title()),
        );

        $toc_entries_unsorted = array();
        $toc_subentries = array();
        foreach ($this->config->get('panels') as $stub) {
            $panel = $stub;
            $panel['heading_id'] = "fa-heading-{$panel['id']}";
            $panel['body_id'] = "fa-body-{$panel['id']}";
            $skip = true;
            if (array_key_exists('field', $panel)) {
                $data = $model->xpath("//{$panel['field']}");
                foreach ($data as $datum) {
                    if (strlen(trim($datum)) > 0) {
                        $panel['single-field'] = trim($datum);
                        $skip = false;
                        break;
                    }
                }
            }
            elseif (array_key_exists('fields', $panel)) {
                $panel['multi-field'] = array();
                foreach ($panel['fields'] as $entry) {
                    $data = $model->xpath("//{$entry['field']}");
                    $metadata = array();
                    foreach ($data as $datum) {
                        if (strlen(trim($datum)) > 0) {
                            $metadata[] = array(
                                'content' => trim($datum),
                            );
                            $skip = false;
                        }
                    }
                    if (count($metadata) > 0) {
                        $panel['multi-field'][] = array(
                            'field_id' => "fa-fields-{$panel['body_id']}-{$entry['id']}",
                            'label' => fa_brevity($entry['label']),
                            'entries' => $metadata,
                        );
                        $skip = false;
                    }
                    if (array_key_exists('in_toc', $entry)) {
                        if ($entry['in_toc']) {
                            $toc_entry = array(
                                'label' => fa_brevity($entry['label']),
                                'id' => "fa-fields-{$panel['body_id']}-{$entry['id']}",
                            );
                            $toc_entries_unsorted[$entry['id']] = $toc_entry;
                        }
                    }
                }
            }
            else {
                $component_count = count($model->xpath('contents/c'));
                if ($component_count > 0) {
                    $skip = false;
                    $templates = array('container_list', 'component');
                    foreach ($templates as $template) {
                        $this->templates[$template] = load_template("findingaid/$template");
                    }
                    foreach ($model->xpath('contents/c') as $c) {
                        $details = $this->render_component($m, $c);
                        $panel['components'][] = array(
                            'component' => $details[0],
                        );
                        if ($details[1]['level'] === 'series') {
                            $attributes = $c->attributes();
                            $toc_subentries[] = $details[1]['metadata'];
                        }
                    }
                    $panel['contents_entries'] = array();
                    if (count($toc_subentries) > 0) {
                        $panel['subentries'] = true;
                        $panel['contents_entries'] = $toc_subentries;
                        $toc_subentries = array();
                    }
                }
            }

            if ($skip) {
                continue;
            }

            $in_toc = false;
            if (array_key_exists('in_toc', $panel)) {
                $in_toc = $panel['in_toc'];
                if ($in_toc) {
                    $toc_entry = array(
                        'label' => fa_brevity($panel['label']),
                        'id' => $panel['heading_id'],
                    );
                    if (isset($panel['subentries'])) {
                        $toc_entry['subentries'] = true;
                        $toc_entry['contents_entries'] = $panel['contents_entries'];
                    }
                    $toc_entries_unsorted[$panel['id']] = $toc_entry;
                }
            }

            $options['panels'][] = $panel;
        }

        $toc_config = $this->config->get('toc');
        $toc_entries = array();
        foreach ($toc_config['entries'] as $entry) {
            if (array_key_exists($entry, $toc_entries_unsorted)) {
                $toc_entry = $toc_entries_unsorted[$entry];
                $toc_entries[] = $toc_entry;
            }
        }

        $links = array();
        foreach ($toc_config['links'] as $link) {
            if (array_key_exists('skip', $link)) {
                if ($link['skip']) {
                    continue;
                }
            }
            if (array_key_exists('field', $link)) {
                if (array_key_exists('search_field', $link)) {
                    if ($model->repository() !== 'University of Kentucky') {
                        continue;
                    }
                    $search_field = $link['search_field'];
                    $data = $model->xpath("//{$link['field']}");
                    $raw_search = false;
                    foreach ($data as $datum) {
                        if (strlen(trim($datum)) > 0) {
                            $raw_search = trim($datum);
                            break;
                        }
                    }
                    $url = 'https://exploreuk.uky.edu/?' .
                           $search_field . '=' . urlencode($raw_search);
                }
                else {
                    $data = $model->xpath("//{$link['field']}");
                    $url = false;
                    foreach ($data as $datum) {
                        if (strlen(trim($datum)) > 0) {
                            $url = trim($datum);
                            break;
                        }
                    }
                }
                $links[] = array(
                    'label' => $link['label'],
                    'url' => $url,
                );
            }
        }

        $repository = $model->repository();
        $requestable = ($repository === 'University of Kentucky');

        $toc_component = false;
        if ($requestable and ($component_count == 0)) {
            $toc_component = array(
                'summary' => '',
                'id' => 'fa-no-components-request',
                'container_list' => fa_brevity($model->title()),
                'volume' => '',
                'container' => '',
            );
        }

        $toc_options = array(
            'id' => "fa-{$toc_config['id']}",
            'label' => fa_brevity($toc_config['label']),
            'entries' => $toc_entries,
            'links' => $links,
            'requestable' => $requestable,
            'toc_component' => $toc_component,
        );

        $toc = $m->render(
            load_template('findingaid/toc'),
            $toc_options
        );

        $content = $m->render(
            load_template('findingaid/show'),
            $options
        );

        if ($requestable) {
            $requests_config = $this->config->get('requests');
            $requests = $m->render(
                load_template('findingaid/requests'),
                array(
                    'id' => $requests_config['summary']['id'],
                    'label' => fa_brevity($requests_config['summary']['label']),
                    'list_id' => $requests_config['summary']['list_id'],
                    'title' => $this->cleanup($model->unittitle()),
                    'collection_id' => $model->id(),
                    'call_number' => $model->unitid(),
                    'item_date' => $model->unitdate(),
                    'item_url' => 'http://exploreuk.uky.edu/catalog/' . $model->id() . '/',
                )
            );
        }
        else {
            $requests = '';
        }

        $css_hrefs = array(
            "css/bootstrap.min.css",
            "css/jquery-ui.min.css",
            "css/extra.css",
            "css/footer.css",
            "css/lity.min.css",
            "css/mediaelementplayer.min.css",
        );

        $css = array();
        foreach ($css_hrefs as $href) {
            $css[] = array('href' => $href);
        }

        $layout = new Mustache_Engine(array(
            'partials_loader' => new Mustache_Loader_FilesystemLoader(
                implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        APP,
                        'views',
                        'layouts',
                    )
                )
            ),
        ));

        $page = $layout->render(
            load_template('layouts/application'),
            array(
                'content' => $content,
                'toc' => $toc,
                'requests' => $requests,
                'css' => $css,
                'js' => array(array(
                    'href' => 'js/app.js',
                    'hash' => hash_file('sha256', implode(
                        DIRECTORY_SEPARATOR,
                        array(
                            ROOT,
                            'public',
                            'js',
                            'app.js',
                        )
                    )),
                )),
                'title' => $model->title(),
                'requestable' => $requestable,
                'repository' => $this->config->get_repo($repository),
            )
        );
        set_cache($id, $page);
        if (php_sapi_name() === 'cli') {
            exit;
        }
        echo $page;
    }

    public function render_component($renderer, $component_xml)
    {
        $component_content = '';
        $attributes = $component_xml->attributes();
        if (isset($attributes['id'])) {
            $heading_id = "fa-heading-{$attributes['id']}";
            $body_id = "fa-body-{$attributes['id']}";
            $component = new ComponentModel($this->params['id'], $attributes['id']);
            $subcomponent_content = array();
            foreach ($component->subcomponents() as $subcomponent) {
                $subcomponent_details = $this->render_component($renderer, $subcomponent->xml());
                $subcomponent_content[] = array(
                    'subcomponent' => $subcomponent_details[0],
                );
            }

            $container_lists = array();
            foreach ($component->container_lists() as $container_list) {
                $container_list_content = $renderer->render(
                    $this->templates['container_list'],
                    $container_list
                );
                $container_lists[] = array(
                    'container_list' => $container_list_content,
                );
            }

            $component_content = $renderer->render(
                $this->templates['component'],
                array(
                    'label' => fa_brevity($component->title()),
                    'collapsible' => true,
                    'container_lists' => $container_lists,
                    'scopecontent' => $component->scopecontent(),
                    'links' => $component->links,
                    'subcomponents' => $subcomponent_content,
                    'heading_id' => $heading_id,
                    'body_id' => $body_id,
                )
            );
        }
        else {
            error_log("FA: attributes_id not set");
        }
        return array(
            $component_content,
            array(
                'level' => (string)$component->level(),
                'metadata' => array(
                    'label' => fa_brevity($component->title()),
                    'id' => $heading_id,
                ),
            ),
        );
    }

    private function cleanup($message)
    {
        return preg_replace('/\s+/', ' ', $message);
    }
}
