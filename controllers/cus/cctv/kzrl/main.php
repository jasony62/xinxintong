<?php
namespace cus\cctv\kzrl;

require_once dirname(dirname(dirname(dirname(__FILE__)))).'/xxt_base.php';
/**
 * 抗战日历 
 */
class main extends \xxt_base {
    /**
     *
     */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 求两个已知经纬度之间的距离,单位为米
     * @param lng1,lng2 经度
     * @param lat1,lat2 纬度
     * @return float 距离，单位米
     * @author www.Alixixi.com
    **/
    private function calcDistance($lng1, $lat1, $lng2, $lat2){
    	//将角度转为狐度
    	$radLat1 = deg2rad($lat1);//deg2rad()函数将角度转换为弧度
    	$radLat2 = deg2rad($lat2);
    	$radLng1 = deg2rad($lng1);
    	$radLng2 = deg2rad($lng2);
    	$a = $radLat1 - $radLat2;
    	$b = $radLng1 - $radLng2;
    	$s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2))) * 6378.137 * 1000;
        
    	return $s;
    }
    /**
     * 获得一篇文章的详细信息
     *
     * $articleid
     * $lat 纬度
     * $lng 经度
     */
    public function get_action($articleid, $lat=null, $lng=null)
    {
        $q = array(
            'a.title,a.summary,a.pic,a.body,a.weight,e.occured_time,e.occured_lat,e.occured_lng',
            'xxt_article a, xxt_article_extinfo e',
            "a.id=e.article_id"
        );
        
        $article = $this->model()->query_obj_ss($q);
        if ($lat !== null && $lng === null) {
            $distance = $this->calcDistance($lng, $lat, $article->occured_lng, $article->occured_lat);
            $article->distance = $distance;    
        }
        
        return new \ResponseData($article);
    }
    /**
     * 获得历史上今天发生的事件
     */
    public function today_action()
    {
        $current = time();
        
        $day = date('j', $current);
        $month = date('n', $current);
        
        $q = array(
            'a.title,a.summary,a.weight,e.occured_time',
            'xxt_article a, xxt_article_extinfo e',
            "a.id=e.article_id and e.occured_month=$month and e.occured_day=$day"
        );
        $q2 = array(
            'o'=>'e.occured_time desc'
        );
        
        $articles = $this->model()->query_objs_ss($q, $q2);
        
        return new \ResponseData($articles);
    }
    /**
     * 按照事件轴列出历史事件
     *
     * $articleid 以哪篇文章作为基准
     * $direction Backword|Two-way|Forward
     * $size 一个方向上的查找数量
     */
    public function timeline_action($articleid=null, $direction='T', $size=10)
    {
        $result = array();
        
        if ($articleid === null) {
            $start = time();
        } else {
            $q = array(
                'e.occured_time',
                'xxt_article_extinfo e',
                'e.article_id=$article_id'    
            );
            $start = $this->model()->query_val_ss($q);
        }
        $day = date('j', $start);
        $month = date('n', $start);
        
        /**
         * backwards
         */
        if ($direction === 'T' || $direction === 'B') {
            $q = array(
                'a.title,a.summary,a.weight,e.occured_time',
                'xxt_article a, xxt_article_extinfo e',
                "a.id=e.article_id and (e.occured_month<$month or (e.occured_month=$month and e.occured_day<=$day))"
            );
            $q2 = array(
                'o'=>'e.occured_time desc',
                'r'=>array('o'=>'0','l'=>$size)
            );
            
            if ($backwards = $this->model()->query_objs_ss($q, $q2)){
                $backwards = array_reverse($forward);
            }
            $result = $backwards;
        }
        /**
         * forwards
         */
        if ($direction === 'T' || $direction === 'F') {
            $q = array(
                'a.title,a.summary,a.weight,e.occured_time',
                'xxt_article a, xxt_article_extinfo e',
                "a.id=e.article_id and (e.occured_month>$month or (e.occured_month=$month and e.occured_day>$day))"
            );
            $q2 = array(
                'o'=>'e.occured_time asc',
                'r'=>array('o'=>'0','l'=>$size)
            );
            
            $forwards = $this->model()->query_objs_ss($q, $q2);
            
            $result = array_merge($result, $forwards);
        }
        
        return new \ResponseData($result);
    }
    /**
     * 查某一事件附近的事件
     */    
    public function nearby_action($articleid, $size=10)
    {
        $q = array(
            'a.title,a.summary,a.weight,e.occured_time,d.distance',
            'xxt_article a, xxt_article_extinfo e, xxt_article_ext_distance d',
            "a.id=$articleid and a.id=e.article_id and a.id=d.article_id_a"
        );
        $q2 = array(
            'o'=>'d.distance asc',
            'r'=>array('o'=>0,'l'=>$size)
        );
        
        $nearbys = $this->model()->query_objs_ss($q, $q2);
        
        return new \ResponseData($nearbys);
    }
    /**
     *
     */
    public function import_action($mpid='c4a663165e2198f7bb411ce30dca91e5', $cleanExistent = 'Y')
    {
        if ($cleanExistent === 'Y') {
            $this->model()->delete('xxt_article', "mpid='$mpid' and creater='import'");
            $this->model()->delete('xxt_article_extinfo', '1=1');
            $this->model()->delete('xxt_article_ext_distance', '1=1');
        }
        //solving: Maximum execution time of 30 seconds exceeded
        @set_time_limit(0);

        if (!($file = fopen($_FILES['kzrl']['tmp_name'], "r")))
            return new \ResponseError('open file, failed.');
        /**
         * handle data.
         */
        $current = time();
        for ($row = 0; ($record = fgetcsv($file)) != false; $row++) {
            $a = array(
                'mpid' => $mpid,
                'pic' => '', //本地的存储路径
                'url' => '',
                'title' => $record[1],
                'summary' => $record[2],
                'create_at' => $current,
                'modify_at' => $current,
                'body' => $record[5],
                'creater' => 'import',
                'creater_name' => 'import'
            );
            $articleid = $this->model()->insert('xxt_article', $a, true);
            $ei = array();
            /**
             * date
             */
            $occured_time = $record[3];
            $occured_time = str_replace(array('年','月'), '/', $occured_time);
            $occured_time = str_replace('日', '', $occured_time);
            list($year, $mon, $day) = explode('/', $occured_time);
            $occured_time = strtotime($year.'-'.$mon.'-'.$day);
            /**
             * location
             */
            $occured_point = trim($record[4]);
            $occured_point = str_replace(array('\'',' '), '', $occured_point);
            $occured_point = str_replace(array('北纬','南纬','东经','西经','°'), array('','-',',',',-','.'), $occured_point);
            list($lat, $lng) = explode(',', $occured_point);
            $ei = array(
                'article_id' => $articleid,
                'occured_time' => $occured_time,
                'occured_year' => $year,
                'occured_month' => $mon,
                'occured_day' =>$day,
                'occured_lat' => $lat,
                'occured_lng' => $lng    
            );
            $this->model()->insert('xxt_article_extinfo', $ei, false);
        }

        if (!feof($file)) {
            return new \ResponseError('unexpected fgets() fail.');
        }
        fclose($file);

        return new \ResponseData($row);
    }
    /**
     *
     */
    public function precalc_action($count=5)
    {
        //solving: Maximum execution time of 30 seconds exceeded
        @set_time_limit(0);

        $q = array(
            'e.article_id id,e.occured_lng lng,e.occured_lat lat',
            'xxt_article_extinfo e'  
        );
        $all = $this->model()->query_objs_ss($q);
        $all2 = $all;
        foreach ($all as $a) {
            foreach ($all2 as $b) {
                $d = $this->calcDistance($a->lng, $a->lat, $b->lng, $b->lat);
                $this->model()->insert(
                    'xxt_article_ext_distance', 
                    array('article_id_a'=>$a->id, 'article_id_b'=>$b->id, 'distance'=>$d)
                );
            }
        }
        
        return new \ResponseData('ok');
    }
}
