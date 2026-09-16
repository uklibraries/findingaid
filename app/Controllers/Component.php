<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Component as ComponentModel;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class Component extends Controller
{
    public function __construct($params = [])
    {
        parent::__construct($params);
    }

    public function show()
    {
        $result = $this->render();
        echo $result[0];
    }

    public function render()
    {
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

        $pieces = explode('_', (string) $this->params['id']);
        $id = $pieces[0];
        $component_id = $pieces[1];
        $model = new ComponentModel($id, $component_id);

        $container_list_template = load_template('Findingaid/container_list');
        $component_template = load_template('Findingaid/component');

        $container_lists = [];
        foreach ($model->containerLists() as $container_list) {
            $container_list_content = $m->render(
                $container_list_template,
                $container_list
            );
            $container_lists[] = [
                'container_list' => $container_list_content,
            ];
        }

        $subcomponent_content = [];
        foreach ($model->subcomponents() as $subcomponent) {
            $subcomponent_content[] = [
                'subcomponent' => $m->render(
                    $component_template,
                    [
                        'label' => fa_brevity($subcomponent->title()),
                        'collapsible' => count($subcomponent->subcomponents()) > 0,
                        'links' => $subcomponent->links,
                        'has_media' => !empty($subcomponent->links),
                        'has_image_overflow' => $subcomponent->has_image_overflow,
                        'bioghist_head' => $subcomponent->bioghistHead(),
                        'bioghist' => $subcomponent->bioghist(),
                        'scopecontent_head' => $subcomponent->scopecontentHead(),
                        'scopecontent' => $subcomponent->scopecontent(),
                        'processinfo_head' => $subcomponent->processinfoHead(),
                        'processinfo' => $subcomponent->processinfo(),
                        'heading' => fa_heading_context(4),
                        'note_heading' => fa_heading_context(5),
                    ]
                ),
            ];
        }

        $component_content = $m->render(
            $component_template,
            [
                'label' => fa_brevity($model->title()),
                'collapsible' => !empty($subcomponent_content),
                'links' => $model->links,
                'has_media' => !empty($model->links),
                'has_image_overflow' => $model->has_image_overflow,
                'container_lists' => $container_lists,
                'has_container_lists' => !empty($container_lists),
                'bioghist_head' => $model->bioghistHead(),
                'bioghist' => $model->bioghist(),
                'scopecontent_head' => $model->scopecontentHead(),
                'scopecontent' => $model->scopecontent(),
                'processinfo_head' => $model->processinfoHead(),
                'processinfo' => $model->processinfo(),
                'subcomponents' => $subcomponent_content,
                'heading' => fa_heading_context(3),
                'note_heading' => fa_heading_context(4),
            ]
        );

        return [
            $component_content,
            [
                'level' => (string)$model->level(),
                'metadata' => [
                    'label' => fa_brevity($model->title()),
                    'id' => 'demo_id',
                ],
            ],
        ];
    }
}
