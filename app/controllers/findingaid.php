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
            'title' => $model->title(),
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
                            'label' => $entry['label'],
                            'entries' => $metadata,
                        );
                        $skip = false;
                    }
                    if (array_key_exists('in_toc', $entry)) {
                        if ($entry['in_toc']) {
                            $toc_entry = array(
                                'label' => $entry['label'],
                                'id' => "fa-fields-{$panel['body_id']}-{$entry['id']}",
                            );
                            $toc_entries_unsorted[$entry['id']] = $toc_entry;
                        }
                    }
                }
            }
            else {
                if (count($model->xpath('contents/c')) > 0) {
                    $skip = false;
                    $panel['components'] = array();
                    $templates = array('container_list', 'component');
                    foreach ($templates as $template) {
                        $this->templates[$template] = load_template("findingaid/$template");
                    }
                    $container_list_template = load_template('findingaid/container_list');
                    $component_template = load_template('findingaid/component');
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
                        'label' => $panel['label'],
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

        $toc_options = array(
            'id' => "fa-{$toc_config['id']}",
            'label' => $toc_config['label'],
            'entries' => $toc_entries,
        );

        $toc = $m->render(
            load_template('findingaid/toc'),
            $toc_options
        );

        $content = $m->render(
            load_template('findingaid/show'),
            $options
        );

        $requests_config = $this->config->get('requests');
       # $unitid = $model->metadata('//archdesc/did/unitid');
       # if (count($unitid) > 0) {
       #     $unitid = $unitid[0];
       # }
       # print "<!-- hz $unitid -->\n";
        $requests = $m->render(
            load_template('findingaid/requests'),
            array(
                'id' => $requests_config['summary']['id'],
                'label' => $requests_config['summary']['label'],
                'list_id' => $requests_config['summary']['list_id'],
                'title' => $model->unittitle(),
                'collection_id' => $model->id(),
                'call_number' => $model->unitid(),
                'item_author' => $model->item_author(),
                'item_date' => $model->unitdate(),
                'item_url' => 'http://exploreuk.uky.edu/catalog/' . $model->id() . '/',
            )
        );

        $css_hrefs = array(
            "/fa/themes/bootstrap/css/bootstrap.min.css",
            "/fa/themes/bootstrap/js/jqueryui/jquery-ui.min.css",
            "/fa/themes/bootstrap/css/extra.css",
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
                'js' => array(array('href' => 'js/app.js')),
                'title' => $model->title(),
            )
        );
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
                    'label' => $component->title(),
                    'collapsible' => true,
                    'container_lists' => $container_lists,
                    'scopecontent' => $component->scopecontent(),
                    'subcomponents' => $subcomponent_content,
                    'heading_id' => $heading_id,
                    'body_id' => $body_id,
                )
            );
        }
        return array(
            $component_content,
            array(
                'level' => (string)$component->level(),
                'metadata' => array(
                    'label' => $component->title(),
                    'id' => $heading_id,
                ),
            ),
        );
    }
}
