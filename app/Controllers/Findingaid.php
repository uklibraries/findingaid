<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Findingaid as FindingaidModel;
use App\Models\Component as ComponentModel;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class Findingaid extends Controller
{
    private $templates;

    public function __construct($params = [])
    {
        parent::__construct($params);
        $templates = [];
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
        $m = new Mustache_Engine([
            'partials_loader' => new Mustache_Loader_FilesystemLoader(
                implode(
                    DIRECTORY_SEPARATOR,
                    [
                        APP,
                        'Views',
                        'Findingaid',
                    ]
                )
            ),
        ]);

        $model = new FindingaidModel($this->params['id']);

        if ($model->exists) {
            $options = [
                'panels' => [],
                'title' => fa_brevity($model->title()),
            ];

            $toc_entries_unsorted = [];
            $toc_subentries = [];
            foreach ($this->config->get('panels') as $stub) {
                $panel = $stub;
                $panel['heading_id'] = "fa-heading-{$panel['id']}";
                $panel['body_id'] = "fa-body-{$panel['id']}";
                $skip = true;
                if (array_key_exists('field', $panel)) {
                    $data = $model->xpath("//{$panel['field']}");
                    foreach ($data as $datum) {
                        $content = trim((string) fa_render($datum));
                        if (strlen($content) > 0) {
                            $panel['single-field'] = $content;
                            $skip = false;
                            break;
                        }
                    }
                    if ($skip && array_key_exists('backup_field', $panel)) {
                        $data = $model->xpath("//{$panel['backup_field']}");
                        foreach ($data as $datum) {
                            $content = trim((string) fa_render($datum));
                            if (strlen($content) > 0) {
                                $panel['single-field'] = $content;
                                $skip = false;
                                break;
                            }
                        }
                    }
                } elseif (array_key_exists('fields', $panel)) {
                    $panel['multi-field'] = [];
                    foreach ($panel['fields'] as $entry) {
                        $data = $model->xpath("//{$entry['field']}");
                        $metadata = [];
                        foreach ($data as $datum) {
                            $content = trim((string) fa_render($datum));
                            if (strlen($content) > 0) {
                                $metadata[] = [
                                    'content' => $content,
                                ];
                                $skip = false;
                            }
                        }
                        if (count($metadata) > 0) {
                            $panel['multi-field'][] = [
                                'field_id' => "fa-fields-{$panel['body_id']}-{$entry['id']}",
                                'label' => fa_brevity($entry['label']),
                                'entries' => $metadata,
                            ];
                            $skip = false;
                            if (array_key_exists('in_toc', $entry)) {
                                if ($entry['in_toc']) {
                                    $toc_entry = [
                                        'label' => fa_brevity($entry['label']),
                                        'id' => "fa-fields-{$panel['body_id']}-{$entry['id']}",
                                    ];
                                    $toc_entries_unsorted[$entry['id']] = $toc_entry;
                                }
                            }
                        }
                    }
                } else {
                    $component_count = count($model->xpath('contents/c'));
                    if ($component_count > 0) {
                        $skip = false;
                        $templates = ['container_list', 'component'];
                        foreach ($templates as $template) {
                            $this->templates[$template] = load_template("Findingaid/$template");
                        }
                        foreach ($model->xpath('contents/c') as $c) {
                            $details = $this->renderComponent($m, $c);
                            $panel['components'][] = [
                                'component' => $details[0],
                            ];
                            if ($details[1]['level'] === 'series') {
                                $attributes = $c->attributes();
                                $toc_subentries[] = $details[1]['metadata'];
                            }
                        }
                        $panel['contents_entries'] = [];
                        if (count($toc_subentries) > 0) {
                            $panel['subentries'] = true;
                            $panel['contents_entries'] = $toc_subentries;
                            $toc_subentries = [];
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
                        $toc_entry = [
                            'label' => fa_brevity($panel['label']),
                            'id' => $panel['heading_id'],
                        ];
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
            $toc_entries = [];
            foreach ($toc_config['entries'] as $entry) {
                if (array_key_exists($entry, $toc_entries_unsorted)) {
                    $toc_entry = $toc_entries_unsorted[$entry];
                    $toc_entries[] = $toc_entry;
                }
            }

            $links = [];
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
                            if (strlen(trim((string) $datum)) > 0) {
                                $raw_search = trim((string) $datum);
                                break;
                            }
                        }
                        if (!$raw_search && array_key_exists('backup_field', $link)) {
                            $data = $model->xpath("//{$link['backup_field']}");
                            $raw_search = false;
                            foreach ($data as $datum) {
                                if (strlen(trim((string) $datum)) > 0) {
                                    $raw_search = trim((string) $datum);
                                    break;
                                }
                            }
                        }
                        $url = '/?' . $search_field . '=' . urlencode($raw_search);
                    } else {
                        $data = $model->xpath("//{$link['field']}");
                        $url = false;
                        foreach ($data as $datum) {
                            if (strlen(trim((string) $datum)) > 0) {
                                $url = trim((string) $datum);
                                break;
                            }
                        }
                        if (!$url && array_key_exists('backup_field', $link)) {
                            $data = $model->xpath("//{$link['backup_field']}");
                            $url = false;
                            foreach ($data as $datum) {
                                if (strlen(trim((string) $datum)) > 0) {
                                    $url = trim((string) $datum);
                                    break;
                                }
                            }
                        }
                    }
                    $links[] = [
                        'label' => $link['label'],
                        'url' => $url,
                    ];
                }
            }

            $repository = $model->repository();
            $requestable = ($repository === 'University of Kentucky');

            $toc_component = false;
            if ($requestable and ($component_count == 0)) {
                $toc_component = [
                    'summary' => '',
                    'id' => 'fa-no-components-request',
                    'container_list' => fa_brevity($model->title()),
                    'volume' => '',
                    'container' => '',
                ];
            }

            $toc_options = [
                'id' => "fa-{$toc_config['id']}",
                'label' => fa_brevity($toc_config['label']),
                'entries' => $toc_entries,
                'links' => $links,
                'requestable' => $requestable,
                'toc_component' => $toc_component,
            ];

            $toc = $m->render(
                load_template('Findingaid/toc'),
                $toc_options
            );

            $content = $m->render(
                load_template('Findingaid/show'),
                $options
            );

            if ($requestable) {
                $requests_config = $this->config->get('requests');
                $requests = $m->render(
                    load_template('Findingaid/requests'),
                    [
                        'id' => $requests_config['summary']['id'],
                        'label' => fa_brevity($requests_config['summary']['label']),
                        'list_id' => $requests_config['summary']['list_id'],
                        'title' => $this->cleanup($model->unittitle()),
                        'collection_id' => $model->id(),
                        'call_number' => $model->unitid(),
                        'item_date' => $model->unitdate(),
                        'item_url' => '/catalog/' . $model->id() . '/',
                    ]
                );
            } else {
                $requests = '';
            }

            $css_hrefs = [

                "css/jquery-ui.min.css",
                "css/extra.css",
                "css/footer.css",
                "css/lity.min.css",
                "css/mediaelementplayer.min.css",
            ];

            $css = [];
            foreach ($css_hrefs as $href) {
                $css[] = ['href' => $href];
            }

            $layout = new Mustache_Engine([
                'partials_loader' => new Mustache_Loader_FilesystemLoader(
                    implode(DIRECTORY_SEPARATOR, [APP, 'Views', 'Layouts'])
                ),
                new Mustache_Loader_FilesystemLoader(
                    implode(DIRECTORY_SEPARATOR, [APP, 'Views', 'Shared'])
                )
            ]);
            $page = $layout->render(
                load_template('Layouts/application'),
                [
                    'content' => $content,
                    'toc' => $toc,
                    'requests' => $requests,
                    'css' => $css,
                    'js' => [[
                        'href' => 'js/app.js',
                        'hash' => hash_file('sha256', implode(
                            DIRECTORY_SEPARATOR,
                            [
                                ROOT,
                                'public',
                                'js',
                                'app.js',
                            ]
                        )),
                    ]],
                    'title' => $model->title(),
                    'requestable' => $requestable,
                    'repository' => $this->config->getRepo($repository),
                ]
            );
            set_cache($id, $page);
        } else {
            $layout = new Mustache_Engine([
                'partials_loader' => new Mustache_Loader_CascadingLoader([
                    new Mustache_Loader_FilesystemLoader(
                        implode(DIRECTORY_SEPARATOR, [APP, 'Views', 'Layouts'])
                    ),
                    new Mustache_Loader_FilesystemLoader(
                        implode(DIRECTORY_SEPARATOR, [APP, 'Views', 'Shared'])
                    ),
                ]),
            ]);
            $meta = $this->config->getNonUK($id);
            if ($meta) {
                $repo = $meta['repository'];
                $former_kdl_partners = $this->config->get('partners');
                $is_kdl_partner = true;
                foreach ($former_kdl_partners as $partner) {
                    if ($partner['name'] === $repo) {
                        $is_kdl_partner = false;
                        $repo_url = $partner['url'];
                        break;
                    }
                }

                if ($is_kdl_partner) {
                    $page = $layout->render(
                        load_template('Layouts/suggest_kdl'),
                        [
                            'title' => $meta['title'],
                            'repository' => $meta['repository'],
                        ]
                    );
                } else {
                    $page = $layout->render(
                        load_template('Layouts/suggest_former_kdl'),
                        [
                            'title' => $meta['title'],
                            'repository' => $meta['repository'],
                            'repo_url' => $repo_url,
                        ]
                    );
                }
            } else {
                # This is probably a deleted ExploreUK finding aid
                header("Location: /");
                die();
            }
        }

        if (php_sapi_name() === 'cli') {
            exit;
        }
        echo $page;
    }

    public function renderComponent($renderer, $component_xml)
    {
        $component_content = '';
        $attributes = $component_xml->attributes();
        if (isset($attributes['id'])) {
            $heading_id = "fa-heading-{$attributes['id']}";
            $body_id = "fa-body-{$attributes['id']}";
            $component = new ComponentModel($this->params['id'], $attributes['id']);
            $subcomponent_content = [];
            foreach ($component->subcomponents() as $subcomponent) {
                $subcomponent_details = $this->renderComponent($renderer, $subcomponent->xml());
                $subcomponent_content[] = [
                    'subcomponent' => $subcomponent_details[0],
                ];
            }

            $container_lists = [];
            foreach ($component->containerLists() as $container_list) {
                $container_list_content = $renderer->render(
                    $this->templates['container_list'],
                    $container_list
                );
                $container_lists[] = [
                    'container_list' => $container_list_content,
                ];
            }

            $component_content = $renderer->render(
                $this->templates['component'],
                [
                    'label' => fa_brevity($component->title()),
                    'collapsible' => true,
                    'container_lists' => $container_lists,
                    'bioghist_head' => $component->bioghistHead(),
                    'bioghist' => $component->bioghist(),
                    'scopecontent_head' => $component->scopecontentHead(),
                    'scopecontent' => $component->scopecontent(),
                    'processinfo_head' => $component->processinfoHead(),
                    'processinfo' => $component->processinfo(),
                    'links' => $component->links,
                    'subcomponents' => $subcomponent_content,
                    'heading_id' => $heading_id,
                    'body_id' => $body_id,
                ]
            );
        } else {
            error_log("FA: attributes_id not set");
        }
        return [
            $component_content,
            [
                'level' => (string)$component->level(),
                'metadata' => [
                    'label' => fa_brevity($component->title()),
                    'id' => $heading_id,
                ],
            ],
        ];
    }

    private function cleanup($message)
    {
        return preg_replace('/\s+/', ' ', (string) $message);
    }
}
