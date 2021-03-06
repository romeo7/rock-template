<?php

namespace rock\snippets;


use rock\components\Model;
use rock\file\UploadedFile;
use rock\helpers\ArrayHelper;
use rock\helpers\Helper;
use rock\helpers\Instance;
use rock\template\Html;
use rock\template\Template;

class ActiveFormSnippet extends Snippet
{
    public $config = [];
    /** @var  \rock\components\Model|string|array */
    public $model;
    public $fields = [];
    public $submitButton = [];
    public $load;
    public $prepareAttributes = [];
    public $validate = false;
    /**
     * Name/inline wrapper template.
     *
     * @var string
     */
    public $wrapperTpl;
    /**
     * Result to placeholder (name of placeholder).
     *
     * @var string
     */
    public $toPlaceholder;
    /**
     * @inheritdoc
     */
    public $sanitize = Template::SANITIZE_DISABLE;

    /**
     * @inheritdoc
     */
    public function get()
    {
        $this->model = Instance::ensure($this->model);

        if (!$this->model->isLoad()) {
            $this->validate = false;
        }

        if ($this->validate === true) {
            $this->prepareAttributes();
            if (!$this->model->validate()) {
                $this->errorsToPlaceholders($this->model);
            }
        }

        $this->config['model'] = $this->model;

        ob_start();
        $form = \rock\widgets\ActiveForm::begin($this->config);
        $output = ob_get_clean();
        $fields = [];
        $fields[] = $output;
        if (!empty($this->fields)) {
            $this->prepareFields($form, (array)$this->fields, $fields);
        }

        $fields[] = $this->prepareSubmitButton($this->submitButton);
        ob_start();
        ob_implicit_flush(false);
        \rock\widgets\ActiveForm::end();
        $output = ob_get_clean();
        $fields[] = $output;
        $result = implode("\n", $fields);
        // Inserting content into wrapper template (optional)
        if (!empty($this->wrapperTpl)) {
            $result = $this->parseWrapperTpl($result, $this->wrapperTpl);
        }
        // To placeholder
        if (!empty($this->toPlaceholder)) {
            $this->template->addPlaceholder($this->toPlaceholder, $result, true);
            $this->template->cachePlaceholders[$this->toPlaceholder] = $result;
            return '';
        }

        return $result;
    }

    protected function prepareFields(\rock\widgets\ActiveForm $form, array $fields, array &$result)
    {
        $form->submitted = $this->model->isLoad();
        foreach ($fields as $attributeName => $params) {
            if (is_int($attributeName)) {
                $result[] = $this->template->replace($params);
                continue;
            }
            if (isset($params['options']['enabled']) && $params['options']['enabled'] === false) {
                continue;
            }
            unset($params['options']['enabled']);
            $field = $form->field($this->model, $attributeName, Helper::getValue($params['options'], []));
            unset($params['options']);

            foreach ($params as $additionName => $additionParams) {
                if (is_int($additionName)) {
                    $additionName = $additionParams;
                    unset($additionParams);
                }
                call_user_func_array([$field, $additionName], Helper::getValue($additionParams, []));
            }

            $result[] = $field->render();
        }
        return $result;
    }

    protected function prepareSubmitButton($submit)
    {
        if (empty($submit)) {
            return Html::submitButton();
        }
        $submit = (array)$submit;
        $submit[1] = isset($submit[1]) ? $submit[1] : [];
        list($content, $options) = $submit;
        if (!empty($this->config['clientAction']) && !isset($options['data']['ng-disabled'])) {
            $options['data']['ng-disabled'] = 'isSend()';
        }
        if (isset($options['wrapperTpl'])) {
            $wrapper = $options['wrapperTpl'];
            unset($options['wrapperTpl']);
            return $this->parseWrapperTpl(Html::submitButton($this->template->replace($content), $options), $wrapper);
        }
        return Html::submitButton($this->template->replace($content), $options);
    }

    /**
     * Inserting content into wrapper template.
     *
     * @param string $value content
     * @param        $wrapperTpl
     * @return string
     */
    protected function parseWrapperTpl($value, $wrapperTpl)
    {
        $value = $this->template->replaceByPrefix($wrapperTpl, ['output' => $value]);
        $this->template->removePlaceholder('output');

        return $value;
    }

    protected function prepareAttributes()
    {
        foreach ($this->prepareAttributes as $attributeName => $type) {
            if (isset($this->model->$attributeName)) {
                if (class_exists('\rock\file\UploadedFile')) {
                    if ($type == 'file') {
                        $this->model->$attributeName = UploadedFile::getInstance($this->model, $attributeName);
                    } elseif ($type == 'multiFiles') {
                        $this->model->$attributeName = UploadedFile::getInstances($this->model, $attributeName);
                    }
                }
            }
        }
    }

    protected function errorsToPlaceholders(Model $model)
    {
        $errors = ArrayHelper::only($model->getErrors(), [], $model->safeAttributes());
        $this->template->addMultiPlaceholders($errors);
    }
}