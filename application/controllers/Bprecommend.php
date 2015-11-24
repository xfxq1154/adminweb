<?php
/**
 * 推荐
 *
 * @author yanbo
 */
class BpRecommendController extends Base{
    
    use Trait_Layout;
    
    
    function indexAction(){
        
        $this->layout("platform/recommend.phtml");
        
    }
    
    
    
}

