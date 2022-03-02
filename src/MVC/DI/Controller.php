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
use SPT\View\Theme;  
use SPT\View\View;  
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

    public function prepareTheme()
    {
        $viewPath = AppIns::path('plugin') ? AppIns::path('plugin'). $this->app->get('plugin'). '/views/' : AppIns::path('view');

        if(AppIns::path('theme') && $this->app->config->exists('theme'))
        {
            $themePath = AppIns::path('theme'). $this->app->config->theme. '/';
            $overrideLayouts = [
                $themePath. '__.php',
                $themePath. '__/index.php',
                $viewPath. '__.php',
                $viewPath. '__/index.php'
            ];
        }
        else
        {
            $themePath = $viewPath;
            $overrideLayouts = [
                $viewPath. '__.php',
                $viewPath. '__/index.php'
            ];
        }

        return new Theme($themePath, $overrideLayouts);
    }

    public function prepareView()
    {
        $this->view = new View();
        $this->view->init([
            $this->app->lang, 
            $this->prepareTheme()
        ]);
    }

    public function toHtml()
    {
        $this->prepareView();
        $layout = $this->app->get('layout', 'default');
        
        $data = $this->getAll(); 
        if(is_array($data) && count($data))
        {
            $this->view->setIndex($layout); // because we call set before render
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
            $this->view->setIndex($layout); // because we call set before render
            $this->view->set($data);
        }

        $this->app->response( $this->view->_render($layout) );
    }
}