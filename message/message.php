<?php
namespace Cyan\CMS;

/**
 * Class Message
 * @package Cyan\CMS
 */
abstract class Message
{
    public static $ERROR = 'error';
    public static $INFO =  'info';
    public static $SUCCESS = 'success';
    public static $WARNING = 'warning';

    /**
     * @var array
     */
    private static $classes = [
        'error' => [
            'class' => 'alert-danger'
        ],
        'info' => [
            'class' => 'alert-info'
        ],
        'success' => [
            'class' => 'alert-success'
        ],
        'warning' => [
            'class' => 'alert-warning'
        ]
    ];

    /**
     * @param array $classes
     */
    public static function setClasses(array $classes)
    {
        self::$classes = $classes;
    }

    /**
     * Enqueue message and redirect
     *
     * @param Application $App
     * @param $type
     * @param $message
     * @param $redirect_identifier
     * @param $redirect_parameters
     */
    public static function enqueueRedirect($App, $type, $message, $redirect_identifier, $redirect_parameters = [])
    {
        if (!($App instanceof \Cyan\Framework\ApplicationWeb)) {
            throw new \Cyan\Framework\ApplicationException('$App must be a instance of Cyan\Framework\ApplicationWeb.');
        }
        $type = strtolower($type);
        $App->enqueueMessage($App->Text->translate($message),ucfirst($type),self::$classes[$type]);
        $App->Router->redirect( strpos($redirect_identifier,'://') === false ? $App->Router->generate($redirect_identifier, $redirect_parameters) : $redirect_identifier);
    }

    /**
     * Enqueue a message
     *
     * @param $App
     * @param $type
     * @param $message
     */
    public static function enqueue($App, $type, $message)
    {
        if (!($App instanceof \Cyan\Framework\ApplicationWeb)) {
            throw new \Cyan\Framework\ApplicationException('$App must be a instance of Cyan\Framework\ApplicationWeb.');
        }
        $type = strtolower($type);
        $App->enqueueMessage($App->Text->translate($message),ucfirst($type),self::$classes[$type]);
    }
}