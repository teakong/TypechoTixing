<?php
/**
 * 聚合推送，将博客动态发送提醒通知到手机上（微信，钉钉、飞书等方式）。
 * 
 * @package TypechoTixing
 * @author teakong
 * @version 1.0.0
 * @link http://www.phprm.com/TypechoTixing/
 */
class TypechoTixing_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
    
        Typecho_Plugin::factory('Widget_Feedback')->comment = array('TypechoTixing_Plugin', 'sc_send');
        Typecho_Plugin::factory('Widget_Feedback')->trackback = array('TypechoTixing_Plugin', 'sc_send');
        Typecho_Plugin::factory('Widget_XmlRpc')->pingback = array('TypechoTixing_Plugin', 'sc_send');
        
        return _t('请配置此插件的 口令, 以使您的微信、飞书、钉钉推送生效');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $key = new Typecho_Widget_Helper_Form_Element_Text('channel_code', NULL, NULL, _t('口令：'), _t('需要关注 <a href="https://www.phprm.com/push/h5/">一封传话</a> 公众号<br />
        同时，关注后需要在 <a href="https://www.phprm.com/push/h5/todo_create.html">口令提醒</a> 点击图标创建一个口令提醒, 然后“复制网址”粘贴到这里'));
        $form->addInput($key->addRule('required', _t('您必须填写一个正确的 口令')));
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 微信推送
     * 
     * @access public
     * @param array $comment 评论结构
     * @param Typecho_Widget $post 被评论的文章
     * @return void
     */
    public static function sc_send($comment, $post)
    {
        $options = Typecho_Widget::widget('Widget_Options');

        $channel_code = $options->plugin('TypechoTixing')->channel_code;

        $text = ($comment['author'] ? $comment['author'] : "有人")."在".($post->title ? $post->title : "您的博客")."发表了评论";
        
		// 时区配置不生效时读取时区: timezone配置数字(向后偏移秒才能得到东八区的时间)
		$desp = date('H:i:s', $comment['created'] + $options->timezone)."  ".$comment['created'].$comment['text'];
        $postdata = json_encode(
            array(
                'head' => $text,
                'body' => $desp
                )
            );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => $postdata
                )
            );
        $context  = stream_context_create($opts);
		if(strpos($channel_code, "http") === 0) {
			$api_url = $channel_code;
		}else{
			$api_url = 'https://www.phprm.com/services/push/trigger/'.$channel_code;
		}
        $result = file_get_contents($api_url, false, $context);
        return  $comment;
    }
}
