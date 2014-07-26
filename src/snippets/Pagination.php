<?php
namespace rock\template\snippets;

use rock\template\helpers\Helper;
use rock\template\helpers\String;
use rock\template\Snippet;
use rock\template\url\Url;

/**
 * Snippet "Pagination"
 *
 * Examples:
 *
 * ```php
 * $template = new \rock\Template;
 * $countItems = 10;
 * $params = [
 *      'array' => \rock\helpers\Pagination::get($countItems, (int)$_GET['page'])
 * ];
 * $template->getSnippet('\rock\snippet\Pagination', $params);
 * ```
 *
 * With ActiveDataProvider:
 *
 * ```php
 * $provider = new \rock\db\ActiveDataProvider(
 *  [
 *      'query' => Post::find()->asArray()->all(),
 *      'pagination' => ['limit' => 10, 'sort' => SORT_DESC, 'pageCurrent' => (int)$_GET['num']]
 *  ]
 * );
 *
 *  $params = [
 *      'array' => $provider->getPagination(),
 *      'pageVar' => 'num'
 * ];
 * $template->getSnippet('\rock\snippet\Pagination', $params);
 * ```
 */
class Pagination extends Snippet
{
    /**
     * @var array
     */
    public $array;

    /**
     * May be a callable, snippet, and instance
     *
     * ```
     * [[Pagination?call=`\foo\FooController.getPagination`]]
     * [[Pagination?call=`context.getPagination`]] - self context
     * ```
     *
     * ```php
     * $params = [
     *  'call' => ['\foo\FooController', 'getPagination']
     * ];
     * (new \rock\Template)->getSnippet('Pagination', $params);
     * ```
     *
     * @var string|array
     */
    public $call;

    public $pageVar;

    /**
     * tpl active
     *
     * @var string
     */
    public $pageActiveTpl;

    public $pageNumTpl;

    public $pageFirstName;

    public $pageFirstTpl;

    public $pageLastName;

    public $pageLastTpl;

    public $wrapperTpl;

    /**
     * url-arguments
     *
     * @var
     */
    public $pageArgs;
    public $pageAnchor;

    public $autoEscape = false;

    /** @var Url */
    public $urlManager;


    public function init()
    {
        parent::init();
        if (!isset($this->urlManager)) {
            $this->urlManager = new Url;
        } elseif ($this->urlManager instanceof \Closure) {
            $this->urlManager = call_user_func($this->urlManager, $this);
        }
    }

    public function get()
    {
        if (empty($this->array) && empty($this->call)) {
            return null;
        }
        $this->calculateArray();
        if (!isset($this->array['pageCount']) ||
            (int)$this->array['pageCount'] === 1 ||
            empty($this->array['pageDisplay'])
        ) {
            return null;
        }
        $data = $this->array;
        /**
         * if exits args-url
         */
        if (!$this->calculateArgs()) {
            return null;
        }
        /**
         * set name of arg-url by pagination
         */
        $pageVar = !empty($this->pageVar)
            ? $this->pageVar
            : (!empty($data['pageVar'])
                ? $data['pageVar']
                : \rock\template\helpers\Pagination::PAGE_VAR
            );
        /**
         * Numeration
         */
        $num = $this->calculateNum($data, $pageVar);
        $pageFirstName = $this->calculateFirstPage($data, $pageVar);
        $pageLastName = $this->calculateLastPage($data, $pageVar);

        return $this->template->replaceParamByPrefix(
            isset($this->wrapperTpl) ? $this->wrapperTpl : '@rock.views/pagination/wrapper',
            [
                'num' => $num,
                'pageFirst' => $pageFirstName,
                'pageLast' => $pageLastName,
                'pageCurrent' => Helper::getValue($data['pageCurrent']),
                'countMore' => Helper::getValue($data['countMore'])
            ]
        );
    }

    protected function calculateArray()
    {
        $this->array = Helper::getValue($this->array);
        if (!empty($this->call)) {
            $this->array = $this->callFunction($this->call);
        }
    }

    /**
     * Calculate url args
     *
     * @return bool
     */
    protected function calculateArgs()
    {
        if (empty($this->pageArgs)) {
            return true;
        }
        if (is_string($this->pageArgs)) {
            parse_str(
                String::trimSpaces($this->pageArgs),
                $this->pageArgs
            );
        }
        if (empty($this->pageArgs) || !is_array($this->pageArgs)) {
            return false;
        }
        foreach ($this->pageArgs as $key => $val) {
            if (empty($key) || empty($val)) {
                continue;
            }
            $this->pageArgs[$key] = strip_tags($val);
        }

        return true;
    }

    protected function calculateNum(array $data, $pageVar)
    {
        $result = '';
        foreach ($data['pageDisplay'] as $num) {
            $this->pageArgs[$pageVar] = $num;
            $this->urlManager->reset();
            $url = $this->urlManager->addArgs($this->pageArgs)->addAnchor($this->pageAnchor)->get();
            /**
             * for active page
             */
            if ((int)$data['pageCurrent'] === (int)$num) {
                $result .=
                    $this->template->replaceParamByPrefix(
                        isset($this->pageActiveTpl) ? $this->pageActiveTpl
                            : '@rock.views/pagination/numActive',
                        [
                            'num' => $num,
                            'url' => $url
                        ]
                    );
                continue;
            }
            /**
             * for default page
             */
            $result .=
                $this->template->replaceParamByPrefix(
                    isset($this->pageNumTpl) ? $this->pageNumTpl : '@rock.views/pagination/num',
                    [
                        'num' => $num,
                        'url' => $url
                    ]
                );
        }

        return $result;
    }

    protected function calculateFirstPage(array $data, $pageVar)
    {
        if (!$pageFirst = (int)$data['pageFirst']) {
            return null;
        }
        $pageFirstName = !empty($this->pageFirstName) ? $this->pageFirstName : 'page first';
        $this->pageArgs[$pageVar] = $pageFirst;
        $this->urlManager->reset();

        return $this->template->replaceParamByPrefix(
            isset($this->pageFirstTpl) ? $this->pageFirstTpl : '@rock.views/pagination/first',
            [
                'url' => $this->urlManager
                        ->addArgs($this->pageArgs)
                        ->addAnchor($this->pageAnchor)
                        ->get(),
                'pageFirstName' => $pageFirstName
            ]
        );
    }

    protected function calculateLastPage(array $data, $pageVar)
    {
        if (!$pageLast = (int)$data['pageLast']) {
            return null;
        }
        $pageLastName = !empty($this->pageLastName) ? $this->pageLastName : 'page last';
        $this->pageArgs[$pageVar] = $pageLast;
        $this->urlManager->reset();

        return $this->template->replaceParamByPrefix(
            isset($this->pageLastTpl) ? $this->pageLastTpl : '@rock.views/pagination/last',
            [
                'url' => $this->urlManager
                        ->addArgs($this->pageArgs)
                        ->addAnchor($this->pageAnchor)
                        ->get(),
                'pageLastName' => $pageLastName
            ]
        );
    }
}

