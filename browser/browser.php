<?php
namespace Cyan\CMS;

use Cyan\Framework\TraitSingleton;

/**
 * Class Browser
 * @package Cyan\CMS
 *
 * @method Browser getInstance
 */
class Browser
{
    use TraitSingleton;

    private $tablet_browser = 0;
    private $mobile_browser = 0;
    private $os;

    public function __construct()
    {
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $this->tablet_browser++;
        }
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            $this->mobile_browser++;
        }
        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
            $this->mobile_browser++;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-'
        );

        if (in_array($mobile_ua,$mobile_agents)) {
            $this->mobile_browser++;
        }
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'opera mini') > 0) {
            $this->mobile_browser++;
            //Check for tablets on opera mini alternative headers
            $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])?$_SERVER['HTTP_X_OPERAMINI_PHONE_UA']:(isset($_SERVER['HTTP_DEVICE_STOCK_UA'])?$_SERVER['HTTP_DEVICE_STOCK_UA']:''));
            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
                $this->tablet_browser++;
            }
        }

        preg_match("/iPhone|Android|iPad|iPod|webOS/", $_SERVER['HTTP_USER_AGENT'], $matches);
        $this->os = current($matches);
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        return ($this->mobile_browser > 0);
    }

    /**
     * @return bool
     */
    public function isTablet()
    {
        return ($this->tablet_browser > 0);
    }

    /**
     * @return bool
     */
    public function isDesktop()
    {
        return (!$this->isTablet() && !$this->isMobile());
    }

    /**
     * @return string
     */
    public function getDevice()
    {
        if ($this->isTablet()) {
            return 'tablet';
        } elseif ($this->isMobile()) {
            return 'mobile';
        } else {
            return 'desktop';
        }
    }

    /**
     * @return string
     */
    public function getOS()
    {
        switch (strtolower($this->os)) {
            case 'ipad':
            case 'ipod':
            case 'iphone':
                $os = 'ios';
                break;
            case 'android':
                $os = 'android';
                break;
            default:
                $os = 'index';
                break;
        }
        return $os;
    }
}