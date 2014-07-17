<?php
use rock\template\date\Date;
use rock\template\execute\CacheExecute;
use rock\template\filters\BaseFilter;
use rock\template\filters\ConditionFilter;
use rock\template\filters\StringFilter;
use rock\template\snippets\Formula;
use rock\template\snippets\ForSnippet;
use rock\template\snippets\IfSnippet;
use rock\template\snippets\ListView;
use rock\template\snippets\Pagination;
use rock\template\snippets\Url;

$datetime = function(){
    $datetime = new Date;
    $datetime->formats = [
        'mysql' => Date::ISO_DATETIME_FORMAT,
        'dmy'   => function(\rock\template\date\DateTime $dateTime){
                $nowYear  = date('Y');
                $lastYear = $dateTime->format('Y');

                return $nowYear > $lastYear
                    ? $dateTime->format('j F Y')
                    : $dateTime->format('d F');
            },
        'dmyhm' => function(\rock\template\date\DateTime $dateTime){
                $nowYear  = date('Y');
                $lastYear = $dateTime->format('Y');
                return $nowYear > $lastYear
                    ? $dateTime->format('j F Y H:i')
                    : $dateTime->format('j F H:i');
            },
    ];
    return $datetime;
};

$execute = function(){
    $execute = new CacheExecute();
    $execute->path = '@runtime/execute';
    return $execute;
};

return [
    'filters' => [
        'stripString' => [
            'class' => StringFilter::className(),
        ],
        'stripTags' => [
            'class' => StringFilter::className(),
        ],
        'truncate' => [
            'class' => StringFilter::className(),
        ],
        'truncateWords' => [
            'class' => StringFilter::className(),
        ],
        'upper' => [
            'class' => StringFilter::className(),
        ],
        'lower' => [
            'class' => StringFilter::className(),
        ],
        'upperFirst' => [
            'class' => StringFilter::className(),
        ],
        'encode' => [
            'class' => StringFilter::className(),
        ],
        'decode' => [
            'class' => StringFilter::className(),
        ],
        'formula' => [
            'class' => BaseFilter::className(),
        ],
        'arrayToString' => [
            'class' => BaseFilter::className(),
        ],
        'unserialize' => [
            'class' => BaseFilter::className(),
        ],
        'serialize' => [
            'class' => BaseFilter::className(),
        ],
        'replaceTpl' => [
            'class' => BaseFilter::className(),
        ],
        'modifyDate' => [
            'class' => BaseFilter::className(),
            'handlers' => $datetime
        ],
        'date' => [
            'class' => BaseFilter::className(),
            'handlers' => $datetime
        ],
        'modifyUrl' => [
            'class' => BaseFilter::className(),
        ],
        'url' => [
            'class' => BaseFilter::className(),
        ],
        'arrayToJson' => [
            'class' => BaseFilter::className(),
        ],
        'toJson' => [
            'class' => BaseFilter::className(),
        ],
        'jsonToArray' => [
            'class' => BaseFilter::className(),
        ],
        'toArray' => [
            'class' => BaseFilter::className(),
        ],
        'notEmpty' => [
            'class' => ConditionFilter::className(),
        ],
        'empty' => [
            'class' => ConditionFilter::className(),
            'name' => '_empty'
        ],
        'if' => [
            'class' => ConditionFilter::className(),
            'name' => '_if'
        ],
    ],
    'snippets' => [
        'ListView' => [
            'class'        => ListView::className(),
        ],
        'List' => [
            'class'        => ListView::className(),
        ],
        'Date' => [
            'class'        => \rock\template\snippets\Date::className(),
            'datetime' => $datetime
        ],
        'For' => [
            'class'        => ForSnippet::className(),
        ],
        'Formula' => [
            'class'        => Formula::className(),
            'execute' => $execute
        ],
        'If' => [
            'class'        => IfSnippet::className(),
        ],
        'Pagination' => [
            'class'        => Pagination::className(),
        ],
        'Url' => [
            'class'        => Url::className(),
        ],
    ]
];