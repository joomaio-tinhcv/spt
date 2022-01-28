<?php
/**
 * SPT software - MVC Controller with simple DI
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: MVC Controller
 * 
 */

namespace SPT\MVC\DI;

use SPT\BaseObj;
use SPT\App\Adapter as Application;
use SPT\App\Instance as AppIns;

class Controller extends BaseObj
{
    protected $app;
    protected $view;

    public function __construct(Application $app)
    {
        $this->app = $app; 
    }

    public function prepareView()
    {
        if(AppIns::path('theme') && isset($this->app->config->theme))
        {
            $themePath = AppIns::path('theme'). $this->app->config->theme;
            $overrideLayouts = [
                $themePath. '__.php',
                $themePath. '__/index.php',
                AppIns::path('view'). '__.php',
                AppIns::path('view'). '__/index.php'
            ];
        }
        else
        {
            $themePath = AppIns::path('view');
            $overrideLayouts = [
                AppIns::path('view'). '__.php',
                AppIns::path('view'). '__/index.php'
            ];
        }
        
        $this->view = new View(
            $this->app->lang, 
            new SPT\Theme($themePath, $overrideLayouts)
        );
    }

    public function display()
    {
        $this->prepareView();
        $layout = $this->app->get('layout', 'default');
        
        $data = $this->getAll();
        if(is_array($data) && count($data))
        {
            $this->view->set($data);
        } 

        $page = $this->app->get('page', 'index');
        $this->app->response( $this->view->createPage($layout, $page) );
    }

    public function toJson($data=null)
    {
        header('Content-Type: application/json;charset=utf-8');
        if(null === $data) $data = $this->getAll();
        $this->app->response( $data );
    }

    public function toAjax()
    {
        $this->prepareView();
        $layout = $this->app->get('layout');

        $data = $this->getAll();
        if(is_array($data) && count($data))
        {
            $this->view->set($data);
        }

        $this->app->response( $this->view->_render($layout) );
    }
}