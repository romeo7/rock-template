<?php
namespace rock\snippets;

use rock\execute\Execute;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\helpers\StringHelper;

/**
 * Snippet "IfSnippet"
 *
 * [[if
 *      ?subject=`:foo > 1 && :foo < 3`
 *      ?operands=`{"foo" : "[[+foo]]"}`
 *      ?then=`success`
 *      ?else=`fail`
 * ]]
 */
class IfSnippet extends Snippet
{
    /**
     * Condition (strip html/php-tags). E.g `:foo > 1 && :foo < 3`
     * @var string
     */
    public $subject;
    /**
     * Compliance of the operand to the placeholder. E.g. `{"foo" : "[[+foo]]"}`
     * @var array
     */
    public $operands = [];
    /** @var  string */
    public $then;
    /** @var  string */
    public $else;
    /**
     * Adding external placeholders in `tpl` and `wrapperTpl`.
     * @var array
     */
    public $addPlaceholders = [];
    /** @var  Execute|string|array */
    protected $execute = 'execute';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->execute = Instance::ensure($this->execute, '\rock\execute\EvalExecute');
    }

    public function get()
    {
        if (!isset($this->subject, $this->operands, $this->then) ||
            empty($this->operands)
        ) {
            return null;
        }
        $operands = $this->operands;
        $this->template->addMultiPlaceholders($this->template->findPlaceholders($this->addPlaceholders));
        $paramsTpl = [
            'subject' => $this->subject,
            'params' => $operands,
            'then' => $this->then,
            'template' => $this->template
        ];

        if (isset($this->else)) {
            $paramsTpl['else'] = $this->else;
        }
        $data = [];
        $this->subject = strip_tags($this->subject);
        foreach ($operands as $keyParam => $valueParam) {
            $valueParam = Helper::toType($valueParam);
            if (is_string($valueParam)) {
                $valueParam = addslashes($valueParam);
            }
            $data[$keyParam] = $valueParam;
        }

        $value = '
            $template = $params[\'template\'];
            if (' . preg_replace('/:([\\w]+)/', '$data[\'$1\']', $this->subject) . ') {
                return $template->replace($params[\'then\']);
            }' .
            (isset($this->else)
                ? ' else {return $template->replace($params[\'else\']);}'
                : null
            );

        return $this->execute->get(StringHelper::removeSpaces($value), $paramsTpl, $data);
    }
}