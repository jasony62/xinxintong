<?php
class bbs_model extends TMS_MODEL {
    /**
     *
     */
    public function &subjects($mpid)
    {
        $q = array('s.*,m.nickname',
            'xxt_bbs_subject s,xxt_member m',
            "s.mpid='$mpid' and m.forbidden='N' and s.mpid=m.mpid and s.creater=m.mid"
        );
        $q2['o'] = 'reply_at desc, publish_at desc';
        $subjects = parent::query_objs_ss($q, $q2);

        return $subjects;
    }
    /**
     *
     */
    public function &replies($mpid, $sid)
    {
        $q = array('r.*,m.nickname',
            'xxt_bbs_reply r,xxt_member m',
            "sid=$sid and r.mpid='$mpid' and m.forbidden='N' and r.mpid=m.mpid and r.creater=m.mid");
        $q2['o'] = 'r.reply_at desc';
        $replies = parent::query_objs_ss($q, $q2);

        return $replies;
    }
    /**
     *
     */
    public function removeSubject($sid)
    {
        parent::delete('xxt_bbs_reply', "sid=$sid");
        parent::delete('xxt_bbs_subject', "sid=$sid");
        return true;
    }
    /**
     *
     */
    public function removeReply($rid)
    {
        return parent::delete('xxt_bbs_reply', "rid=$rid");
    }
}
