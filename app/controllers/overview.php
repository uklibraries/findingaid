<?php
class Overview extends Controller
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
            $options = array(
                'panels' => array(),
                'title' => fa_brevity($model->title()),
            );

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
                load_template('layouts/overview'),
                array(
                    'title' => $model->title(),
                    'bioghist' => $model->bioghist(),
                    'scopecontent' => $model->scopecontent(),
                    #'content' => $content,
                    'css' => $css,
                    #'title' => $model->title(),
                )
            );
        }

        echo $page;
    }
}
