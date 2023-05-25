<?php
/**
 * SPT software - Asset
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: An application context
 * 
 */

namespace SPT\Application\Simple;
 
use SPT\Router\ArrayEndpoint as Router;
use SPT\Request\Base as Request;
use SPT\Response;
use \Exception;
use SPT\Storage\File\ArrayType as FileArray;

class Web extends \SPT\Application\Base
{
    protected function envLoad()
    {
        // secrect key 
        $this->request = new Request();
        $this->router =  new Router($this->config->subpath);
    }

    public function execute(string $themePath = '')
    {
        $router = $this->router;
        $config = $this->getConfig();

        $this->plgLoad('routing', 'registerEndpoints', function ($endpoints) use ( $router ){
            $router->import($endpoints);
        });

        if($masterPlg = $config->master)
        {
            $this->plgRun($masterPlg, 'Routing', 'afterRegisterEndpoints');
        }

        try{

            $try = $router->parse($this->request);
            if(false === $try)
            {
                if($config->exists('pagenotfound'))
                {
                    $try = [$config->pagenotfound, []];
                }
                else
                {
                    $this->raiseError('Invalid request', 500);
                }
            }
    
            list($todo, $params) = $try;
            $try = explode('.', $todo);
            
            if(count($try) !== 3)
            {
                throw new Exception('Not correct routing', 500);
            } 
    
            if(count($params))
            {
                foreach($params as $key => $value)
                {
                    $this->set($key, $value);
                }
            }

            if($themePath)
            {
                $this->set('themePath', $themePath);
            }

            // support if this home - special deals
            if($router->get('isHome'))
            {
                $this->plgLoad('routing', 'isHome'); 
            }

            list($plugin, $controller, $function) = $try;
            $plugin = strtolower($plugin);
            $this->set('currentPlugin', $plugin); 
            
            return $this->plgDispatch($controller, $function);

        }
        catch (Exception $e) 
        {
            $this->raiseError('[Error] ' . $e->getMessage(), 500);
        }
    }
}