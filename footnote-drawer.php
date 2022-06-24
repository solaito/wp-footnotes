<?php

/*
Plugin Name: Footnote Drawer
Plugin URI: https://github.com/solaito/footnote-drawer
Description: View Footnotes in the drawer.
Version: 1.0
Author: Tonica, LLC.
Author URI: https://tonica.llc/
License: A "Slug" license name e.g. GPL2
*/

define("FOOTNOTE_DRAWER_PLUGIN", __FILE__);
define("FOOTNOTE_DRAWER_PLUGIN_BASENAME", plugin_basename(FOOTNOTE_DRAWER_PLUGIN));
define("FOOTNOTE_DRAWER_PLUGIN_DIR_URL", plugin_dir_url(FOOTNOTE_DRAWER_PLUGIN));

add_action('wp_enqueue_scripts', 'footnote_drawer_enqueue_scripts');
function footnote_drawer_enqueue_scripts()
{
    $data = get_file_data(FOOTNOTE_DRAWER_PLUGIN, array('version' => 'Version'));
    $version = $data['version'];
    $footnote_drawer = array(
        'plugin' => array(
            'text' => array(
                'footnotes' => __('Footnotes'),
            )
        )
    );
    wp_enqueue_script('footnote-drawer', FOOTNOTE_DRAWER_PLUGIN_DIR_URL . 'includes/js/index.js', null, $version);
    wp_localize_script('footnote-drawer', 'footnote_drawer', $footnote_drawer);
    wp_enqueue_style('footnote_drawer', FOOTNOTE_DRAWER_PLUGIN_DIR_URL . 'includes/css/style.css', null, $version);
}

class FootnoteDrawer
{
    private $footnotes;

    /*
     * MEMO:
     * the_content -> shortcodeの順で処理されるのでトリッキーなことをしている
     * 以下の処理順でコンテンツの末尾に文末脚注を差し込む
     * ・the_contentでコンテンツ末尾に文末脚注用のショートコードを追加
     * ・それぞれの脚注をショートコードで拾い、クラス変数に格納する
     * ・文末脚注用のショートコードでそれぞれの脚注をまとめてリスト化
     */
    public function __construct()
    {
        // the_postが最初に呼び出されるのでそのタイミングで初期化
        add_filter('the_post', array($this, 'init'));
        add_filter('the_content', array($this, 'add_temp_endnotes_filter'));
        add_shortcode('fnd', array($this, 'footnote_callback'));
        add_shortcode('fnd_end', array($this, 'endnotes_callback'));
    }

    public function init()
    {
        $this->footnotes = [];
    }

    public function footnote_callback($atts, $content = null)
    {
        $n = count($this->footnotes) + 1;
        $id_prefix = 'footnote-drawer-post-'. get_the_ID();
        $id = $id_prefix . '-' . $n;
        $ref_id = $id_prefix . '-ref-' . $n;
        array_push($this->footnotes,
            array(
                'id' => $id,
                'ref_id' => $ref_id,
                'content' => $content
            ));
        $a = sprintf('<a href="#%s">[%d]</a>', $id, $n );
        return sprintf('<sup id="%s" class="footnote-drawer-reference" data-footnote-drawer-number="%s" data-footnote-drawer-to="%s">%s</sup>',
            $ref_id, $n, $id, $a);
    }

    public function endnotes_callback($atts, $content = null)
    {
        // 脚注が登録されていなければ文末脚注も不要
        if (empty($this->footnotes)) {
            return;
        }
        $lis = '';
        foreach ($this->footnotes as $footnote) {
            $jump_link = sprintf('<b class="footnote-drawer-scroll-up""><a href="#%s">^</a></b>', $footnote['ref_id']);
            $content = sprintf('<span class="footnote-drawer-endnotes-contents">%s</span>', $footnote['content']);
            $lis .= sprintf('<li id="%s">%s%s</li>', $footnote['id'], $jump_link, $content);
        }

        return sprintf('<h2>%s</h2><ol class="footnote-drawer-endnotes">%s</ol>', __('Footnotes'), $lis);
    }

    public function add_temp_endnotes_filter($content)
    {
        return $content . '[fnd_end]';
    }
}

new FootnoteDrawer();