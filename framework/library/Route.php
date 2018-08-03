<?php
/* ========================================================================
 * 路由类
 * 主要功能,解析URL
 * ======================================================================== */
namespace  Framework\library;

class Route
{
    public $ctrl;
    public $action;
    public $module;
    public $path;
    public $route;
    public $addons;

    public function __construct()
    {
        $route = conf::all('route');
        $this->route=$route;
        $this->addons=$route['DEFAULT_ADDONS'];
        //如果路由是第二种模式
        if($route['PATH_INFO']==2  && !isset($_GET['m'])){
            $path=$this->analysisVar();
            $this->pathinfoTwo($route,$path);

        }else if($route['PATH_INFO']==3){ //如果路由是第三种模式
            $path=$this->analysisVar();
            if(!empty($path) && !isset($_GET['m'])){
                $this->pathinfoTwo($route,$path);
            }else{
                $this->pathinfoOne($route);
            }

        }else{ //如果路由是第一种模式
            $this->pathinfoOne($route);
        }

    }

    public function pathinfoOne($route)
    {
        global $_G,$_GPC;
        if(isset($_GPC['m'])){
            $this->module=$_GPC['m'];
        }else{
            $this->module=conf::get('DEFAULT_MODULE', 'route');
            $_GPC['m']=$this->module;
            $_GET['m']=$this->module;
        }

        if(isset($_GPC['c'])){
            $this->ctrl=$_GPC['c'];
        }else{
            $this->ctrl=conf::get('DEFAULT_CTRL', 'route');
            $_GPC['c']=$this->ctrl;
            $_GET['c']=$this->ctrl;
        }

        if(isset($_GPC['a'])){
            $this->action=$this->delSuffix($_GPC['a']);

        }else{
            $this->action=$this->delSuffix(conf::get('DEFAULT_ACTION', 'route'));
            $_GPC['a']=$this->action;
            $_GET['a']=$this->action;
        }



    }

    public function pathinfoTwo($route,$path)
    {
        global $_GPC;

        if (isset($_SERVER['REQUEST_URI'])) {
            if (isset($path[0]) && $path[0]) {
                $this->module = $path[0];
            } else {
                $this->module = $route['DEFAULT_MODULE'];
            }

            $_GET['m']=$this->module;
            $_GPC['m']=$this->module;
            unset($path[0]);
            if (isset($path[1]) && $path[1]) {
                $this->ctrl = $path[1];
            } else {
                $this->ctrl = $route['DEFAULT_CTRL'];
            }


            $_GET['c']=$this->ctrl;
            $_GPC['c']=$this->ctrl;
            unset($path[1]);

            //检测是否包含路由缩写
            if (@isset($route['ROUTE'][$this->ctrl])) {
                $this->action = $route['ROUTE'][$this->ctrl][2];
                $this->ctrl = $route['ROUTE'][$this->ctrl][1];
            } else {
                if (isset($path[2]) && $path[2]) {
                    $have = strstr($path[2], '?', true);
                    if ($have) {
                        $this->action = $have;
                    } else {
                        $this->action = $path[2];
                    }

                } else {
                    $this->action = $route['DEFAULT_ACTION'];
                }
                unset($path[2]);


            }

            $this->action=$this->delSuffix($this->action);
            $_GET['a']=$this->action;
            $_GPC['a']=$this->action;
            $this->path = array_merge($path);

            $pathLenth = count($path);
            /**
             * 最后一尾参数去掉后缀
             */
            if(@isset($this->path[$pathLenth-1])){
                $this->path[$pathLenth-1]=$this->delSuffix($this->path[$pathLenth-1]);
            }

            $i = 0;
            while ($i < $pathLenth) {
                if (isset($this->path[$i + 1])) {
                    $_GET[$this->path[$i]] = $this->path[$i + 1];
                    $_GPC[$this->path[$i]]=$this->path[$i + 1];
                }
                $i = $i + 2;
            }

        } else {

            $this->module = conf::get('DEFAULT_MODULE', 'route');
            $this->ctrl = conf::get('DEFAULT_CTRL', 'route');
            $this->action = $this->delSuffix(conf::get('DEFAULT_ACTION', 'route'));
            $_GPC['m']=$this->module;
            $_GET['m']=$this->module;
            $_GPC['c']=$this->ctrl;
            $_GET['c']=$this->ctrl;
            $_GPC['a']=$this->action;
            $_GET['a']=$this->action;
        }

    }
    /**
     * 解析url参数
     */
    public function analysisVar(){
        $pathStr = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI'],$count);
        $pathStr2 = str_replace($_SERVER['REQUEST_URI'], '', $_SERVER['SCRIPT_NAME'],$count2);
        //$path=@trim($pathStr,'?');
        $path=@trim($pathStr,'/');
        $str=substr($path,0,1);

        if($count < 1 && $count2 > 0 || $str=="&" || $str=="?"){
            $path=[];
        }else{
            $path = explode('/', $path);

            foreach ($path as $k=>$v){

                $str=substr($v,0,1);
                if($str=="&" || $str=="?"){
                    unset($path[$k]);
                }else{
                    /**
                     * 过滤参数中的？后面字符
                     */
                    $pos = strpos($v,"?");
                    if($pos){
                        $path[$k] = substr($v,0,$pos);
                    }
                }


            }
        }

        return $path;
    }

    /**
     * @param      $num
     * @param bool $default
     *
     * @return bool
     */
    public function urlVar($num, $default = false)
    {
        if (isset($this->path[$num])) {
            return $this->path[$num];
        } else {
            return $default;
        }
    }

    /**
     * @return mixed
     */
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 删除后缀
     */
    public function delSuffix($action){
        $route = conf::all('route');
        if($route['SUFFIX_STATUS']){
            $action=str_replace($route['SUFFIX'],'',$action);
        }
        return $action;

    }
}