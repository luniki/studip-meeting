<?php

/*
 * Stud.IP Video Conference Services Integration
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Gl�ggler <till.gloeggler@elan-ev.de>
 * @author      Christian Flothmann <christian.flothmann@uos.de>
 * @copyright   2011-2014 ELAN e.V. <http://www.elan-ev.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */

// load legacy code for older Stud.IP-Versions
require_once 'vendor/flexi/flexi.php';
#$GLOBALS['template_factory_sb'] = new \Flexi_TemplateFactory(dirname(__FILE__) . '/compat/2.3/sidebar/templates');

require_once 'compat/2.3/StudipArrayObject.php';
require_once 'compat/2.3/Meetings_SimpleCollection.php';
require_once 'compat/2.3/Meetings_SimpleORMapCollection.php';
require_once 'compat/2.3/Meetings_SimpleORMap.php';
require_once 'compat/2.3/Course.class.php';
#require_once 'compat/2.3/Institute.class.php';

#spl_autoload_register(function($class) {
#    require_once 'compat/2.3/sidebar/'. $class . '.php';
#});

require_once __DIR__.'/vendor/autoload.php';

use ElanEv\Model\CourseConfig;
use ElanEv\Model\MeetingCourse;

class MeetingPlugin extends StudipPlugin implements StandardPlugin, SystemPlugin
{
    const NAVIGATION_ITEM_NAME = 'video-conferences';

    private $assetsUrl;

    public function __construct() {
        parent::__construct();

        $this->assetsUrl = rtrim($this->getPluginURL(), '/').'/assets';

        /** @var \Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];
        if ($perm->have_perm('root')) {
            $item = new Navigation(_('Meeting�bersicht'), PluginEngine::getLink($this, array('cid' => null), 'index/all'));
            $item->setImage($GLOBALS['ASSETS_URL'].'/images/icons/16/white/chat.png');

            if (Navigation::hasItem('/admin/locations')) {
                Navigation::addItem('/admin/locations/meetings', $item);
            } else {
                Navigation::addItem('/admin/config/all_meetings', $item);
            }

            $item = new Navigation(_('Meetings konfigurieren'), PluginEngine::getLink($this, array(), 'admin/index'));
            $item->setImage($GLOBALS['ASSETS_URL'].'/images/icons/16/white/chat.png');
            Navigation::addItem('/admin/config/meetings', $item);
        } elseif ($perm->have_perm('dozent')) {
            $item = new Navigation(_('Meine Meetings'), PluginEngine::getLink($this, array(), 'index/my'));
            //$item->setImage($GLOBALS['ASSETS_URL'].'/images/icons/16/white/chat.png');
            //Navigation::addItem('/meetings', $item);
	        Navigation::addItem('/profile/meetings', $item);
        }

        // do nothing if plugin is deactivated in this seminar/institute
        if (!$this->isActivated()) {
            return;
        }
        
        $navigation = $this->getTabNavigation(Request::get('cid', $GLOBALS['SessSemName'][1]));
        Navigation::insertItem('/course/'.self::NAVIGATION_ITEM_NAME, $navigation['video-conferences'], null);
    }

	public function getPluginName()
	{
		return _('Meetings (Beta)');
	}

    public function getInfoTemplate($course_id) {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIconNavigation($courseId, $lastVisit, $userId = null)
    {
        /** @var Seminar_Perm $perm */
        $perm = $GLOBALS['perm'];

        if ($perm->have_studip_perm('tutor', $courseId)) {
            $courses = MeetingCourse::findByCourseId($courseId);
        } else {
            $courses = MeetingCourse::findActiveByCourseId($courseId);
        }

        $recentMeetings = 0;

        foreach ($courses as $meetingCourse) {
            if ($meetingCourse->course->mkdate >= $lastVisit) {
                $recentMeetings++;
            }
        }

        $courseConfig = CourseConfig::findByCourseId($courseId);
        $navigation = new Navigation($courseConfig->title, PluginEngine::getLink($this, array(), 'index'));

        if ($recentMeetings > 0) {
            $navigation->setImage('icons/20/red/chat.png', array(
                'title' => sprintf(_('%d Meeting(s), %d neue'), count($courses), $recentMeetings),
            ));
        } else {
            $navigation->setImage('icons/16/grey/chat.png', array(
                'title' => sprintf(_('%d Meeting(s)'), count($courses)),
            ));
        }

        return $navigation;
    }

    /* interface method */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNavigation($courseId)
    {
        $courseConfig = CourseConfig::findByCourseId($courseId);
        $main = new Navigation($courseConfig->title);
        $main->setURL(PluginEngine::getURL($this, array(), 'index'));
        $main->setImage('icons/16/white/chat.png', array('title', $courseConfig->title));

        return array(self::NAVIGATION_ITEM_NAME => $main);
    }

    //TODO: show error message
    public function error(){
        return null;
    }

    /**
     * {@inheritdoc}
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath().'/app';
        $dispatcher = new Trails_Dispatcher($trails_root, PluginEngine::getUrl($this, array(), 'index'), 'index');
        $dispatcher->dispatch($unconsumed_path);

    }

    public function getAssetsUrl()
    {
        return $this->assetsUrl;
    }
}
