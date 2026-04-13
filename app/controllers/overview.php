<?php
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
                'partials_loader' => new Mustache_Loader_CascadingLoader([
                    new Mustache_Loader_FilesystemLoader(
                        implode(DIRECTORY_SEPARATOR, [APP, 'views', 'layouts'])
                    ),
                    new Mustache_Loader_FilesystemLoader(
                        implode(DIRECTORY_SEPARATOR, [APP, 'views', 'shared'])
                    ),
                ]),
            ]);
            $page = $layout->render(
                load_template('layouts/overview'),
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
