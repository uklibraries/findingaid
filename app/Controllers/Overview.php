<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Overview as OverviewModel;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

class Overview extends Controller
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

#        $m = new Mustache_Engine(array(
#            'partials_loader' => new Mustache_Loader_FilesystemLoader(
#                implode(
#                    DIRECTORY_SEPARATOR,
#                    array(
#                        APP,
#                        'views',
#                        'overview',
#                    )
#                )
#            ),
#        ));

        $model = new OverviewModel($this->params['id']);

        if ($model->exists) {
            $options = [
                'panels' => [],
                'title' => fa_brevity($model->title()),
            ];

            $css_hrefs = [
                "css/bootstrap.min.css",
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
                    implode(
                        DIRECTORY_SEPARATOR,
                        [
                            APP,
                            'Views',
                            'Layouts',
                        ]
                    )
                ),
            ]);
            $page = $layout->render(
                load_template('Layouts/overview'),
                [
                    'title' => $model->title(),
                    'bioghist' => $model->bioghist(),
                    'scopecontent' => $model->scopecontent(),
                    #'content' => $content,
                    'css' => $css,
                    #'title' => $model->title(),
                ]
            );
        }

        echo $page;
    }
}
