<?php
namespace ValorPay\CardPay\Model\Adminhtml\Source;

/**
 * Class to validate valorpay APP keys before saving setting values 
 * if success then proceed and save data otherwise its throw error
 */
class ValidateKey 
{   

    protected $_curl;

    public function __construct(
    	\Magento\Framework\HTTP\Client\Curl $curl
    )
    {
        $this->_curl = $curl;
     }

    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {

        $groups = $subject->getGroups();

        if( !isset($groups) || !isset($groups["valorpay_gateway"]) ) return $proceed();

        $appid     = $groups["valorpay_gateway"]["fields"]["appid"]["value"];
        $authkey   = $groups["valorpay_gateway"]["fields"]["appkey"]["value"];
        $epi       = $groups["valorpay_gateway"]["fields"]["epi"]["value"];
        $authtoken = $groups["valorpay_gateway"]["fields"]["authtoken"]["value"];
        $sandbox   = $groups["valorpay_gateway"]["fields"]["sandbox"]["value"];
        
        if( $appid == "******" ) return $proceed();

        $requestData = array(
            'app_id'     => $appid,
            'auth_key'   => $authkey,
            'epi'        => $epi,
            'auth_token' => $authtoken,
            'mtype'      => 'validate'
        );
        
        $validateurl = "https://vt.valorpaytech.com";
        if( $sandbox == 1 )	{
            $validateurl = "https://vt-staging.valorpaytech.com:4430";
        }

        $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->_curl->post($validateurl, http_build_query($requestData));
         
        //response will contain the output of curl request
        $response = $this->_curl->getBody();
        
        $response = json_decode($response);

        if( $response->error_no != "00" ) {
                
            throw new \Magento\Framework\Exception\ValidatorException(
                __("ValorPay APP KEYS Error: (".$response->error_no.") ".$response->mesg.", ".$response->desc)
            );

        }
        
        return $proceed();
    
    }

}