<?php
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
                        'views',
                        'findingaid',
                    ]
                )
            ),
        ]);

        $pieces = explode('_', (string) $this->params['id']);
        $id = $pieces[0];
        $component_id = $pieces[1];
        $model = new ComponentModel($id, $component_id);

        $container_list_template = load_template('findingaid/container_list');
        $component_template = load_template('findingaid/component');

        $container_lists = [];
        foreach ($model->container_lists() as $container_list) {
            $container_list_content = $m->render(
                $container_list_template,
                $container_list
            );
            $container_lists[] = [
                'container_list' => $container_list_content,
            ];
        }

        $subcomponents = $model->subcomponents();
        $subcomponent_content = [];
        foreach ($model->subcomponents() as $subcomponent) {
            $subcomponent_content[] = [
                'subcomponent' => $m->render(
                    $component_template,
                    [
                        'label' => fa_brevity($subcomponent->title()),
                        'collapsible' => true,
                        'bioghist_head' => $model->bioghistHead(),
                        'bioghist' => $subcomponent->bioghist(),
                        'scopecontent_head' => $model->scopecontentHead(),
                        'scopecontent' => $subcomponent->scopecontent(),
                        'processinfo_head' => $model->processinfoHead(),
                        'processinfo' => $subcomponent->processinfo(),
                    ]
                ),
            ];
        }

        $component_content = $m->render(
            $component_template,
            [
                'label' => fa_brevity($model->title()),
                'collapsible' => true,
                'container_lists' => $container_lists,
                'bioghist_head' => $model->bioghistHead(),
                'bioghist' => $model->bioghist(),
                'scopecontent_head' => $model->scopecontentHead(),
                'scopecontent' => $model->scopecontent(),
                'processinfo_head' => $model->processinfoHead(),
                'processinfo' => $model->processinfo(),
                'subcomponents' => $subcomponent_content,
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
