<?php
namespace mp\matter;

require_once dirname(__FILE__).'/matter_ctrl.php';
/**
 * This is the implementation of the server side part of
 * Resumable.js client script, which sends/uploads files
 * to a server in several chunks.
 *
 * The script receives the files in a standard way as if
 * the files were uploaded using standard HTML form (multipart).
 *
 * This PHP script stores all the chunks of a file in a temporary
 * directory (`temp`) with the extension `_part<#ChunkN>`. Once all 
 * the parts have been uploaded, a final destination file is
 * being created from all the stored parts (appending one by one).
 *
 * @author Gregory Chris (http://online-php.com)
 * @email www.online.php@gmail.com
 */
class resumable {
    
    private $mpid;
    
    public function __construct($mpid)
    {
        $this->mpid = $mpid;    
    }
    /**
     *
     * Logging operation - to a file (upload_log.txt) and to the stdout
     * @param string $str - the logging string
     */
    private function _log($str) {
        // log to the output
        //$log_str = date('d.m.Y').": {$str}\r\n";
        //echo $log_str;
    
        // log to file
        //if (($fp = fopen('upload_log.txt', 'a+')) !== false) {
        //    fputs($fp, $log_str);
        //    fclose($fp);
        //}
    }
    /**
     * 
     * Delete a directory RECURSIVELY
     * @param string $dir - directory path
     * @link http://php.net/manual/en/function.rmdir.php
     */
    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object); 
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
    /**
     *
     * Check if all the parts exist, and 
     * gather all the parts of the file together
     * @param string $temp_dir - the temporary directory holding all the parts of the file
     * @param string $fileName - the original file name
     * @param string $chunkSize - each chunk size (in bytes)
     * @param string $totalSize - original file size (in bytes)
     */
    private function createFileFromChunks($temp_dir, $articleid, $fileName, $chunkSize, $totalSize) 
    {
        // count all the parts of this file
        $total_files = 0;
        foreach(scandir($temp_dir) as $file) {
            if (stripos($file, $fileName) !== false) {
                $total_files++;
            }
        }
        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($total_files * $chunkSize >=  ($totalSize - $chunkSize + 1)) {
            // create the final destination file 
            if (($fp = fopen(self::UPLOAD_DIR.'article_'.$articleid.'_'.$fileName, 'w')) !== false) {
                for ($i=1; $i<=$total_files; $i++) {
                    fwrite($fp, file_get_contents($temp_dir.'/'.$fileName.'.part'.$i));
                    $this->_log('writing chunk '.$i);
                }
                fclose($fp);
            } else {
                $this->_log('cannot create the destination file');
                return false;
            }
            // rename the temporary directory (to avoid access from other 
            // concurrent chunks uploads) and than delete it
            if (rename($temp_dir, $temp_dir.'_UNUSED')) {
                $this->rrmdir($temp_dir.'_UNUSED');
            } else {
                $this->rrmdir($temp_dir);
            }
        }
    }
    /**
     * $articleid
     */
    public function handleRequest($articleid)
    {
        //check if request is GET and the requested chunk exists or not. this makes testChunks work
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $temp_dir = self::UPLOAD_DIR.$_GET['resumableIdentifier'];
            $chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
            if (file_exists($chunk_file)) {
                 header("HTTP/1.0 200 Ok");
            } else {
                 header("HTTP/1.0 404 Not Found");
            }
        }
        // loop through files and move the chunks to a temporarily created directory
        if (!empty($_FILES)) foreach ($_FILES as $file) {
        
            // check the error status
            if ($file['error'] != 0) {
                $this->_log('error '.$file['error'].' in file '.$_POST['resumableFilename']);
                continue;
            }
            // init the destination file (format <filename.ext>.part<#chunk>
            // the file is stored in a temporary directory
            $temp_dir = self::UPLOAD_DIR.$_POST['resumableIdentifier'];
            $dest_file = $temp_dir.'/'.$_POST['resumableFilename'].'.part'.$_POST['resumableChunkNumber'];
            // create the temporary directory
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }
            // move the temporary file
            if (!move_uploaded_file($file['tmp_name'], $dest_file)) {
                $this->_log('Error saving (move_uploaded_file) chunk '.$_POST['resumableChunkNumber'].' for file '.$_POST['resumableFilename']);
            } else {
                // check if all the parts present, and create the final destination file
                $this->createFileFromChunks($temp_dir, $articleid, $_POST['resumableFilename'], $_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
            }
        }
    }
}
class resumableSae {
    
    private $mpid;
    
    public function __construct($mpid)
    {
        $this->mpid = $mpid;    
    }
    /**
     *
     * Logging operation - to a file (upload_log.txt) and to the stdout
     * @param string $str - the logging string
     */
    private function _log($str) {
        // log to the output
        //$log_str = date('d.m.Y').": {$str}\r\n";
        //echo $log_str;
    
        // log to file
        //if (($fp = fopen('upload_log.txt', 'a+')) !== false) {
        //    fputs($fp, $log_str);
        //    fclose($fp);
        //}
    }
    /**
     *
     * Check if all the parts exist, and 
     * gather all the parts of the file together
     * @param string $temp_dir - the temporary directory holding all the parts of the file
     * @param string $fileName - the original file name
     * @param string $chunkSize - each chunk size (in bytes)
     * @param string $totalSize - original file size (in bytes)
     */
    private function createFileFromChunks($temp_dir, $articleid, $fileName, $chunkSize, $totalSize) 
    {
        $fs = \TMS_APP::M('fs/attachment', $this->mpid);
        // count all the parts of this file
        $total_files = 0;
        $rst = $fs->getListByPath($temp_dir);
        foreach($rst['files'] as $file) {
            if (stripos($file['Name'], $fileName) !== false) {
                $total_files++;
            }
        }
        // check that all the parts are present
        // the size of the last part is between chunkSize and 2*$chunkSize
        if ($total_files * $chunkSize >=  ($totalSize - $chunkSize + 1)) {
            // create the final destination file 
            $dest = 'article_'.$articleid.'_'.$fileName;
            $content = '';
            for ($i=1; $i<=$total_files; $i++) {
                $content .= $fs->read($temp_dir.'/'.$fileName.'.part'.$i);
                $fs->delete($temp_dir.'/'.$fileName.'.part'.$i);
                $this->_log('writing chunk '.$i);
            }
            $fs->write($dest, $content);
        }
    }
    /**
     * $articleid
     */
    public function handleRequest($articleid)
    {
        //check if request is GET and the requested chunk exists or not. this makes testChunks work
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $temp_dir = $_GET['resumableIdentifier'];
            $chunk_file = $temp_dir.'/'.$_GET['resumableFilename'].'.part'.$_GET['resumableChunkNumber'];
            if (file_exists($chunk_file)) {
                 header("HTTP/1.0 200 Ok");
            } else {
                 header("HTTP/1.0 404 Not Found");
            }
        }
        // loop through files and move the chunks to a temporarily created directory
        if (!empty($_FILES)) foreach ($_FILES as $file) {
            // check the error status
            if ($file['error'] != 0) {
                $this->_log('error '.$file['error'].' in file '.$_POST['resumableFilename']);
                continue;
            }
            // init the destination file (format <filename.ext>.part<#chunk>
            // the file is stored in a temporary directory
            $temp_dir = $_POST['resumableIdentifier'];
            $dest_file = $temp_dir.'/'.$_POST['resumableFilename'].'.part'.$_POST['resumableChunkNumber'];
            // move the temporary file
            $fs = \TMS_APP::M('fs/attachment', $this->mpid);
            if (!$fs->upload($dest_file, $file['tmp_name'])) {
                $this->_log('Error saving (move_uploaded_file) chunk '.$_POST['resumableChunkNumber'].' for file '.$_POST['resumableFilename']);
            } else {
                // check if all the parts present, and create the final destination file
                $this->createFileFromChunks($temp_dir, $articleid, $_POST['resumableFilename'], $_POST['resumableChunkSize'], $_POST['resumableTotalSize']);
            }
        }
    }
}
/*
*
*/
class article extends matter_ctrl {
    /*
    *
    */
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'white';
        $rule_action['actions'][] = 'hello';
        return $rule_action;
    }
    /**
     * 判断当前的图文是否允许编辑
     */
    public function view_action($path)
    {
        if (isset($_GET['id'])) {
            if ($creater = $this->model('matter\article')->byId($_GET['id'], 'creater'))
                \TPL::assign('creater', $creater);
        } else {
            \TPL::assign('mpaccount', $this->getMpaccount());
        }
        parent::view_action($path);
    }
    /**
     * 获得可见的图文列表
     *
     * $id article's id
     * $src p:从父账号检索图文
     * $id
     * $tag
     * $page
     * $size
     * $order
     *
     */
    public function index_action($id=null, $page=1, $size=30) 
    {
       return $this->get_action($id, $page, $size);
    }
    /**
     * 获得可见的图文列表
     *
     * $id article's id
     * $page
     * $size
     * post options
     * --$src p:从父账号检索图文
     * --$tag
     * --$channel
     * --$order
     *
     */
    public function get_action($id=null, $page=1, $size=30) 
    {
        $options = $this->getPostJson();
        
        if ($id) {
            $article = $this->getOne($this->mpid, $id);
            return new \ResponseData($article);
        } else {
            $uid = \TMS_CLIENT::get_client_uid();
            /**
             * 单图文来源 
             */
            $mpid = (!empty($options->src) && $options->src==='p') ? $this->getParentMpid() : $this->mpid;
            /**
             * select fields
             */
            $s = "a.id,a.mpid,a.title,a.summary,a.custom_body,a.create_at,a.modify_at,a.approved,a.creater,a.creater_name,a.creater_src,'$uid' uid";
            /**
             * where
             */
            $w = "a.mpid='$mpid' and a.state=1 and finished='Y'";
            /**
             * 限作者和管理员
             */
            if (!$this->model('mp\permission')->isAdmin($mpid, $uid, true)) {
                $fea = $this->model('mp\mpaccount')->getFeatures($mpid, 'matter_visible_to_creater');
                if ($fea->matter_visible_to_creater === 'Y')
                    $w .= " and (a.creater='$uid' or a.public_visible='Y')";
            }
            /**
             * 按频道过滤
             */
            if (!empty($options->channel)) {
                is_array($options->channel) && $options->channel = implode(',', $options->channel);
                $whichChannel = "exists (select 1 from xxt_channel_matter c where a.id = c.matter_id and c.matter_type='article' and c.channel_id in ($options->channel))";
                $w .= " and $whichChannel";
            }
            /**
             * 按标签过滤
             */
            if (empty($options->tag)) {
                $q = array(
                    $s, 
                    'xxt_article a', 
                    $w
                );
                if (!empty($options->order) && $options->order === 'title')
                    $q2['o'] = 'CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
                else 
                    $q2['o'] = 'a.modify_at desc';
            } else {
                /**
                 * 按标签过滤
                 */
                is_array($options->tag) && $options->tag = implode(',',$options->tag); 
                $w .= " and a.mpid=at.mpid and a.id=at.res_id and at.tag_id in($options->tag)";
                $q = array(
                    $s, 
                    'xxt_article a,xxt_article_tag at', 
                    $w
                );
                $q2['g'] = 'a.id';
                if ($options->order === 'title')
                    $q2['o'] = 'count(*),CONVERT(a.title USING gbk ) COLLATE gbk_chinese_ci';
                else 
                    $q2['o'] = 'count(*) desc,a.modify_at desc';
            }
            /**
             * limit
             */
            $q2['r'] = array('o'=>($page-1)*$size, 'l'=>$size);

            if ($articles = $this->model()->query_objs_ss($q, $q2)) {
                /**
                 * amount
                 */
                $q[0] = 'count(*)';
                $amount = (int)$this->model()->query_val_ss($q);
                /**
                 * 获得每个图文的tag
                 */
                foreach ($articles as &$a) {
                    $ids[] = $a->id;
                    $map[$a->id] = &$a;
                }
                $rels = $this->model('tag')->tagsByRes($ids, 'article');
                foreach ($rels as $aid => &$tags)
                    $map[$aid]->tags = $tags;
                return new \ResponseData(array($articles, $amount)); 
            }
            return new \ResponseData(array(array(),0));
        }
    }
    /**
     * 一个单图文的完整信息
     */
    private function &getOne($mpid, $id, $cascade=true) 
    {
        $uid = \TMS_CLIENT::get_client_uid();

        $pmpid = $this->getParentMpid();

        $q = array(
            "a.*,'$uid' uid",
            'xxt_article a',
            "(a.mpid='$mpid' or a.mpid='$pmpid') and a.state=1 and a.id=$id"
        );
        if (($article = $this->model()->query_obj_ss($q)) && $cascade===true) {
            /**
             * channels
             */
            $article->channels = $this->model('matter\channel')->byMatter($id, 'article');
            /**
             * tags
             */
            $article->tags = $this->model('tag')->tagsByRes($article->id, 'article');
            /**
             * acl
             */
            $article->acl = $this->model('acl')->byMatter($mpid, 'article', $id);
            /**
             * attachments
             */
            if ($article->has_attachment === 'Y')
                $article->attachments = $this->model()->query_objs_ss(array('*','xxt_article_attachment',"article_id='$id'"));
        }

        return $article;
    }
    /**
     * 图文的阅读情况
     */
    public function read_action($id)
    {
        $model = $this->model('matter\article');

        $reads = $model->readLog($id);

        return new \ResponseData($reads);
    }
    /**
     * 获得指定文章的所有评论
     *
     * $id article's id
     */
    public function remarks_action($id, $page=1, $size=30)
    {
        $range = array(
            'p'=>$page, 
            's'=>$size
        );
        $rst = $this->model('matter\article')->remarks($id, null, $range);

        return new \ResponseData($rst);
    }
    /**
     *
     */
    public function delRemark_action($id)
    {
        $rst = $this->model()->delete('xxt_article_remark', "id=$id");
        
        return new \ResponseData($rst);
    }
    /**
     *
     */
    public function cleanRemark_action($articleid)
    {
        $rst = $this->model()->delete('xxt_article_remark', "article_id=$articleid");
        
        return new \ResponseData($rst);
    }
    /**
     * 图文的统计数据
     */
    public function stat_action($id)
    {
        $model = $this->model('matter\article');
        $article = $model->byId($id);
        /**
         * 阅读次数
         */
        $stat['readNum'] = $model->readNum($id);
        /**
         * 赞的数量
         */
        $stat['score'] = $article->score;
        $stat['remark_num'] = $article->remark_num;

        return new \ResponseData($stat);
    }
    /**
     * 创建新图文
     */
    public function create_action()
    {
        $current = time();
        $d['mpid'] = $this->mpid;
        $d['creater'] = \TMS_CLIENT::get_client_uid();
        $d['creater_src'] = 'A';
        $d['creater_name'] = \TMS_CLIENT::account()->nickname;
        $d['create_at'] = $current;
        $d['modify_at'] = $current;
        $d['title'] = '新单图文';
        $d['pic'] = '';
        $d['hide_pic'] = 'N';
        $d['summary'] = '';
        $d['url'] = '';
        $d['body'] = '';
        $id = $this->model()->insert('xxt_article', $d);

        $article = $this->getOne($this->mpid, $id, false);

        return new \ResponseData($article);
    }
    /**
     * 更新单图文的字段
     *
     * $id article's id
     * $nv pair of name and value
     */
    public function update_action($id) 
    {
        $pmpid = $this->getParentMpid();

        $nv = (array)$this->getPostJson();

        isset($nv['body']) && $nv['body'] = $this->model()->escape(urldecode($nv['body']));

        $nv['modify_at'] = time();

        $rst = $this->model()->update(
            'xxt_article', 
            $nv,
            "(mpid='$this->mpid' or mpid='$pmpid') and id='$id'"
        );

        return new \ResponseData($rst);
    }
    /**
     *
     */
    public function upload_action($articleid)
    {
        if (defined('SAE_TMP_PATH'))
            $resumable = new resumableSae($this->mpid);
        else
            $resumable = new resumable($this->mpid);
            
        $resumable->handleRequest($articleid);
        exit;
    }
    /**
     * 添加附件
     */
    public function attachmentAdd_action($id)
    {
        $file = $this->getPostJson();
        /**
         * store to sae
         */
        $url = 'article_'.$id.'_'.$file->name;
        /**
         * store to local
         */
        $att = array();
        $att['article_id'] = $id;
        $att['name'] = $file->name;
        $att['type'] = $file->type;
        $att['size'] = $file->size;
        $att['last_modified'] = $file->lastModified;
        $att['url'] = $url;

        $att['id'] = $this->model()->insert('xxt_article_attachment', $att, true);

        $this->model()->update(
            'xxt_article', 
            array('has_attachment' => 'Y'),
            "id='$id'"
        );

        return new \ResponseData($att);
    }
    /**
     * 删除附件
     */
    public function attachmentDel_action($id)
    {
        $att = $this->model()->query_obj_ss(array('url','xxt_article_attachment', "id='$id'"));
        /**
         * remove from fs
         */
        $fs = $this->model('fs/attachment', $this->mpid);
        $fs->delete($att->url);
        /**
         * remove from local
         */
        $rst = $this->model()->delete('xxt_article_attachment', "id='$id'");

        if ($rst == 1) {
            $q = array(
                '1',
                'xxt_article_attachment',
                "id='$id'"
            );
            $cnt = $this->model()->query_val_ss($q);
            if ($cnt == 0)
                $this->model()->update(
                    'xxt_article', 
                    array('has_attachment' => 'N'),
                    "id='$id'"
                );
        }

        return new \ResponseData($rst);
    }
    /**
     * 删除一个单图文
     * 
     * 
     */
    public function remove_action($id)
    {
        $pmpid = $this->getParentMpid();

        $model = $this->model();
        
        $rst = $model->update(
            'xxt_article',
            array('state'=>0, 'modify_at'=>time()),
            "(mpid='$this->mpid' or mpid='$pmpid') and id='$id'");
        /**
         * 将图文从所属的多图文和频道中删除
         */    
        if ($rst) {
            $model->delete('xxt_channel_matter', "matter_id='$id' and matter_type='article'");
            $modelNews = $this->model('matter\news');
            if ($news = $modelNews->byMatter($id, 'article')) {
                foreach ($news as $n)
                    $modelNews->removeMatter($n->id, $id, 'article');
            }
        }    
        
        return new \ResponseData($rst);
    }
    /**
     * 添加图文的标签
     */
    public function addTag_action($id)
    {
        $tags = $this->getPostJson();

        $this->model('tag')->save(
            $this->mpid, $id, 'article', $tags, null);

        return new \ResponseData('success');
    }
    /**
     * 删除图文的标签
     */
    public function removeTag_action($id)
    {
        $tags = $this->getPostJson();

        $this->model('tag')->save(
            $this->mpid, $id, 'article', null, $tags
        );

        return new \ResponseData('success');
    }
    /**
     *
     */
    protected function getMatterType()
    {
        return 'article';
    }
}
