<?php
class Pages_Block_Pageslist extends Zikula_Controller_AbstractBlock
{
    /**
     * initialise block
     *
     * @author       Mark West
     * @version      $Revision: 434 $
     */
    public function init()
    {
        // Security
        SecurityUtil::registerPermissionSchema('Pages:pageslistblock:', 'Block title::');
    }

    /**
     * get information on block
     *
     * @author       Mark West
     * @version      $Revision: 434 $
     * @return       array       The block information
     */
    public function info()
    {
        return array('module'          => 'Pages',
                'text_type'       => $this->__('Pages list'),
                'text_type_long'  => $this->__('Display a list of pages'),
                'allow_multiple'  => true,
                'form_content'    => false,
                'form_refresh'    => false,
                'show_preview'    => true,
                'admin_tableless' => true);
    }

    /**
     * display block
     *
     * @author       Mark West
     * @version      $Revision: 434 $
     * @param        array       $blockinfo     a blockinfo structure
     * @return       output      the rendered bock
     */
    public function display($blockinfo)
    {
        // Security check
        if (!SecurityUtil::checkPermission('Pages:pageslistblock:', "$blockinfo[title]::", ACCESS_READ)) {
            return false;
        }

        // Get variables from content block
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // Defaults
        if (empty($vars['numitems'])) {
            $vars['numitems'] = 5;
        }

        // Check if the htmlpages module is available.
        if (!ModUtil::available('Pages')) {
            return false;
        }

        // Call the modules API to get the items
        $items = ModUtil::apiFunc('Pages', 'user', 'getall');

        // Check for no items returned
        if (empty($items)) {
            return;
        }

        // Call the modules API to get the numitems
        $countitems = ModUtil::apiFunc('Pages', 'user', 'countitems');

        // Compare the numitems with the block setting
        if ($countitems <= $vars['numitems']) {
            $vars['numitems'] = $countitems;
        }

        // Create output object
        $this->view->setCacheId($blockinfo['bid']);

        // Display each item, permissions permitting
        $shown_results = 0;
        $pagesitems = array();
        foreach ($items as $item) {
            if (SecurityUtil::checkPermission('Pages::', "{$item['title']}::{$item['pageid']}", ACCESS_OVERVIEW)) {
                $shown_results++;
                if ($shown_results <= $vars['numitems']) {
                    if (SecurityUtil::checkPermission('Pages::', "{$item['title']}::{$item['pageid']}", ACCESS_READ)) {
                        $pagesitems[] = array('url'   => ModUtil::url('Pages', 'user', 'display', array('pageid' => $item['pageid'])),
                                'title' => $item['title']);
                    } else {
                        $pagesitems[] = array('title' => $item['title']);
                    }
                }
            }
        }
        $this->view->assign('items', $pagesitems);

        // Populate block info and pass to theme
        $blockinfo['content'] = $this->view->fetch('block/pageslist.tpl');

        return BlockUtil::themeBlock($blockinfo);
    }

    /**
     * modify block settings
     *
     * @author       Mark West
     * @version      $Revision: 434 $
     * @param        array       $blockinfo     a blockinfo structure
     * @return       output      the bock form
     */
    public function modify($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // Defaults
        if (empty($vars['numitems'])) {
            $vars['numitems'] = 5;
        }

        // Create output object
        $this->view->setCaching(false);

        // assign the approriate values
        $this->view->assign($vars);

        // Return the output that has been generated by this function
        return $this->view->fetch('block/pageslist_modify.tpl');
    }

    /**
     * update block settings
     *
     * @author       Mark West
     * @version      $Revision: 434 $
     * @param        array       $blockinfo     a blockinfo structure
     * @return       $blockinfo  the modified blockinfo structure
     */
    public function update($blockinfo)
    {
        // Get current content
        $vars = BlockUtil::varsFromContent($blockinfo['content']);

        // alter the corresponding variable
        $vars['numitems'] = (int)FormUtil::getPassedValue('numitems', null, 'POST');

        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);

        // clear the block cache
        $this->view->clear_cache('block/pageslist.tpl');

        return $blockinfo;
    }
}