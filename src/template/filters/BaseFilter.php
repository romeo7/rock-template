<?php
namespace rock\template\filters;

use rock\base\ClassName;
use rock\date\DateTime;
use rock\di\Container;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\helpers\Json;
use rock\helpers\Serialize;
use rock\image\ImageProvider;
use rock\image\ThumbInterface;
use rock\Rock;
use rock\template\Html;
use rock\template\Template;
use rock\url\Url;

class BaseFilter
{
    use className;

    /**
     * Unserialize.
     *
     * @param string $value  serialized array
     * @param array  $params params:
     *
     * - key
     * - separator
     *
     * @return string
     */
    public static function unserialize($value, array $params)
    {
        if (empty($value)) {
            return null;
        }
        if (!empty($params['key'])) {
            return ArrayHelper::getValue(
                Serialize::unserialize($value, false),
                explode(Helper::getValue($params['separator'], '.'), $params['key'])
            );
        }

        return Serialize::unserialize($value, false);
    }

    /**
     * Replace variables template (`chunk`, `snippet`...).
     *
     * @param string   $content content
     * @param array    $placeholders
     * @param Template $template
     * @return string
     */
    public static function replaceTpl($content, array $placeholders = null, Template $template)
    {
        $template = clone $template;
        $template->removeAllPlaceholders();

        return $template->replace($content, $placeholders);
    }


    /**
     * Modify date.
     *
     * @param string $date   date
     * @param array  $params params:
     *
     * - format: date format
     * - locale: date locale.
     *
     * @return string|null
     */
    public static function modifyDate($date, array $params = [])
    {
        if (empty($date)) {
            return null;
        }
        $params['config'] = Helper::getValue($params['config'], []);
        if (!empty($params['locale'])) {
            $params['config']['locale'] = $params['locale'];
        }
        $dateTime = DateTime::set($date, null, $params['config']);
        if (isset($params['timezone'])) {
            $dateTime->convertTimezone($params['timezone']);
        }
        return $dateTime->format(Helper::getValue($params['format']));
    }


    /**
     * Modify url.
     *
     * @param string $url
     * @param array  $params params:
     *
     * - args:        URL-arguments for set.
     * - csrf:        adding CSRF-token.
     * - addArgs:       URL-arguments for adding.
     * - removeArgs:      URL-arguments for removing.
     * - removeAllArgs:   remove all URL-arguments.
     * - beginPath:     string to begin of URL-path.
     * - endPath:       string to end of URL-path.
     * - replace:       the replacement data.
     * - anchor:       anchor for adding.
     * - removeAnchor:       remove anchor.
     * - const: adduce URL to: {@see \rock\url\Url::ABS}, {@see \rock\url\Url::HTTP},
     *                  and {@see \rock\url\Url::HTTPS}.
     * @return string
     */
    public static function modifyUrl($url, array $params = [])
    {
        if (empty($url)) {
            return '#';
        }
        $urlBuilder = Url::set($url);
        if (isset($params['removeAllArgs'])) {
            $urlBuilder->removeAllArgs();
        }
        if (isset($params['removeArgs'])) {
            $urlBuilder->removeArgs($params['removeArgs']);
        }
        if (isset($params['removeAnchor'])) {
            $urlBuilder->removeAnchor();
        }
        if (isset($params['beginPath'])) {
            $urlBuilder->addBeginPath($params['beginPath']);
        }
        if (isset($params['endPath'])) {
            $urlBuilder->addEndPath($params['endPath']);
        }
        if (isset($params['replace'])) {
            if (!isset($params['replace'][1])) {
                $params['replace'][1] = '';
            }
            list($search, $replace) = $params['replace'];
            $urlBuilder->replacePath($search, $replace);
        }
        if (isset($params['args'])) {
            $urlBuilder->setArgs($params['args']);
        }
        if (isset($params['addCSRF'])) {
            /** @var \rock\csrf\CSRF $csrf */
            $csrf = Instance::ensure(isset($params['csrf']) ? $params['csrf'] : 'csrf', '\rock\csrf\CSRF', false);
            if ($csrf instanceof \rock\csrf\CSRF) {
                $params['addArgs'] = array_merge(
                    [$csrf->csrfParam => $csrf->get()],
                    Helper::getValue($params['csrf'], [])
                );
            }
        }
        if (isset($params['addArgs'])) {
            $urlBuilder->addArgs($params['addArgs']);
        }
        if (isset($params['anchor'])) {
            $urlBuilder->addAnchor($params['anchor']);
        }

        return $urlBuilder->get(Helper::getValue($params['const'], 0), (bool)Helper::getValue($params['selfHost']));
    }

    /**
     * Converting array to JSON-object.
     *
     * @param array $array current array
     * @return string
     */
    public static function arrayToJson($array)
    {
        if (empty($array)) {
            return null;
        }

        return Json::encode($array) ?: null;
    }

    /**
     * Get thumb.
     *
     * @param string $path src to image
     * @param array $params params:
     *
     * - type:     get `src`, `<a>`, `<img>` (default: `<img>`)
     * - w:        width
     * - h:        height
     * - q:        quality
     * - class:    attr `class`
     * - alt:      attr `alt`
     * - const
     * - dummy
     * @return string
     */
    public static function thumb($path, array $params)
    {
        if (empty($path)) {
            if (empty($params['dummy'])) {
                return '';
            }
            $path = $params['dummy'];
        }
        $const = Helper::getValue($params['const'], 1, true);
            /** @var ImageProvider $imageProvider */
        $imageProvider = Instance::ensure(isset($params['imageProvider']) ? $params['imageProvider'] : 'imageProvider', '\rock\image\ImageProvider');
        $src = $imageProvider->get($path, Helper::getValue($params['w']), Helper::getValue($params['h']));
        if (!($const & ThumbInterface::WITHOUT_WIDTH_HEIGHT)) {
            $params['width'] = $imageProvider->width;
            $params['height'] = $imageProvider->height;
        }
        unset($params['h'], $params['w'], $params['type'], $params['const']);

        return $const & ThumbInterface::OUTPUT_IMG ? Html::img($src, $params) : $src;
    }
}