<?php
class Env{
    var $ACT=null;
    
    function __construct($obj){
        $this->ACT=$obj;
    }
    
    //加载后台环境
    function admin(){
        include(  DOCUROOT."/admin/dashboard/Config/userface.php" );
        $this->ACT->assign( "userface", USERFACE );
        
        $sitetpl = conf('global','tpl');
        $sitetpl = is_file(DOCUROOT."/template/{$sitetpl}/admin.html")?$sitetpl:'default';
        $this->ACT->parentTpl = DOCUROOT . "/template/{$sitetpl}/admin.html";
        $this->ACT->env="admin";
        
        //加载管理口插件
        $siteid= conf('global','tpl');
        $adminPluginFile = DOCUROOT."/plugins/{$siteid}/admin_home.php";
        if(is_file($adminPluginFile)){
            include $adminPluginFile;
            $obj = new admin_home(Action::$tplobj);
            $obj->init();
        }
    }
    
    //加载用户空间环境
    function space(){
        $sitetpl = conf('global','tpl');
        $this->ACT->parentTpl = DOCUROOT . "/template/{$sitetpl}/space.html";
        $this->ACT->env="space";
        
        //加载用户空间的标准数据
        $obj=load("panel_info");
        foreach($obj->loadSpaceInfo() as $key=>$item) $this->ACT->assign($key, $item);
        
    }
}
?>