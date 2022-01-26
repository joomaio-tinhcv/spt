<?php
/**
 * SPT software - PHP Session
 * 
 * @project: https://github.com/smpleader/spt
 * @author: Pham Minh - smpleader
 * @description: PHP Session
 * 
 */

namespace SPT\Session;

use SPT\Session\Adapter SessionAdapter;
use SPT\Query;

class DatabaseSession implements SessionAdapter
{
    private $session = array();
    private $table = '';

    public function __construct(string $table = 'spt_session')
    {
        $this->table = $table;
        $this->reload();
    }

    public function reload()
    {
        // connect the database and setup
    }

    public function get(string $key)
    {
        return isset($this->session[$key]) ? $this->session[$key] : null;
    }
    
    public function set(string $key, $value)
    {
        $this->session[$key] = $value;
    }
}