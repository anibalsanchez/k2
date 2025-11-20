<?php

/*
 * @package     k2-jx-ready
 *
 * @author      Extly, CB. <team@extly.com>
 * @copyright   Copyright (c)2025 Extly, CB. All rights reserved.
 * @license     https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * @see         https://www.extly.com
 *
 * Based on K2 by JoomlaWorks Ltd. See: https://github.com/getk2/k2
 */

// no direct access
defined('_JEXEC') || die;

class K2HelperUtilities
{
    // Get user avatar
    public static function getAvatar($userID, $email = null, $width = 50)
    {
        jimport('joomla.filesystem.folder');
        jimport('joomla.application.component.model');
        $app = Joomla\CMS\Factory::getApplication();
        $params = self::getParams('com_k2');
        $template = JRequest::getCmd('template');

        // Check for placeholder overrides
        if (isset($template) && Joomla\CMS\Filesystem\File::exists(JPATH_SITE.'/templates/'.$template.'/images/placeholder/user.png')) {
            $avatarPath = 'templates/'.$template.'/images/placeholder/user.png';
        } elseif (Joomla\CMS\Filesystem\File::exists(JPATH_SITE.'/templates/'.$app->getTemplate().'/images/placeholder/user.png')) {
            $avatarPath = 'templates/'.$app->getTemplate().'/images/placeholder/user.png';
        } else {
            $avatarPath = 'components/com_k2/images/placeholder/user.png';
        }

        // Continue with default K2 avatar determination
        if ($userID == 'alias') {
            $avatar = Joomla\CMS\Uri\Uri::root(true).'/'.$avatarPath;
        } elseif ($userID == 0) {
            if ($params->get('gravatar') && !is_null($email)) {
                $avatar = 'https://secure.gravatar.com/avatar/'.md5($email).'?s='.$width.'&amp;default='.urlencode(Joomla\CMS\Uri\Uri::root().$avatarPath);
            } else {
                $avatar = Joomla\CMS\Uri\Uri::root(true).'/'.$avatarPath;
            }
        } elseif (is_numeric($userID) && $userID > 0) {
            K2Model::addIncludePath(JPATH_SITE.'/components/com_k2/models');
            $model = K2Model::getInstance('Item', 'K2Model');
            $profile = $model->getUserProfile($userID);
            $avatar = (is_null($profile)) ? '' : $profile->image;
            if (empty($avatar)) {
                if ($params->get('gravatar') && !is_null($email)) {
                    $avatar = 'https://secure.gravatar.com/avatar/'.md5($email).'?s='.$width.'&amp;default='.urlencode(Joomla\CMS\Uri\Uri::root().$avatarPath);
                } else {
                    $avatar = Joomla\CMS\Uri\Uri::root(true).'/'.$avatarPath;
                }
            } else {
                $avatarTimestamp = '';
                $avatarFile = JPATH_SITE.'/media/k2/users/'.$avatar;
                if (is_file($avatarFile) && filemtime($avatarFile)) {
                    $avatarTimestamp = '?t='.date('Ymd_Hi', filemtime($avatarFile));
                }

                $avatar = Joomla\CMS\Uri\Uri::root(true).'/media/k2/users/'.$avatar.$avatarTimestamp;
            }
        }

        if (!$params->get('userImageDefault') && $avatar === Joomla\CMS\Uri\Uri::root(true).'/'.$avatarPath) {
            return '';
        }

        return $avatar;
    }

    public static function getCategoryImage($image, $params)
    {
        $app = Joomla\CMS\Factory::getApplication();
        $categoryImage = null;
        if (!empty($image)) {
            $catImageTimestamp = '';
            $catImageFile = JPATH_SITE.'/media/k2/categories/'.$image;
            if (is_file($catImageFile) && filemtime($catImageFile)) {
                $catImageTimestamp = '?t='.date('Ymd_Hi', filemtime($catImageFile));
            }

            $categoryImage = Joomla\CMS\Uri\Uri::root(true).'/media/k2/categories/'.$image.$catImageTimestamp;
        } elseif ($params->get('catImageDefault')) {
            if (is_file(JPATH_SITE.'/templates/'.$app->getTemplate().'/images/placeholder/category.png')) {
                $categoryImage = Joomla\CMS\Uri\Uri::root(true).'/templates/'.$app->getTemplate().'/images/placeholder/category.png';
            } else {
                $categoryImage = Joomla\CMS\Uri\Uri::root(true).'/components/com_k2/images/placeholder/category.png';
            }
        }

        return $categoryImage;
    }

    // Word limit
    public static function wordLimit($str, $limit = 100, $end_char = '&#8230;')
    {
        if (JString::trim($str) == '') {
            return $str;
        }

        // always strip tags for text
        $str = strip_tags($str);

        $find = ["/\r|\n/u", "/\t/u", "/\s\s+/u"];
        $replace = [' ', ' ', ' '];
        $str = preg_replace($find, $replace, $str);

        preg_match('/\s*(?:\S*\s*){'.(int) $limit.'}/u', $str, $matches);
        if (JString::strlen($matches[0]) == JString::strlen($str)) {
            $end_char = '';
        }

        return JString::rtrim($matches[0]).$end_char;
    }

    // Character limit
    public static function characterLimit($str, $limit = 150, $end_char = '...')
    {
        if (JString::trim($str) == '') {
            return $str;
        }

        // always strip tags for text
        $str = strip_tags(JString::trim($str));

        $find = ["/\r|\n/u", "/\t/u", "/\s\s+/u"];
        $replace = [' ', ' ', ' '];
        $str = preg_replace($find, $replace, $str);

        if (JString::strlen($str) > $limit) {
            $str = JString::substr($str, 0, $limit);

            return JString::rtrim($str).$end_char;
        }

        return $str;
    }

    // Cleanup HTML entities
    public static function cleanHtml($text)
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    // Gender
    public static function writtenBy($gender)
    {
        if (empty($gender) || $gender == 'n') {
            return Joomla\CMS\Language\Text::_('K2_WRITTEN_BY');
        }

        if ($gender == 'm') {
            return Joomla\CMS\Language\Text::_('K2_WRITTEN_BY_MALE');
        }

        if ($gender == 'f') {
            return Joomla\CMS\Language\Text::_('K2_WRITTEN_BY_FEMALE');
        }

        return null;
    }

    public static function setDefaultImage(&$item, $view, $params = null)
    {
        if ($view == 'item') {
            $image = 'image'.$item->params->get('itemImgSize');
            $item->image = $item->$image;
            switch ($item->params->get('itemImgSize')) {
                case 'XSmall':
                    $item->imageWidth = $item->params->get('itemImageXS');
                    break;
                case 'Small':
                    $item->imageWidth = $item->params->get('itemImageS');
                    break;
                case 'Medium':
                    $item->imageWidth = $item->params->get('itemImageM');
                    break;
                case 'Large':
                    $item->imageWidth = $item->params->get('itemImageL');
                    break;
                case 'XLarge':
                    $item->imageWidth = $item->params->get('itemImageXL');
                    break;
            }
        }

        if ($view == 'itemlist') {
            $image = 'image'.$params->get($item->itemGroup.'ImgSize');
            $item->image = $item->$image ?? '';
            switch ($params->get($item->itemGroup.'ImgSize')) {
                case 'XSmall':
                    $item->imageWidth = $item->params->get('itemImageXS');
                    break;
                case 'Small':
                    $item->imageWidth = $item->params->get('itemImageS');
                    break;
                case 'Medium':
                    $item->imageWidth = $item->params->get('itemImageM');
                    break;
                case 'Large':
                    $item->imageWidth = $item->params->get('itemImageL');
                    break;
                case 'XLarge':
                    $item->imageWidth = $item->params->get('itemImageXL');
                    break;
            }
        }

        if ($view == 'latest') {
            $image = 'image'.$params->get('latestItemImageSize');
            $item->image = $item->$image;
            switch ($params->get('latestItemImageSize')) {
                case 'XSmall':
                    $item->imageWidth = $item->params->get('itemImageXS');
                    break;
                case 'Small':
                    $item->imageWidth = $item->params->get('itemImageS');
                    break;
                case 'Medium':
                    $item->imageWidth = $item->params->get('itemImageM');
                    break;
                case 'Large':
                    $item->imageWidth = $item->params->get('itemImageL');
                    break;
                case 'XLarge':
                    $item->imageWidth = $item->params->get('itemImageXL');
                    break;
            }
        }

        if ($view == 'relatedByTag' && $params->get('itemRelatedImageSize')) {
            $image = 'image'.$params->get('itemRelatedImageSize');
            $item->image = $item->$image;
            switch ($params->get('itemRelatedImageSize')) {
                case 'XSmall':
                    $item->imageWidth = $item->params->get('itemImageXS');
                    break;
                case 'Small':
                    $item->imageWidth = $item->params->get('itemImageS');
                    break;
                case 'Medium':
                    $item->imageWidth = $item->params->get('itemImageM');
                    break;
                case 'Large':
                    $item->imageWidth = $item->params->get('itemImageL');
                    break;
                case 'XLarge':
                    $item->imageWidth = $item->params->get('itemImageXL');
                    break;
            }
        }
    }

    public static function getParams($option)
    {
        if (K2_JVERSION != '15') {
            $app = Joomla\CMS\Factory::getApplication();

            return $app->isSite() ? $app->getParams($option) : Joomla\CMS\Component\ComponentHelper::getParams($option);
        }

        return Joomla\CMS\Component\ComponentHelper::getParams($option);
    }

    public static function cleanTags($string, $allowed_tags)
    {
        $allowed_htmltags = [];
        foreach ($allowed_tags as $allowed_tag) {
            $allowed_htmltags[] .= '<'.$allowed_tag.'>';
        }

        $allowed_htmltags = implode('', $allowed_htmltags);
        $string = strip_tags($string, $allowed_htmltags);

        return $string;
    }

    // Clean HTML Tag Attributes
    // e.g. cleanupAttributes($string,"img,hr,h1,h2,h3,h4","style,width,height,hspace,vspace,border,class,id");
    public static function cleanAttributes($string, $tag_array, $attr_array)
    {
        $attr = implode('|', $attr_array);
        foreach ($tag_array as $tag) {
            preg_match_all(sprintf('#<(%s) .+?>#', $tag), $string, $matches, PREG_PATTERN_ORDER);
            foreach ($matches[0] as $match) {
                preg_match_all('/('.$attr.')=([\\"\\\']).+?([\\"\\\'])/', $match, $matchesAttr, PREG_PATTERN_ORDER);
                foreach ($matchesAttr[0] as $attrToClean) {
                    $string = str_replace($attrToClean, '', $string);
                    $string = preg_replace('|  +|', ' ', $string);
                    $string = str_replace(' >', '>', $string);
                }
            }
        }

        return $string;
    }

    public static function verifyRecaptcha()
    {
        $params = Joomla\CMS\Component\ComponentHelper::getParams('com_k2');
        $vars = [];
        $vars['secret'] = $params->get('recaptcha_private_key');
        $vars['response'] = $_POST['g-recaptcha-response'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($vars, '', '&'));
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $response = json_decode($result);

        return (bool) ($result && $info['http_code'] == 200 && is_object($response) && isset($response->success) && $response->success == true);
    }
}
