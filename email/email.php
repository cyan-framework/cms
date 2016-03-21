<?php
namespace Cyan\CMS;

/**
 * Class Email
 * @package Cyan\CMS
 */
abstract class Email
{
    public static function send($app, $config)
    {
        /** @var Cyan $Cyan */
        $Cyan = \Cyan::initialize();

        $mail = new \PHPMailer(true);
        $phpMailerIdentifier = $app->getName().':config.phpmailer';
        $phpMailerConfig = $Cyan->Finder->getIdentifier($phpMailerIdentifier, []);

        if (empty($phpMailerConfig)) {
            throw new EmailException(sprintf('Config not found: %s', $Cyan->Finder->getPath($phpMailerIdentifier)));
        }

        $config = array_filter(array_merge_recursive($config, $phpMailerConfig));

        foreach ($config as $key => $value) {
            if (property_exists($mail, $key)) {
                $mail->$key = $value;
            } elseif (method_exists($mail, $key)) {
                if (is_array($value)) {
                    switch (count($value))
                    {
                        case 1:
                            $mail->$key($value[0]);
                            break;
                        case 2:
                            $mail->$key($value[0], $value[1]);
                            break;
                        case 3:
                            $mail->$key($value[0], $value[1], $value[2]);
                            break;
                    }
                } else {
                    $mail->$key($value);
                }
            }
        }

        return $mail->send();
    }
}